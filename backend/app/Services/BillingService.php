<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\WaterTariffBlock;
use Carbon\Carbon;

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
            'status' => 'unpaid',
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
        $blockIndex = 0;

        foreach ($blocks as $block) {
            if ($remaining <= 0) {
                break;
            }

            $min = (int) $block->usage_min_m3;

            if ($block->usage_max_m3 !== null) {
                $max = (int) $block->usage_max_m3;
                // Blok pertama (min=0): range = max - min
                // Blok selanjutnya: range = max - min + 1 (karena min = prev_max + 1)
                $range = $blockIndex === 0 ? $max - $min : $max - $min + 1;
            } else {
                $range = $remaining;
            }

            $used = min($remaining, $range);
            $total += round($used * (float) $block->price_per_m3);
            $remaining -= $used;
            $blockIndex++;
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
