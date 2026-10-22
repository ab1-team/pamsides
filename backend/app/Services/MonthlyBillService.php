<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\WaterTariffBlock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonthlyBillService
{
    public function generate()
    {
        $now = now();
        $bulan = $now->month;
        $tahun = $now->year;

        $settings = Setting::first();
        $batasTagihan = $settings?->batas_tagihan ?? 27;

        // CEGAH GENERATE DOBEL
        $exists = MonthlyBill::where('billing_period_month', $bulan)
            ->where('billing_period_year', $tahun)
            ->exists();

        if ($exists) {
            return [
                'status' => false,
                'message' => 'Tagihan bulan ini sudah pernah digenerate',
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
                'message' => 'Masih ada pelanggan yang belum dicatat meternya',
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

            if (! $last) {
                continue;
            }

            $usage = $reading->meter_value - $last->meter_value;

            if ($usage < 0) {
                continue;
            }

            $customer = Customer::with('ticket.package')->find($reading->customer_id);

            if (! $customer || ! $customer->ticket || ! $customer->ticket->package) {
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

            // Tagihan Rp 0 bisa langsung berstatus `paid` — tapi hanya kalau nolnya
            // benar-benar bisa dibenarkan, yaitu pelanggan tidak mengonsumsi air.
            //
            // Kalau `$usage > 0` tapi total tetap 0, itu berarti paketnya belum
            // punya blok tarif (atau semua price_per_m3-nya 0). Membiarkan tagihan
            // seperti itu jadi `paid` berarti pelanggan yang benar-benar pakai
            // air tidak ditagih sama sekali dan paket yang rusak ikut tersembunyi.
            // Jadi kasus itu sengaja dibiarkan `unpaid` supaya kelihatan di
            // daftar tagihan dan bisa dibetulkan admin.
            //
            // Sama seperti `BillingService::generateForCustomer` yang dipakai saat
            // input pemakaian satu per satu.
            $isNihilLegitimate = $usage <= 0 && $total <= 0;

            if ($usage > 0 && $total <= 0) {
                Log::warning('Tagihan Rp 0 padahal pelanggan memakai air — konfigurasi paket kemungkinan bermasalah', [
                    'customer_id' => $reading->customer_id,
                    'package_id' => $package->id,
                    'usage_m3' => $usage,
                    'total_amount' => $total,
                    'period' => $tahun.'-'.$bulan,
                ]);
            }

            $status = $isNihilLegitimate ? 'paid' : 'unpaid';

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
                'status' => $status,
                'due_date' => $this->computeDueDate($tahun, $bulan, $batasTagihan),
            ]);

            $count++;
        }

        return [
            'status' => true,
            'message' => 'Tagihan berhasil digenerate',
            'total_generated' => $count,
        ];
    }

    private function calculateTariff($usage, $package_id)
    {
        $blocks = WaterTariffBlock::where('package_id', $package_id)
            ->orderBy('usage_min_m3')
            ->get();

        if ($blocks->isEmpty()) {
            return 0;
        }

        $remaining = $usage;
        $total = 0;

        foreach ($blocks as $index => $block) {
            if ($remaining <= 0) {
                break;
            }

            $min = (int) $block->usage_min_m3;

            // Panjang rentang blok, dengan aturan yang SAMA dengan
            // `BillingService::calculateProgressiveCharge()`:
            //
            //   - Blok tak terbatas (`usage_max_m3` null) → pakai sisa.
            //   - Blok berurutan tanpa jeda (mis. `1-10` lalu `11-...`) →
            //     batas atas inklusif, panjang = `max - min + 1`
            //     (1..10 = 10 m3).
            //   - Blok berjarak / tumpang tindih (mis. `0-10` lalu `10-20`) →
            //     panjang = `max - min`.
            //
            // Kenapa dibedakan: blok tarif di database ditulis sebagai rentang
            // inklusif (`1-10 m3` = 10 m3). Kalau selalu pakai `max - min`,
            // 1 m3 terakhir terlewat dan masuk blok berikutnya yang lebih
            // mahal — tagihan jadi 500 lebih mahal per pelanggan.
            if ($block->usage_max_m3 === null) {
                $range = $remaining;
            } else {
                $max = (int) $block->usage_max_m3;
                $nextMin = $blocks[$index + 1]->usage_min_m3 ?? null;

                $range = ($nextMin !== null && (int) $nextMin === $max + 1)
                    ? $max - $min + 1
                    : max(0, $max - $min);
            }

            $used = min($remaining, $range);
            $total += $used * (float) $block->price_per_m3;
            $remaining -= $used;
        }

        return $total;
    }

    private function calculatePenalty($customer_id, $bulan, $tahun, $package)
    {
        $date = Carbon::create($tahun, $bulan, 1)->subMonths(1);

        $bill = MonthlyBill::where('customer_id', $customer_id)
            ->where('billing_period_month', $date->month)
            ->where('billing_period_year', $date->year)
            ->first();

        if ($bill && $bill->status == 'unpaid') {
            return $package->late_penalty;
        }

        return 0;
    }

    public function computeDueDate(int $year, int $month, int $day = 27): string
    {
        $carbon = Carbon::create($year, $month, 1);
        $maxDay = $carbon->daysInMonth;
        $day = min($day, $maxDay);

        return $carbon->setDay($day)->toDateString();
    }
}
