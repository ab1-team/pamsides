<?php

namespace App\Console\Commands;

use App\Services\LegacyPaymentSource;
use App\Services\LegacyTicketAligner;
use App\Services\LegacyTransactionSource;
use App\Support\MemoryLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyTransactionsCommand extends Command
{
    protected $signature = 'legacy:sync-transactions
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--only=missing : missing | all}
                            {--memory=512M : memory_limit proses (dataset legacy butuh >128M)}';

    protected $description = 'Selaraskan tabel transactions dengan legacy transactions (signature: tgl + kode_debet + kode_kredit + nominal + keterangan)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        // Dataset legacy (~79.000 transaksi + indeks signature) tidak muat di
        // memory_limit default 128M. Naikkan runtime bila perlu -- hanya
        // menaikkan, tidak menyentuh php.ini global.
        $this->line(MemoryLimit::describe(MemoryLimit::ensure((string) $this->option('memory'))));

        $biz = (int) $this->option('business');
        $only = (string) $this->option('only');

        $this->info('=== Selaraskan transactions dengan legacy ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: transaksi akan ditambah.');
        $this->info("business_id={$biz} | only={$only}");
        $this->line('');

        $N = DB::connection();

        $this->info('Mengumpulkan legacy transactions...');
        // Peta RINGKAS legacy: signature → daftar legacy_id (bukan 79.000 baris penuh).
        $src = LegacyTransactionSource::collect($biz);
        $legacySig = $src['signatureIds'];
        $this->line('  legacy biz'.$biz.' : ' . $src['total'] . ' transaksi (akun terpetakan)');
        $this->line('  DB baru            : ' . $N->table('transactions')->count());
        $this->line('');

        // Indeks signature DB baru: signature → daftar id (peta ringkas, bukan objek penuh).
        $newIdx = [];
        foreach ($N->table('transactions')->select(['id', 'tgl_transaksi', 'account_debet', 'account_kredit', 'saldo', 'keterangan_transaksi'])->cursor() as $r) {
            $newIdx[LegacyTransactionSource::signatureOfNew($r)][] = (int) $r->id;
        }
        $this->line('  signature unik DB baru : ' . count($newIdx));

        // Cocokkan lewat PETA SIGNATURE saja (tanpa menahan baris penuh).
        // Tiap signature: berapa banyak id legacy vs berapa banyak baris DB baru
        // → yang cocok = min(...), sisanya "belum ada". Jumlah ini IDENTIK dengan
        // pencocokan baris satu per satu (legacy di-shift selama kuota DB baru ada).
        $matched = 0;
        $sisaPerSig = [];
        foreach ($legacySig as $sig => $ids) {
            $want = count($ids);
            $have = count($newIdx[$sig] ?? []);
            $take = min($want, $have);
            $matched += $take;
            if ($want > $take) {
                $sisaPerSig[$sig] = $want - $take; // baris legacy yang belum ada di DB baru
            }
            // sisa id baris DB baru yang belum terpakai legacy tetap di $newIdx → orphan
            $newIdx[$sig] = array_slice($newIdx[$sig] ?? [], $take);
        }
        $this->line('  sudah ada (cocok)     : ' . $matched);

        // Ambil baris legacy yang BELUM ada lewat stream kedua (urutan tetap
        // ORDER BY id), dengan kuota per signature agar tidak double-hitung.
        $missing = [];
        if ($sisaPerSig) {
            foreach (LegacyTransactionSource::stream($biz) as $row) {
                $sig = LegacyTransactionSource::signature($row);
                if (($sisaPerSig[$sig] ?? 0) > 0) {
                    $sisaPerSig[$sig]--;
                    $missing[] = $row;
                }
            }
        }
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
            $this->line('  peak memory : ' . MemoryLimit::formatBytes(memory_get_peak_usage(true)));

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

            // Tipe yang wajib punya relasi tagihan/pembayaran.
            // 'commission' & 'writeoff' tidak butuh reverence_id (hanya penerima komisi),
            // jadi ikut tercakup di sini lewat daftar tipe.
            $needRef = in_array($r['reverence_type'], ['monthly_bill', 'overdue_bill', 'bill_payment', 'payment'], true);
            if ($needRef && $reverenceId === null && $r['usage_id'] > 0) {
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
        $this->line('  peak memory : ' . MemoryLimit::formatBytes(memory_get_peak_usage(true)));

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
