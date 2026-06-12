<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\WaterTariffBlock;
use Carbon\Carbon;

class BillingService
{
    public function generateForCustomer(Customer $customer, int $year, int $month): MonthlyBill
    {
        $currentReading = $customer->meterReadings()
            ->where('reading_year', $year)
            ->where('reading_month', $month)
            ->first();

        if (! $currentReading) {
            throw new \RuntimeException('Belum ada pencatatan meter untuk periode ini.');
        }

        $lastMonth     = $month === 1 ? 12 : $month - 1;
        $lastMonthYear = $month === 1 ? $year - 1 : $year;

        $previousReading = $customer->meterReadings()
            ->where('reading_year', $lastMonthYear)
            ->where('reading_month', $lastMonth)
            ->first();

        $startMeter = $previousReading
            ? $previousReading->meter_value
            : $customer->initial_meter_reading;

        $endMeter = $currentReading->meter_value;
        $usageM3  = max(0, $endMeter - $startMeter);

        $usageCharge = $this->calculateProgressiveCharge(
            $customer->ticket->package,
            $usageM3
        );

        $abodemen     = $customer->ticket->package->monthly_abodemen;
        $penaltyAmount = $this->calculatePenalty($customer, $year, $month);

        $totalAmount = $usageCharge + $abodemen + $penaltyAmount;

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
            'due_date'             => $this->computeDueDate($year, $month),
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
        $total     = 0;

        foreach ($blocks as $block) {
            if ($remaining <= 0) {
                break;
            }

            $min = (float) $block->usage_min_m3;
            $max = $block->usage_max_m3 !== null ? (float) $block->usage_max_m3 : PHP_FLOAT_MAX;

            $range = max(0, $max - $min);
            $used  = min($remaining, $range);

            $total += $used * (float) $block->price_per_m3;
            $remaining -= $used;
        }

        return $total;
    }

    public function calculatePenalty(Customer $customer, int $year, int $month): float
    {
        $twoMonthsAgo     = $month <= 2 ? 12 + $month - 2 : $month - 2;
        $twoMonthsAgoYear = $month <= 2 ? $year - 1 : $year;

        $oldBill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $twoMonthsAgoYear)
            ->where('billing_period_month', $twoMonthsAgo)
            ->where('status', 'unpaid')
            ->first();

        if (! $oldBill) {
            return 0;
        }

        return $customer->ticket->package->late_penalty;
    }

    public function computeDueDate(int $year, int $month): string
    {
        $next = Carbon::create($year, $month, 1)->addMonth();
        return $next->setDay(20)->toDateString();
    }

    public function calculateChargeForTesting($package, float $usageM3): float
    {
        return $this->calculateProgressiveCharge($package, $usageM3);
    }
}
