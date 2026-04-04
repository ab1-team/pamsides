<?php

namespace App\Services;

use App\Models\MonthlyBill;
use App\Models\MeterReading;
use App\Models\Customer;
use App\Models\WaterTariffBlock;
use Carbon\Carbon;

class MonthlyBillService
{
    public function generate()
    {
        $now = now();
        $bulan = $now->month;
        $tahun = $now->year;

        // CEGAH GENERATE DOBEL
        $exists = MonthlyBill::where('billing_period_month', $bulan)
            ->where('billing_period_year', $tahun)
            ->exists();

        if ($exists) {
            return [
                'status' => false,
                'message' => 'Tagihan bulan ini sudah pernah digenerate'
            ];
        }

        // VALIDASI METER
        $totalCustomer = Customer::count();

        $totalReading = MeterReading::where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->count();

        if ($totalReading < $totalCustomer) {
            return [
                'status' => false,
                'message' => 'Masih ada pelanggan yang belum dicatat meternya'
            ];
        }

        $readings = MeterReading::where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->get();

        $count = 0;

        foreach ($readings as $reading) {

            $last = MeterReading::where('customer_id', $reading->customer_id)
                ->where(function ($q) use ($bulan, $tahun) {
                    $q->where('reading_year', '<', $tahun)
                        ->orWhere(function ($q2) use ($bulan, $tahun) {
                            $q2->where('reading_year', $tahun)
                                ->where('reading_month', '<', $bulan);
                        });
                })
                ->orderByDesc('reading_year')
                ->orderByDesc('reading_month')
                ->first();

            if (!$last) continue;

            $usage = $reading->meter_value - $last->meter_value;

            if ($usage < 0) continue;

            $customer = Customer::with('ticket.package')->find($reading->customer_id);

            if (!$customer || !$customer->ticket || !$customer->ticket->package) {
                continue;
            }

            $package = $customer->ticket->package;

            $usage_charge = $this->calculateTariff($usage, $package->id);

            $abodemen = $package->monthly_abodemen;

            $penalty = $this->calculatePenalty(
                $reading->customer_id,
                $bulan,
                $tahun,
                $package
            );

            $total = $usage_charge + $abodemen + $penalty;

            MonthlyBill::create([
                'customer_id' => $reading->customer_id,
                'billing_period_month' => $bulan,
                'billing_period_year' => $tahun,
                'meter_reading_start' => $last->meter_value,
                'meter_reading_end' => $reading->meter_value,
                'usage_m3' => $usage,
                'usage_charge' => $usage_charge,
                'abodemen' => $abodemen,
                'penalty_amount' => $penalty,
                'total_amount' => $total,
                'status' => 'unpaid',
                'due_date' => now()->addDays(20)
            ]);

            $count++;
        }

        return [
            'status' => true,
            'message' => 'Tagihan berhasil digenerate',
            'total_generated' => $count
        ];
    }

    private function calculateTariff($usage, $package_id)
    {
        $blocks = WaterTariffBlock::where('package_id', $package_id)
            ->orderBy('usage_min_m3')
            ->get();

        $remaining = $usage;
        $total = 0;

        foreach ($blocks as $block) {
            if ($remaining <= 0) break;

            $min = $block->usage_min_m3;
            $max = $block->usage_max_m3 ?? $remaining;

            $range = $max - $min + 1;
            $used = min($remaining, $range);

            $total += $used * $block->price_per_m3;
            $remaining -= $used;
        }

        return $total;
    }

    private function calculatePenalty($customer_id, $bulan, $tahun, $package)
    {
        $date = Carbon::create($tahun, $bulan, 1)->subMonths(2);

        $bill = MonthlyBill::where('customer_id', $customer_id)
            ->where('billing_period_month', $date->month)
            ->where('billing_period_year', $date->year)
            ->first();

        if ($bill && $bill->status == 'unpaid') {
            return $package->late_penalty;
        }

        return 0;
    }
}
