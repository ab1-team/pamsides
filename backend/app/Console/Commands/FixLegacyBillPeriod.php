<?php

namespace App\Console\Commands;

use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\WaterTariffBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki tagihan yang dibuat dengan KODE LAMA.
 *
 * Bug yang diperbaiki
 * ------------------
 * 1. `due_date` dihitung dari `batas_tagihan` di BULAN YANG SAMA dengan
 *    periode pemakaian. Jadi tagihan pemakaian Oktober (dibuat akhir
 *    Oktober) jatuh tempo 3 Oktober — LEBIH AWAL dari tanggal dibuat, jadi
 *    tagihan langsung berstatus menunggak.
 *
 *    Yang benar: tanggal `toleransi_tunggakan` di BULAN SETELAH periode
 *    pemakaian (konvensi legacy `tgl_akhir`, lihat
 *    `docs/perbedaan-apk-lama-baru.md`).
 *
 * 2. `usage_charge` dihitung dengan rumus PROGRESIF
 *    (`10 x 3.500 + sisa x 4.000`), padahal sistem sejak dulu memakai
 *    SATU BLOK (`usage x harga_blok_yang_memosisi`). Contoh pemakaian 12 m3
 *    paket Warga: progresif = 43.000, satu blok = 48.000.
 *
 * Kenapa command, bukan SQL manual
 * -------------------------------
 * Perbaikan ini perlu dijalankan di server (produksi) tanpa damaging data
 * lain. Command ini karena itu:
 *   - hanya menyentuh baris pada periode yang diminta,
 *   - selalu membuat backup ke tabel terpisah sebelum mengubah apa pun,
 *   - `--dry-run` untuk melihat rencana tanpa mengubah apa pun,
 *   - TIDAK pernah truncate / drop / delete baris.
 */
class FixLegacyBillPeriod extends Command
{
    protected $signature = 'billing:fix-legacy-bill-period
                            {--year=2026 : Tahun periode tagihan yang diperbaiki}
                            {--month=10 : Bulan periode tagihan yang diperbaiki}
                            {--dry-run : Tampilkan rencana, jangan ubah apa pun}
                            {--yes : Lewati konfirmasi interaktif}';

    protected $description = 'Perbaiki due_date + usage_charge + total_amount tagihan yang dibuat dengan kode lama.';

