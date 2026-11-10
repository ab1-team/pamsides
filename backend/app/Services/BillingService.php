<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    private TariffCalculator $tariffCalculator;

    private MeterResetService $meterResetService;

    public function __construct(
        ?TariffCalculator $tariffCalculator = null,
        ?MeterResetService $meterResetService = null,
    ) {
        $this->tariffCalculator = $tariffCalculator ?? new TariffCalculator;
        $this->meterResetService = $meterResetService ?? new MeterResetService($this->tariffCalculator);
    }

    public function generateForCustomer(Customer $customer, int $year, int $month, ?int $toleransiTunggakan = null): MonthlyBill
    {
        return DB::transaction(function () use ($customer, $year, $month, $toleransiTunggakan) {
            // Kunci baris pelanggan selama generate. Dua request bersamaan
            // untuk pelanggan yang sama akan berjalan berurutan sehingga
            // cek-lalu-insert di bawah tidak bisa menghasilkan tagihan dobel
            // per segmen (skema sengaja mengizinkan `lama` + `baru`).
            Customer::whereKey($customer->id)->lockForUpdate()->first();

            $currentReading = $customer->meterReadings()
                ->where('reading_year', $year)
                ->where('reading_month', $month)
                ->first();

            if (! $currentReading) {
                throw new \RuntimeException('Belum ada pencatatan meter untuk periode ini.');
            }

            // Meter awal periode. Kalau periode ini punya reset, baseline-nya
            // adalah `new_meter_start` (meter baru), bukan meter akhir bulan lalu.
            $startMeter = $this->meterResetService->startMeterForPeriod($customer, $year, $month);

            // Segmen tagihan: `baru` kalau periode ini ada reset, selain itu `normal`.
            $reset = $this->meterResetService->resetForPeriod($customer->id, $year, $month);
            $segment = $reset ? 'baru' : 'normal';

            $endMeter = $currentReading->meter_value;
            $usageM3 = max(0, $endMeter - $startMeter);

            $usageCharge = $this->calculateProgressiveCharge(
                $customer->ticket->package,
                $usageM3
            );

            $abodemen = round($customer->ticket->package->monthly_abodemen);
            $penaltyAmount = $this->calculatePenalty($customer, $year, $month);

            $totalAmount = round($usageCharge + $abodemen + $penaltyAmount);

            if ($toleransiTunggakan === null) {
                $settings = Setting::first();
                $toleransiTunggakan = $settings?->toleransi_tunggakan ?? 27;
            }

            // Tagihan Rp 0 bisa langsung berstatus `paid` — TAPI hanya kalau nolnya
            // benar-benar bisa dibenarkan, yaitu pelanggan tidak mengonsumsi air.
            //
            // Dua penyebab total_amount == 0 sama sekali berbeda sifatnya:
            //
            // 1. usage_m3 == 0 → pelanggan memang tidak pakai air, jadi tidak ada
            //    apa pun untuk dibayar. Nol ini sah. Kalau dibiarkan `unpaid`, dia
            //    akan memenuhi lonceng navbar sebagai "tagihan belum bayar", ikut
            //    dihitung menunggak di dashboard, dan menjatuhkan tiket jadi
            //    `suspended` — semuanya untuk sesuatu yang tidak ada uangnya.
            //
            // 2. usage_m3 > 0 tapi total tetap 0 → berarti `calculateProgressiveCharge`
            //    mengembalikan 0 padahal air mengalir. Penyebabnya hampir selalu
            //    paket belum punya blok tarif (`water_tariff_blocks` kosong) atau
            //    semua `price_per_m3`-nya 0. Paket dan blok tarif memang dibuat
            //    terpisah lewat endpoint berbeda, jadi kondisi ini bisa tersimpan.
            //    Auto-paid di sini berarti pelanggan yang benar-benar pakai air
            //    dapat air gratis, dan paket rusak ikut tersembunyi selamanya.
            //
            // Jadi cases (2) sengaja dibiarkan `unpaid`: tagihannya Rp 0 tapi
            // statusnya menggantung di daftar tagihan supaya terlihat dan bisa
            // dibetulkan admin, bukan hilang diam-diam.
            $isNihilLegitimate = $usageM3 <= 0 && $totalAmount <= 0;

            if ($usageM3 > 0 && $totalAmount <= 0) {
                Log::warning('Tagihan Rp 0 padahal pelanggan memakai air — konfigurasi paket kemungkinan bermasalah', [
                    'customer_id' => $customer->id,
                    'package_id' => $customer->ticket->package->id ?? null,
                    'usage_m3' => $usageM3,
                    'total_amount' => $totalAmount,
                    'period' => $year.'-'.$month,
                ]);
            }

            $status = $isNihilLegitimate ? 'paid' : 'unpaid';

            $payload = [
                'meter_reset_id' => $reset?->id,
                'meter_segment' => $segment,
                'meter_reading_start' => $startMeter,
                'meter_reading_end' => $endMeter,
                'usage_m3' => $usageM3,
                'usage_charge' => $usageCharge,
                'abodemen' => $abodemen,
                'penalty_amount' => $penaltyAmount,
                'total_amount' => $totalAmount,
                'status' => $status,
                'due_date' => $this->computeDueDate($year, $month, $toleransiTunggakan),
            ];

            // Idempoten per segmen: periode yang sama boleh punya satu tagihan
            // `lama` dan satu tagihan `baru`, tetapi tidak boleh dobel per segmen.
            $existing = MonthlyBill::where('customer_id', $customer->id)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->where('meter_segment', $segment)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->update($payload);

                return $existing;
            }

            return MonthlyBill::create(array_merge($payload, [
                'customer_id' => $customer->id,
                'billing_period_year' => $year,
                'billing_period_month' => $month,
            ]));
        });
    }

    /**
     * Biaya pemakaian untuk satu pelanggan.
     *
     * Seluruh pemakaian dibebankan dengan harga SATU blok tarif — blok
     * tempat pemakaian itu berada. Lihat `TariffCalculator` untuk aturan
     * lengkap dan alasannya.
     *
     * Nama method lama (`calculateProgressiveCharge`) dipertahankan supaya
     * pemanggilnya tidak perlu berubah; perhitungannya sekarang memakai
     * kalkulator tunggal yang sama dengan jalur generate tagihan massal.
     */
    public function calculateProgressiveCharge($package, float $usageM3): float
    {
        return $this->tariffCalculator->charge($package, $usageM3);
    }

    public function calculatePenalty(Customer $customer, int $year, int $month): float
    {
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevMonthYear = $month === 1 ? $year - 1 : $year;

        $hasUnpaid = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $prevMonthYear)
            ->where('billing_period_month', $prevMonth)
            ->where('status', 'unpaid')
            ->exists();

        if (! $hasUnpaid) {
            return 0;
        }

        return round($customer->ticket->package->late_penalty);
    }

    /**
     * Jatuh tempo tagihan.
     *
     * Aturan: tanggal `toleransi_tunggakan` pada BULAN BERIKUTNYA periode
     * pemakaian.
     *
     *   pemakaian Oktober -> due_date 27 November
     *
     * PENTING: `due_date` ini adalah TANGGAL GENERATE PIUTANG, bukan batas
     * akhir bayar. Batas akhir bayar = `due_date` - 1 hari (26 November),
     * dan itulah yang ditampilkan frontend pada kolom "TGL AKHIR BAYAR"
     * (dikurangi 1 hari di `admin/tagihan/pemakaianAir.vue`).
     *
     * Jadi dipakai untuk dua hal yangBEDA:
     *   - `GenerateOverdueTransactions` memilih tagihan dengan
     *     `due_date < threshold` untuk dicatat sebagai piutang tunggakan.
     *   - Frontend menampilkan `due_date - 1` sebagai batas akhir bayar.
     *
     * Kenapa bulan berikutnya, bukan bulan yang sama
     * ---------------------------------------------
     * Konvensi ini sama dengan APK lama (`docs/perbedaan-apk-lama-baru.md`):
     *
     *   ```php
     *   // UsageController.php:303-320 (APK lama)
     *   $tglAkhir = date('Y-m', strtotime('+1 month', $tglPakai))
     *            . '-' . str_pad($data['toleransi'], 2, '0');
     * ```
     *
     * Pemakaian dibebankan ke bulan berikutnya. Data hasil impor legacy juga
     * memakai aturan ini, sehingga 30.786 dari 30.823 tagihan punya `due_date`
     * di bulan setelah periodenya.
     *
     * Dulu method ini memakai `batas_tagihan` (nilai 3 di Setting) di bulan
     * YANG SAMA dengan periode pemakaian. Akibatnya:
     *
     *   - tagihan pemakaian Oktober jatuh tempo 3 Oktober, yaitu LEBIH
     *     AWAL dari tanggal tagihan dibuat (26/31 Oktober). Tagihan langsung
     *     berstatus menunggak, memicu lonceng notifikasi, dan langsung
     *     dicatat sebagai piutang tunggakan.
     *   - salah sumber nilai: yang dipakai untuk batas akhir bayar adalah
     *     `toleransi_tunggakan` (1-28), bukan `batas_tagihan`.     *
     * Catatan overflow: pakai `addMonthNoOverflow()` dari tanggal 1, jadi
     * tidak pernah melompat ke bulan berikutnya (tidak seperti `addMonth()`
     * yang pada 31 Jan jadi 3 Mar).
     */
    public function computeDueDate(int $year, int $month, int $day = 27): string
    {
        return (new DueDateCalculator)->compute($year, $month, $day);
    }

    public function calculateChargeForTesting($package, float $usageM3): float
    {
        return $this->calculateProgressiveCharge($package, $usageM3);
    }
}
