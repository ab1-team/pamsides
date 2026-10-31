<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonthlyBillService
{
    private TariffCalculator $tariffCalculator;

    public function __construct(?TariffCalculator $tariffCalculator = null)
    {
        $this->tariffCalculator = $tariffCalculator ?? new TariffCalculator;
    }

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

    /**
     * Biaya pemakaian. Memakai `TariffCalculator` yang sama dengan jalur
     * generate per pelanggan, supaya tidak ada lagi dua rumus tarif yang
     * bisa berbeda hasilnya.
     */
    private function calculateTariff($usage, $package_id)
    {
        return $this->tariffCalculator->charge($package_id, (float) $usage);
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