    public function handle()
    {
        $year = (int) $this->option('year');
        $month = (int) $this->option('month');
        $dryRun = (bool) $this->option('dry-run');

        if ($year < 2000 || $month < 1 || $month > 12) {
            $this->error('Tahun/bulan tidak valid.');

            return self::FAILURE;
        }

        $toleransi = (int) (Setting::first()?->toleransi_tunggakan ?? 27);
        $dueDate = $this->computeDueDate($year, $month, $toleransi);

        $this->info("Periode: {$year}-{$month} | toleransi_tunggakan: {$toleransi} | due_date: {$dueDate}");

        // Peta tarif: customer_id => harga per m3 (satu blok).
        // Paket diambil dari tiket pelanggan, lalu blok tarif dicari dari
        // `water_tariff_blocks` sesuai pemakaian.
        $tarif = $this->tarifMap($year, $month);

        $bills = MonthlyBill::where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->whereNull('deleted_at')
            ->get(['id', 'customer_id', 'usage_m3', 'usage_charge', 'abodemen',
                'penalty_amount', 'total_amount', 'due_date', 'status']);

        if ($bills->isEmpty()) {
            $this->info("Tidak ada tagihan pada periode {$year}-{$month}.");

            return self::SUCCESS;
        }

        $perubahan = [];
        $perubahanDue = 0;
        $perubahanCharge = 0;
        $perubahanTotal = 0;

        foreach ($bills as $bill) {
            $harga = $tarif[$bill->customer_id] ?? null;

            $chargeBaru = $bill->usage_m3 > 0 && $harga !== null
                ? round($bill->usage_m3 * $harga, 2)
                : $bill->usage_charge;

            $totalBaru = round($chargeBaru + $bill->abodemen + $bill->penalty_amount, 2);

            // `due_date` di-cast jadi objek Carbon oleh model, jadi harus
            // dibandingkan sebagai string — kalau dibandingkan langsung
            // dengan string Y-m-d, hasilnya SELALU berbeda dan command
            // thinks semua baris perlu diubah.
            $dueSekarang = $bill->due_date?->toDateString();

            $dueBerubah = $dueSekarang !== $dueDate;
            $chargeBerubah = abs($bill->usage_charge - $chargeBaru) > 0.01;
            $totalBerubah = abs($bill->total_amount - $totalBaru) > 0.01;

            if (! $dueBerubah && ! $chargeBerubah && ! $totalBerubah) {
                continue;
            }

            $perubahan[$bill->id] = [
                'usage_charge' => $chargeBaru,
                'total_amount' => $totalBaru,
                'due_date' => $dueDate,
                '_change_due' => $dueBerubah,
                '_change_charge' => $chargeBerubah,
                '_change_total' => $totalBerubah,
            ];

            $perubahanDue += $dueBerubah ? 1 : 0;
            $perubahanCharge += $chargeBerubah ? 1 : 0;
            $perubahanTotal += $totalBerubah ? 1 : 0;
        }

        $this->info(sprintf(
            'Rencana: %d baris berubah (due_date: %d, usage_charge: %d, total_amount: %d) dari %d tagihan.',
            count($perubahan),
            $perubahanDue,
            $perubahanCharge,
            $perubahanTotal,
            $bills->count()
        ));

        if (empty($perubahan)) {
            $this->info('Data sudah benar. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('--dry-run: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('yes') && ! $this->confirm('Lanjutkan koreksi data tagihan?', true)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        // Backup dulu, supaya data asli selalu bisa dikembalikan.
        $backupTable = 'monthly_bills_bak_'.strtolower($year.'_'.$month);

        DB::statement("CREATE TABLE IF NOT EXISTS `{$backupTable}` LIKE monthly_bills");
        DB::statement(
            "INSERT IGNORE INTO `{$backupTable}` SELECT * FROM monthly_bills
             WHERE deleted_at IS NULL AND billing_period_year = ? AND billing_period_month = ?",
            [$year, $month]
        );

        $this->info("Backup dibuat: {$backupTable}");

        DB::transaction(function () use ($perubahan, $year, $month) {
            foreach ($perubahan as $id => $row) {
                DB::table('monthly_bills')
                    ->where('id', $id)
                    ->where('billing_period_year', $year)
                    ->where('billing_period_month', $month)
                    ->whereNull('deleted_at')
                    ->update([
                        'usage_charge' => $row['usage_charge'],
                        'total_amount' => $row['total_amount'],
                        'due_date' => $row['due_date'],
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info(sprintf(
            'Selesai. %d baris diperbaiki. Backup: %s',
            count($perubahan),
            $backupTable
        ));

        return self::SUCCESS;
    }

    /**
     * Harga per m3 untuk tiap pelanggan pada periode ini.
     *
     * Paket diambil dari tiket pelanggan (tabel `installation_tickets`), lalu
     * blok tarif dicari dari `water_tariff_blocks` sesuai pemakaian bulan ini.
     *
     * @return array<int,float> customer_id => harga per m3
     */
    private function tarifMap(int $year, int $month): array
    {
        $rows = DB::table('monthly_bills as b')
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
            ->where('b.billing_period_year', $year)
            ->where('b.billing_period_month', $month)
            ->whereNull('b.deleted_at')
            ->get([
                'b.customer_id', 'b.usage_m3', 't.package_id',
            ]);

        $blocks = WaterTariffBlock::orderBy('usage_min_m3')->get()
            ->groupBy('package_id');

        $map = [];

        foreach ($rows as $row) {
            $harga = $this->cariBlok($blocks->get($row->package_id, collect()), (float) $row->usage_m3);

            if ($harga !== null) {
                $map[(int) $row->customer_id] = $harga;
            }
        }

        return $map;
    }

    /**
     * Blok tarif tempat pemakaian berada (`usage_min_m3` & `usage_max_m3`
     * inklusif). Kalau pemakaian jatuh di celah antar blok, pakai blok
     * berikutnya — sama seperti `TariffCalculator`.
     */
    private function cariBlok($blocks, float $usageM3): ?float
    {
        if ($blocks->isEmpty() || $usageM3 <= 0) {
            return null;
        }

        foreach ($blocks as $block) {
            $min = (float) $block->usage_min_m3;
            $max = $block->usage_max_m3 === null ? null : (float) $block->usage_max_m3;

            if ($usageM3 >= $min && ($max === null || $usageM3 <= $max)) {
                return (float) $block->price_per_m3;
            }
        }

        foreach ($blocks as $block) {
            if ((float) $block->usage_min_m3 > $usageM3) {
                return (float) $block->price_per_m3;
            }
        }

        return null;
    }

    /**
     * Tanggal `toleransi_tunggakan` di bulan setelah periode pemakaian.
     */
    private function computeDueDate(int $year, int $month, int $day): string
    {
        $next = \Carbon\Carbon::create($year, $month, 1)->addMonthNoOverflow();

        return $next->setDay(min(max(1, $day), $next->daysInMonth))->toDateString();
    }
}
