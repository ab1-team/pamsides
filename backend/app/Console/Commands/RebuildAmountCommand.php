<?php

namespace App\Console\Commands;

use App\Services\AmountRecalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildAmountCommand extends Command
{
    protected $signature = 'legacy:rebuild-amount
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--keep-closing=1 : Pertahankan baris bulan 00/13 hasil TutupBuku}';

    protected $description = 'Bangun ulang tabel amount (rekap bulanan per akun) dari transactions';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $keepClosing = (bool) $this->option('keep-closing');

        $this->info('=== Bangun ulang tabel amount dari transactions ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: amount akan ditulis ulang.');
        $this->info('Pertahankan baris TutupBuku (bulan 00/13): ' . ($keepClosing ? 'ya' : 'tidak'));
        $this->line('');

        $N = DB::connection();

        $this->info('Menghitung rekap dari transactions...');
        $computed = AmountRecalculator::compute();
        $this->line('  (akun, periode) hasil hitung : ' . count($computed));
        $this->line('  amount di DB sekarang          : ' . $N->table('amount')->count());
        $this->line('');

        $accCode = $N->table('accounts')->pluck('kode_akun', 'id')->all();

        // Bandingkan dengan DB sekarang
        $existing = [];
        foreach ($N->table('amount')->get() as $r) {
            $existing[(int) $r->id] = $r;
        }

        $inserts = 0; $updates = 0; $deletes = []; $closingKept = 0;

        foreach ($computed as $id => $row) {
            $cur = $existing[$id] ?? null;
            if ($cur === null) {
                $inserts++;
                continue;
            }
            if (abs((float) $cur->debit - $row['debit']) >= 0.005 || abs((float) $cur->kredit - $row['kredit']) >= 0.005) {
                $updates++;
            }
        }

        foreach ($existing as $id => $cur) {
            if (isset($computed[$id])) {
                continue;
            }
            $isClosing = in_array((string) $cur->bulan, ['00', '13'], true);
            if ($isClosing && $keepClosing) {
                $closingKept++;
                continue;
            }
            $deletes[] = $id;
        }

        $this->line('  perlu dibuat   : ' . $inserts);
        $this->line('  perlu diupdate : ' . $updates);
        $this->line('  perlu dihapus  : ' . count($deletes));
        if ($closingKept) {
            $this->line('  dipertahankan (TutupBuku) : ' . $closingKept);
        }
        $this->line('');

        // Ringkasan per akun
        $perAccount = [];
        foreach ($computed as $row) {
            $kode = $accCode[$row['account_id']] ?? ('id' . $row['account_id']);
            $perAccount[$kode]['debit'] = ($perAccount[$kode]['debit'] ?? 0) + $row['debit'];
            $perAccount[$kode]['kredit'] = ($perAccount[$kode]['kredit'] ?? 0) + $row['kredit'];
            $perAccount[$kode]['n'] = ($perAccount[$kode]['n'] ?? 0) + 1;
        }
        ksort($perAccount);
        $this->info('  rekap per akun:');
        foreach ($perAccount as $kode => $v) {
            echo sprintf("    %-12s baris=%-4d debit=%-16s kredit=%s\n",
                $kode, $v['n'], number_format((float) $v['debit'], 2, '.', ''), number_format((float) $v['kredit'], 2, '.', ''));
        }

        $totD = array_sum(array_column($perAccount, 'debit'));
        $totK = array_sum(array_column($perAccount, 'kredit'));
        $this->line('');
        echo sprintf("    TOTAL         debit= %-16s kredit=%s\n", number_format((float) $totD, 2, '.', ''), number_format((float) $totK, 2, '.', ''));
        echo '    balance? ' . (abs($totD - $totK) < 0.01 ? 'YA' : 'TIDAK (selisih Rp ' . number_format($totD - $totK, 2, '.', '') . ')') . PHP_EOL;
        $this->line('');

        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan --force untuk menulis.');

            return self::SUCCESS;
        }

        $this->error('Menulis tabel amount...');
        $N->transaction(function () use ($N, $computed, $deletes) {
            if ($deletes) {
                foreach (array_chunk($deletes, 500) as $chunk) {
                    $N->table('amount')->whereIn('id', $chunk)->delete();
                }
            }
            $batch = [];
            foreach ($computed as $row) {
                $batch[] = $row;
                if (count($batch) >= 400) {
                    $N->table('amount')->upsert($batch, ['id'], ['account_id', 'bulan', 'tahun', 'debit', 'kredit']);
                    $batch = [];
                }
            }
            if ($batch) {
                $N->table('amount')->upsert($batch, ['id'], ['account_id', 'bulan', 'tahun', 'debit', 'kredit']);
            }
        });

        $this->line('');
        $this->info('Selesai.');
        $this->line('  amount baris sekarang : ' . $N->table('amount')->count());

        return self::SUCCESS;
    }
}