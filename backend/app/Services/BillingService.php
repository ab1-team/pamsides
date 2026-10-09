<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\WaterTariffBlock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function generateForCustomer(Customer $customer, int $year, int $month, ?int $batasTagihan = null): MonthlyBill
    {
        $currentReading = $customer->meterReadings()
            ->where('reading_year', $year)
            ->where('reading_month', $month)
            ->first();

        if (! $currentReading) {
            throw new \RuntimeException('Belum ada pencatatan meter untuk periode ini.');
        }

        $lastMonth = $month === 1 ? 12 : $month - 1;
        $lastMonthYear = $month === 1 ? $year - 1 : $year;

        $previousReading = $customer->meterReadings()
            ->where('reading_year', $lastMonthYear)
            ->where('reading_month', $lastMonth)
            ->first();

        $startMeter = $previousReading
            ? $previousReading->meter_value
            : $customer->initial_meter_reading;

        $endMeter = $currentReading->meter_value;
        $usageM3 = max(0, $endMeter - $startMeter);

        $usageCharge = $this->calculateProgressiveCharge(
            $customer->ticket->package,
            $usageM3
        );

        $abodemen = round($customer->ticket->package->monthly_abodemen);
        $penaltyAmount = $this->calculatePenalty($customer, $year, $month);

        $totalAmount = round($usageCharge + $abodemen + $penaltyAmount);

        if ($batasTagihan === null) {
            $settings = Setting::first();
            $batasTagihan = $settings?->batas_tagihan ?? 27;
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

        return MonthlyBill::create([
            'customer_id' => $customer->id,
            'billing_period_year' => $year,
            'billing_period_month' => $month,
            'meter_reading_start' => $startMeter,
            'meter_reading_end' => $endMeter,
            'usage_m3' => $usageM3,
            'usage_charge' => $usageCharge,
            'abodemen' => $abodemen,
            'penalty_amount' => $penaltyAmount,
            'total_amount' => $totalAmount,
            'status' => $status,
            'due_date' => $this->computeDueDate($year, $month, $batasTagihan),
        ]);
    }

    public function calculateProgressiveCharge($package, float $usageM3): float
    {
        $blocks = WaterTariffBlock::where('package_id', $package->id)
            ->orderBy('usage_min_m3')
            ->get();

        if ($blocks->isEmpty()) {
            return 0;
        }

        $remaining = $usageM3;
        $total = 0;

        foreach ($blocks as $block) {
            if ($remaining <= 0) {
                break;
            }

            $min = (int) $block->usage_min_m3;

            if ($block->usage_max_m3 !== null) {
                $max = (int) $block->usage_max_m3;
                // Range selalu max - min. min adalah batas bawah inklusif, max batas atas inklusif.
                // Contoh: min=10, max=20 → range = 10 m³ (11..20 = 10 nilai, atau 10..19 = 10 nilai).
                $range = $max - $min;
            } else {
                $range = $remaining;
            }

            $used = min($remaining, $range);
            $total += round($used * (float) $block->price_per_m3);
            $remaining -= $used;
        }

        return round($total);
    }

    public function calculatePenalty(Customer $customer, int $year, int $month): float
    {
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevMonthYear = $month === 1 ? $year - 1 : $year;

        $oldBill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $prevMonthYear)
            ->where('billing_period_month', $prevMonth)
            ->where('status', 'unpaid')
            ->first();

        if (! $oldBill) {
            return 0;
        }

        return round($customer->ticket->package->late_penalty);
    }

    public function computeDueDate(int $year, int $month, int $day = 27): string
    {
        $carbon = Carbon::create($year, $month, 1);
        $maxDay = $carbon->daysInMonth;
        $day = min($day, $maxDay);

        return $carbon->setDay($day)->toDateString();
    }

    public function calculateChargeForTesting($package, float $usageM3): float
    {
        return $this->calculateProgressiveCharge($package, $usageM3);
    }
}
