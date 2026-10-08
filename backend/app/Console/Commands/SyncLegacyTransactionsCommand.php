<?php

namespace App\Console\Commands;

use App\Services\LegacyPaymentSource;
use App\Services\LegacyTicketAligner;
use App\Services\LegacyTransactionSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyTransactionsCommand extends Command
{
    protected $signature = 'legacy:sync-transactions
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--only=missing : missing | all}';

    protected $description = 'Selaraskan tabel transactions dengan legacy transactions (signature: tgl + kode_debet + kode_kredit + nominal + keterangan)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $only = (string) $this->option('only');

        $this->info('=== Selaraskan transactions dengan legacy ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: transaksi akan ditambah.');
        $this->info("business_id={$biz} | only={$only}");
        $this->line('');

        $N = DB::connection();

        $this->info('Mengumpulkan legacy transactions...');
        $src = LegacyTransactionSource::collect($biz);
        $legacy = $src['rows'];
        $this->line('  legacy biz'.$biz.' : ' . count($legacy) . ' transaksi (akun terpetakan)');
        $this->line('  DB baru            : ' . $N->table('transactions')->count());
        $this->line('');

        // Signature DB baru
        $newIdx = [];
        foreach ($N->table('transactions')->get(['id', 'tgl_transaksi', 'account_debet', 'account_kredit', 'saldo', 'keterangan_transaksi']) as $r) {
            $newIdx[LegacyTransactionSource::signatureOfNew($r)][] = (int) $r->id;
        }
        $this->line('  signature unik DB baru : ' . count($newIdx));

        // Cocokkan
        $missing = [];
        $matched = 0;
        foreach ($legacy as $row) {
            $sig = LegacyTransactionSource::signature($row);
            if (! empty($newIdx[$sig])) {
                array_shift($newIdx[$sig]);
                $matched++;
            } else {
                $missing[] = $row;
            }
        }
        $this->line('  sudah ada (cocok)     : ' . $matched);
        $this->line('  belum ada (perlu insert) : ' . count($missing));

        // Transaksi DB baru yang tidak punya pasangan legacy
        $orphan = 0;
        foreach ($newIdx as $left) {
            $orphan += count($left);
        }
        $this->line('  DB baru tanpa pasangan legacy : ' . $orphan);
        $this->line('');

        if ($only === 'missing' && ! $dry && $orphan > 0) {
            $this->warn('Mode --only=missing tidak menghapus transaksi tanpa pasangan.');
        }

        if ($missing) {
            $byDate = [];
            foreach ($missing as $r) {
                $ym = substr($r['tgl_transaksi'], 0, 7);
                $byDate[$ym] = ($byDate[$ym] ?? 0) + 1;
            }
            ksort($byDate);
            $this->info('  yang akan ditambahkan per bulan:');
            foreach ($byDate as $ym => $c) {
                $this->line(sprintf('    %s : %d', $ym, $c));
            }
            $this->line('');

            $byType = [];
            foreach ($missing as $r) {
                $t = $r['reverence_type'];
                $byType[$t] = ($byType[$t] ?? 0) + 1;
            }
            $this->info('  per reverence_type: ' . json_encode($byType));
            $sum = array_sum(array_column($missing, 'saldo'));
            $this->info('  total nominal: Rp ' . number_format((float) $sum));
            $this->line('');

            $this->info('  contoh 10:');
            foreach (array_slice($missing, 0, 10) as $r) {
                echo sprintf("   tgl=%s d=%s k=%s saldo=%-12s type=%-14s ket=%s\n",
                    $r['tgl_transaksi'], $r['account_debet'], $r['account_kredit'],
                    number_format((float) $r['saldo'], 2, '.', ''), $r['reverence_type'],
                    mb_substr($r['keterangan_transaksi'], 0, 40));
            }
            $this->line('');
        }

        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan --force untuk menambahkan.');

            return self::SUCCESS;
        }

        // Siapkan dependensi relasi
        $staffMap = $this->staffMap($biz);
        $fallback = (int) ($N->table('users')->whereIn('role', ['admin', 'teknisi'])->orderBy('id')->value('id') ?? 1);
        $usageToBill = LegacyPaymentSource::usageToBillMap($biz);
        $billPaymentByUsage = $this->billPaymentIndex($usageToBill);
        $ticketByCode = $N->table('customers')->pluck('ticket_id', 'customer_code')->all();
        $codeByInst = DB::connection('legacy')->table('installations')
            ->where('business_id', $biz)->pluck('kode_instalasi', 'id')->all();
        $ticketByInst = [];
        foreach ($codeByInst as $instId => $code) {
            $tid = $ticketByCode[trim((string) $code)] ?? null;
            if ($tid !== null) {
                $ticketByInst[(int) $instId] = (int) $tid;
            }
        }
        $technisiByTicket = $N->table('installation_tickets')->pluck('user_id', 'id')->all();

        $inserted = 0;
        $skipped = 0;
        $batch = [];

        foreach ($missing as $r) {
            $reverenceId = null;
            $penerimaKomisi = null;

            if ($r['reverence_type'] === 'monthly_bill' || $r['reverence_type'] === 'overdue_bill') {
                $reverenceId = $usageToBill[$r['usage_id']] ?? null;
            } elseif ($r['reverence_type'] === 'bill_payment') {
                $key = ($usageToBill[$r['usage_id']] ?? 0) . '|' . $r['tgl_transaksi'];
                $reverenceId = $billPaymentByUsage[$key] ?? null;
            } elseif ($r['reverence_type'] === 'payment') {
                $tid = $ticketByInst[$r['installation_id']] ?? null;
                if ($tid !== null) {
                    $reverenceId = (int) ($N->table('payments')->where('ticket_id', $tid)->value('id') ?? 0) ?: null;
                    $penerimaKomisi = $technisiByTicket[$tid] ?? null;
                }
            } elseif ($r['reverence_type'] === 'commission') {
                $tid = $ticketByInst[$r['installation_id']] ?? null;
                $penerimaKomisi = $tid !== null ? ($technisiByTicket[$tid] ?? null) : null;
            }

            if ($r['reverence_type'] !== 'other' && $reverenceId === null && $r['usage_id'] > 0) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'tgl_transaksi' => $r['tgl_transaksi'],
                'account_debet' => $r['account_debet'],
                'account_kredit' => $r['account_kredit'],
                'transaction_group' => $r['transaction_group'],
                'reverence_type' => $r['reverence_type'] !== 'other' ? $r['reverence_type'] : null,
                'reverence_id' => $reverenceId,
                'penerima_komisi_id' => $penerimaKomisi,
                'keterangan_transaksi' => $r['keterangan_transaksi'],
                'relasi' => $r['relasi'],
                'saldo' => $r['saldo'],
                'urutan' => $r['urutan'],
                'id_user' => $staffMap[$r['legacy_user_id']] ?? $fallback,
                'created_at' => $r['created_at'] ?? now(),
                'updated_at' => now(),
            ];
            $inserted++;

            if (count($batch) >= 500) {
                $N->table('transactions')->insert($batch);
                $this->line('   ... +'.count($batch));
                $batch = [];
            }
        }
        if ($batch) {
            $N->table('transactions')->insert($batch);
        }

        $this->line('');
        $this->info('Selesai.');
        $this->line('  transaksi ditambahkan : '.$inserted);
        $this->line('  dilewati (tanpa relasi) : '.$skipped);
        $this->line('  transactions sekarang   : '.$N->table('transactions')->count());

        return self::SUCCESS;
    }

    /** @return array<string,int> "bill_id|tanggal" → bill_payments.id */
    private function billPaymentIndex(array $usageToBill): array
    {
        $N = DB::connection();
        $idx = [];
        foreach ($N->table('bill_payments')->get(['id', 'bill_id', 'paid_at']) as $bp) {
            $idx[$bp->bill_id . '|' . substr((string) $bp->paid_at, 0, 10)] = (int) $bp->id;
        }

        return $idx;
    }

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