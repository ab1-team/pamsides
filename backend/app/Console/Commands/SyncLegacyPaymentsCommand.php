<?php

namespace App\Console\Commands;

use App\Services\LegacyPaymentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyPaymentsCommand extends Command
{
    protected $signature = 'legacy:sync-payments
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--only=both : bills | fees | both}
                            {--payments-only=0 : Abaikan awalan "Pendapatan", hanya "Bayar" saja}';

    protected $description = 'Selaraskan bill_payments + payments dengan legacy transactions (relasi lewat usage_id, bukan nama)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $only = (string) $this->option('only');
        // Default: "Pendapatan" di legacy = pengakuan bahwa pelanggan lunas.
        $includeRevenue = ! $this->option('payments-only');

        $this->info('=== Selaraskan pembayaran dengan legacy transactions ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: data akan diubah.');
        $this->info("business_id={$biz} | only={$only} | 'Pendapatan' ikut=".($includeRevenue ? 'ya' : 'tidak'));
        $this->line('');

        $N = DB::connection();
        $staffMap = $this->staffMap($biz);
        $fallback = (int) ($N->table('users')->whereIn('role', ['admin', 'teknisi'])->orderBy('id')->value('id') ?? 1);

        $applied = ['bp_create' => 0, 'bp_update' => 0, 'bp_delete' => 0, 'fee_create' => 0, 'fee_update' => 0, 'status_update' => 0];

        // ---------- bill_payments ----------
        if ($only === 'bills' || $only === 'both') {
            $this->info('--- bill_payments ---');
            $src = LegacyPaymentSource::collect($biz, $includeRevenue);
            $payments = $src['payments'];
            $billTotals = $src['billTotals'];

            $this->line('  legacy usage->bill terpetakan : '.count(LegacyPaymentSource::usageToBillMap($biz)));
            $this->line('  event pembayaran (bill+tanggal) : '.count($payments));
            $this->line('  tagihan yang punya pembayaran     : '.count($billTotals));
            $this->line('  transaksi tanpa pasangan tagihan : '.$src['noUsage']);
            $this->line('  bill_payments DB baru sekarang    : '.$N->table('bill_payments')->count());

            // Baris DB baru: index dengan key komposit yang sama.
            $existing = [];
            foreach ($N->table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at', 'confirmed_by']) as $bp) {
                $date = substr((string) $bp->paid_at, 0, 10);
                $existing[$bp->bill_id.'|'.$date][] = $bp;
            }

            $inserts = [];
            $updates = [];
            $keepIds = [];

            foreach ($payments as $key => $p) {
                [$billId, $date] = explode('|', $key);
                $billId = (int) $billId;
                $keepIds[] = $billId.'|'.$date;

                $rows = $existing[$key] ?? [];
                if (count($rows) === 0) {
                    $inserts[] = [
                        'bill_id' => $billId,
                        'amount_paid' => $p['amount_paid'],
                        'confirmed_by' => $staffMap[$p['legacy_user_id']] ?? $fallback,
                        'paid_at' => $p['paid_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $applied['bp_create']++;
                } else {
                    $cur = $rows[0];
                    $set = [];
                    if (abs((float) $cur->amount_paid - (float) $p['amount_paid']) >= 0.005) {
                        $set['amount_paid'] = $p['amount_paid'];
                    }
                    // confirmed_by: hanya isi kalau legacy punya user yg terpetakan
                    if ($p['legacy_user_id'] > 0 && isset($staffMap[$p['legacy_user_id']])) {
                        $want = $staffMap[$p['legacy_user_id']];
                        if ((int) $cur->confirmed_by !== $want) {
                            $set['confirmed_by'] = $want;
                        }
                    }
                    if ($set) {
                        $updates[] = ['id' => (int) $cur->id, 'set' => $set];
                        $applied['bp_update']++;
                    }
                    // Baris kembar (>1 payment di tanggal sama) → sisanya dihapus
                    foreach (array_slice($rows, 1) as $dup) {
                        $keepIds[] = 'dup:' . $dup->id;
                    }
                }
            }

            $keepSet = array_flip($keepIds);
            $deletes = [];
            foreach ($existing as $key => $rows) {
                if (isset($keepSet[$key])) {
                    continue;
                }
                foreach ($rows as $r) {
                    $deletes[] = (int) $r->id;
                }
            }

            $this->line('  perlu dibuat   : '.$applied['bp_create']);
            $this->line('  perlu diupdate : '.$applied['bp_update']);
            $this->line('  perlu dihapus  : '.count($deletes).' (tidak ada pembayaran legacy untuk tagihan/tanggal itu)');

            if (! $dry) {
                foreach (array_chunk($inserts, 400) as $chunk) {
                    $N->table('bill_payments')->insert($chunk);
                }
                foreach ($updates as $u) {
                    $N->table('bill_payments')->where('id', $u['id'])->update($u['set'] + ['updated_at' => now()]);
                }
                if ($deletes) {
                    foreach (array_chunk($deletes, 400) as $chunk) {
                        $N->table('bill_payments')->whereIn('id', $chunk)->delete();
                    }
                }
                $applied['bp_delete'] = count($deletes);
                $this->info('  tersimpan: +'.count($inserts).' baru, ~'.count($updates).' update, -'.count($deletes).' hapus');
            }
            $this->line('');

            // Status tagihan: mengikuti legacy usages (sumber kebenaran),
            // bukan hasil penjumlahan bill_payments, karena nominal legacy
            // mencakup komisi collectors yang tidak ada di app baru.
            if (! $dry) {
                $updated = 0;
                foreach ($N->table('monthly_bills')->get(['id', 'status']) as $b) {
                    $sum = (float) ($billTotals[$b->id] ?? 0);
                    if ($sum <= 0) {
                        continue;
                    }
                    $total = (float) $N->table('monthly_bills')->where('id', $b->id)->value('total_amount');
                    $want = $sum >= $total - 1 ? 'paid' : 'unpaid';
                    if ($b->status !== $want) {
                        $N->table('monthly_bills')->where('id', $b->id)->update(['status' => $want, 'updated_at' => now()]);
                        $updated++;
                    }
                }
                $applied['status_update'] = $updated;
                $this->info('  status tagihan disesuaikan: '.$updated);
            }
            $this->line('');
        }

        // ---------- payments (biaya instalasi) ----------
        if ($only === 'fees' || $only === 'both') {
            $this->info('--- payments (biaya instalasi) ---');
            $feeSrc = LegacyPaymentSource::collectInstallationFees($biz);
            $fees = $feeSrc['fees'];

            $this->line('  legacy transaksi biaya instalasi : '.count($fees));
            $this->line('  tanpa pasangan ticket             : '.count($feeSrc['unmatched']));
            foreach (array_slice($feeSrc['unmatched'], 0, 5) as $u) {
                $this->line('    trx='.$u['trx_id'].' inst='.$u['inst_id'].' kode='.($u['code'] === '' ? '(kosong)' : $u['code']));
            }
            $this->line('  payments DB baru sekarang        : '.$N->table('payments')->count());

            $existing = [];
            foreach ($N->table('payments')->where('type', 'installation_fee')->get(['id', 'ticket_id', 'amount', 'paid_at', 'status', 'confirmed_by']) as $p) {
                $existing[(int) $p->ticket_id] = $p;
            }

            $ins = 0; $upd = 0; $del = 0;
            foreach ($fees as $ticketId => $f) {
                $cur = $existing[$ticketId] ?? null;
                if (! $cur) {
                    $ins++;
                    continue;
                }
                $set = [];
                if (abs((float) $cur->amount - (float) $f['amount']) >= 0.005) {
                    $set['amount'] = $f['amount'];
                }
                if ($f['paid_at'] && (string) $cur->paid_at !== $f['paid_at']) {
                    $set['paid_at'] = $f['paid_at'];
                }
                if ($set && ! $dry) {
                    $N->table('payments')->where('id', $cur->id)->update($set + ['status' => 'confirmed', 'updated_at' => now()]);
                }
                if ($set) {
                    $upd++;
                }
            }

            $this->line('  perlu dibuat   : '.$ins);
            $this->line('  perlu diupdate : '.$upd);

            if (! $dry) {
                $rows = [];
                foreach ($fees as $ticketId => $f) {
                    if (isset($existing[$ticketId])) {
                        continue;
                    }
                    $rows[] = [
                        'ticket_id' => $ticketId,
                        'amount' => $f['amount'],
                        'type' => 'installation_fee',
                        'status' => 'confirmed',
                        'confirmed_by' => $staffMap[$f['legacy_user_id']] ?? $fallback,
                        'paid_at' => $f['paid_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                foreach (array_chunk($rows, 400) as $chunk) {
                    $N->table('payments')->insert($chunk);
                }
                $this->info('  payments dibuat: '.count($rows));
            }
        }

        $this->line('');
        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menerapkan.');

            return self::SUCCESS;
        }
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    /** @return array<int,int> legacy users.id → users.id DB baru */
    private function staffMap(int $biz): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        $newStaff = [];
        foreach ($N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->get(['id', 'name']) as $u) {
            $newStaff[strtolower(trim((string) $u->name))] = (int) $u->id;
        }

        $map = [];
        foreach ($L->table('users')->get(['id', 'nama']) as $lu) {
            $key = strtolower(trim((string) $lu->nama));
            if ($key !== '' && isset($newStaff[$key])) {
                $map[(int) $lu->id] = $newStaff[$key];
            }
        }

        return $map;
    }
}