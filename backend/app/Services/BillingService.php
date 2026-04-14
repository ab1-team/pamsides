<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyBill;

class BillingService
{
    public function generateForCustomer(Customer $customer, int $year, int $month): MonthlyBill
    {
        // Ambil meter reading bulan ini
        $currentReading = $customer->meterReadings()
            ->where('reading_year', $year)
            ->where('reading_month', $month)
            ->first();

        // Ambil meter reading bulan lalu
        $lastMonth        = $month === 1 ? 12 : $month - 1;
        $lastMonthYear    = $month === 1 ? $year - 1 : $year;
        $previousReading  = $customer->meterReadings()
            ->where('reading_year', $lastMonthYear)
            ->where('reading_month', $lastMonth)
            ->first();

        // Jika bulan lalu tidak ada, pakai initial_meter_reading
        $startMeter = $previousReading
            ? $previousReading->meter_value
            : $customer->initial_meter_reading;

        $endMeter  = $currentReading->meter_value;
        $usageM3   = $endMeter - $startMeter;

        // Kalkulasi biaya pemakaian progresif
        $usageCharge = $this->calculateProgressiveCharge(
            $customer->ticket->package,
            $usageM3
        );

        // Ambil abodemen dari paket
        $abodemen = $customer->ticket->package->monthly_abodemen;

        // Cek denda dari tagihan 2 bulan lalu
        $penaltyAmount = $this->calculatePenalty($customer, $year, $month);

        // Total tagihan
        $totalAmount = $usageCharge + $abodemen + $penaltyAmount;

        // Buat tagihan
        return MonthlyBill::create([
            'customer_id'          => $customer->id,
            'billing_period_year'  => $year,
            'billing_period_month' => $month,
            'meter_reading_start'  => $startMeter,
            'meter_reading_end'    => $endMeter,
            'usage_m3'             => $usageM3,
            'usage_charge'         => $usageCharge,
            'abodemen'             => $abodemen,
            'penalty_amount'       => $penaltyAmount,
            'total_amount'         => $totalAmount,
            'status'               => 'unpaid',
            'due_date'             => now()->setYear($year)->setMonth($month)->endOfMonth(),
        ]);
    }

    private function calculateProgressiveCharge($package, float $usageM3): float
    {
        $blocks = $package->waterTariffBlocks()->orderBy('usage_min_m3')->get();

        foreach ($blocks as $block) {
            $blockMin = (float) $block->usage_min_m3;
            $blockMax = $block->usage_max_m3 !== null ? (float) $block->usage_max_m3 : PHP_FLOAT_MAX;

            if ($usageM3 >= $blockMin && $usageM3 <= $blockMax) {
                return $usageM3 * (float) $block->price_per_m3;
            }
        }

        $lastBlock = $blocks->last();
        return $usageM3 * (float) $lastBlock->price_per_m3;
    }

    private function calculatePenalty(Customer $customer, int $year, int $month): float
    {
        // Cek tagihan 2 bulan lalu
        $twoMonthsAgo      = $month <= 2 ? 12 + $month - 2 : $month - 2;
        $twoMonthsAgoYear  = $month <= 2 ? $year - 1 : $year;

        $oldBill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $twoMonthsAgoYear)
            ->where('billing_period_month', $twoMonthsAgo)
            ->where('status', 'unpaid')
            ->first();

        if (! $oldBill) return 0;

        return $customer->ticket->package->late_penalty;
    }

    public function calculateChargeForTesting($package, float $usageM3): float
    {
        return $this->calculateProgressiveCharge($package, $usageM3);
    }
}
