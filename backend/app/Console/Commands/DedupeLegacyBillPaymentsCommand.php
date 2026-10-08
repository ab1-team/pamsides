<?php

namespace App\Console\Commands;

use App\Services\LegacyPaymentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeLegacyBillPaymentsCommand extends Command
{
    protected $signature = 'legacy:dedupe-bill-payments
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan penghapusan}
                            {--business=5 : business_id legacy yang jadi acuan}';

    protected $description = 'Hapus baris bill_payments ganda pada (bill_id, tanggal) — sisanya dari import lama yang payment-nya menempel ke tagihan lain';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');

        $this->info('=== Bersihkan bill_payments ganda ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: baris akan dihapus.');
        $this->line('');

        $N = DB::connection();

        $src = LegacyPaymentSource::collect($biz);
        $payments = $src['payments'];

        // Kelompokkan baris DB baru per (bill_id, tanggal)
        $rows = [];
        foreach ($N->table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
            $rows[$bp->bill_id . '|' . substr((string) $bp->paid_at, 0, 10)][] = $bp;
        }

        $toDelete = [];
        $kept = 0;
        $detail = [];

        foreach ($rows as $key => $group) {
            if (count($group) < 2) {
                continue;
            }
            $expect = $payments[$key] ?? null;
            if ($expect === null) {
                $detail[] = "bill={$key} legacy tidak ada, hapus semua (" . count($group) . ' baris)';
                foreach ($group as $g) {
                    $toDelete[] = (int) $g->id;
                }
                continue;
            }

            // Baris yang nominalnya cocok legacy = rightful. Sisanya hapus.
            $matchId = null;
            foreach ($group as $g) {
                if (abs((float) $g->amount_paid - (float) $expect['amount_paid']) < 0.005) {
                    $matchId = (int) $g->id;
                    break;
                }
            }
            if ($matchId === null) {
                // Tidak ada yang cocok → ambil yang pertama, sisanya hapus.
                $matchId = (int) $group[0]->id;
                $detail[] = "bill={$key} tidak ada yg cocok legacy (Rp" . number_format((float) $expect['amount_paid']) . '), pertahankan id ' . $matchId;
            }
            foreach ($group as $g) {
                if ((int) $g->id === $matchId) {
                    continue;
                }
                $toDelete[] = (int) $g->id;
                $detail[] = "bill={$key} hapus id={$g->id} Rp" . number_format((float) $g->amount_paid) . ' (legacy Rp' . number_format((float) $expect['amount_paid']) . ')';
            }
            $kept++;
        }

        $this->line('  baris bill_payments sekarang : ' . $N->table('bill_payments')->count());
        $this->line('  pasangan (bill,tanggal) ganda : ' . count(array_filter($rows, fn ($g) => count($g) > 1)));
        $this->line('  akan dihapus                   : ' . count($toDelete));
        $this->line('');
        $this->info('Rincian (20 pertama):');
        foreach (array_slice($detail, 0, 20) as $d) {
            $this->line('   ' . $d);
        }
        if (count($detail) > 20) {
            $this->line('   ... +' . (count($detail) - 20) . ' lainnya');
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        if ($toDelete) {
            foreach (array_chunk($toDelete, 400) as $chunk) {
                $N->table('bill_payments')->whereIn('id', $chunk)->delete();
            }
        }
        $this->line('');
        $this->info('Selesai. Baris dihapus: ' . count($toDelete));
        $this->line('bill_payments sekarang: ' . $N->table('bill_payments')->count());

        return self::SUCCESS;
    }
}