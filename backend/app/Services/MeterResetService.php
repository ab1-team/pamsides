<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MeterReset;
use App\Models\MonthlyBill;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Logika reset / ganti meteran.
 *
 * Alur bisnis:
 *   - Meter lama "ditutup" pada suatu periode, angka akhirnya dicatat.
 *   - Meter baru mulai dari `new_meter_start` (default 0).
 *   - Tagihan meter lama HANYA biaya pemakaian (tanpa abodemen/denda).
 *   - Tagihan meter baru adalah tagihan normal (pemakaian + abodemen + denda).
 */
class MeterResetService
{
    public function __construct(private TariffCalculator $tariffCalculator) {}

    public function resetForPeriod(int $customerId, int $year, int $month): ?MeterReset
    {
        return MeterReset::where('customer_id', $customerId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();
    }

    public function latestResetBefore(int $customerId, int $year, int $month): ?MeterReset
    {
        return MeterReset::where('customer_id', $customerId)
            ->where(function ($q) use ($year, $month) {
                $q->where('period_year', '<', $year)
                    ->orWhere(function ($q2) use ($year, $month) {
                        $q2->where('period_year', $year)
                            ->where('period_month', '<', $month);
                    });
            })
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->first();
    }

    /**
     * Nilai meter terakhir yang diketahui SEBELUM periode tertentu.
     *
     * Sumber bisa tagihan (meter_reading_end) atau pencatatan meter
     * (meter_value), dipilih yang periodenya paling baru. Urutan dalam
     * periode yang sama memprioritaskan segmen `baru` (meter yang sedang
     * berjalan) di atas `normal` dan `lama`.
     */
    public function previousMeterValue(int $customerId, int $year, int $month, ?float $initial = null): float
    {
        $maxPeriod = $year * 100 + $month;

        $bill = MonthlyBill::where('customer_id', $customerId)
            ->whereRaw('(billing_period_year * 100 + billing_period_month) < ?', [$maxPeriod])
            ->orderByDesc('billing_period_year')
            ->orderByDesc('billing_period_month')
            ->orderByRaw("FIELD(meter_segment, 'baru', 'normal', 'lama')")
            ->first();

        $reading = MeterReading::where('customer_id', $customerId)
            ->whereRaw('(reading_year * 100 + reading_month) < ?', [$maxPeriod])
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        $billPeriod = $bill
            ? ((int) $bill->billing_period_year * 100 + (int) $bill->billing_period_month)
            : null;
        $readingPeriod = $reading
            ? ((int) $reading->reading_year * 100 + (int) $reading->reading_month)
            : null;

        if ($bill && ($readingPeriod === null || $billPeriod >= $readingPeriod)) {
            return (float) $bill->meter_reading_end;
        }

        if ($reading) {
            return (float) $reading->meter_value;
        }

        return (float) ($initial ?? 0);
    }

    /**
     * Meter awal yang dipakai saat membuat tagihan / memvalidasi input meter.
     *
     * - Ada reset di periode ini  -> `new_meter_start` (tagihan meter baru).
     * - Ada reset di periode lalu dan meter baru BELUM pernah dibaca
     *   -> `new_meter_start` (baseline meter baru).
     * - Selain itu -> nilai meter terakhir sebelum periode (alur normal).
     */
    public function startMeterForPeriod(Customer $customer, int $year, int $month): float
    {
        $sameReset = $this->resetForPeriod($customer->id, $year, $month);
        if ($sameReset) {
            return (float) $sameReset->new_meter_start;
        }

        $prevReset = $this->latestResetBefore($customer->id, $year, $month);

        if ($prevReset) {
            $resetReading = MeterReading::where('customer_id', $customer->id)
                ->where('reading_year', $prevReset->period_year)
                ->where('reading_month', $prevReset->period_month)
                ->first();

            // Bacaan pada periode reset dianggap "meter baru" bila angkanya
            // berbeda dari angka akhir meter lama.
            $hasNewReading = $resetReading
                && (float) $resetReading->meter_value !== (float) $prevReset->old_meter_final;

            if (! $hasNewReading) {
                $period = $year * 100 + $month;
                $resetPeriod = (int) $prevReset->period_year * 100 + (int) $prevReset->period_month;

                $laterReading = MeterReading::where('customer_id', $customer->id)
                    ->whereRaw('(reading_year * 100 + reading_month) > ?', [$resetPeriod])
                    ->whereRaw('(reading_year * 100 + reading_month) < ?', [$period])
                    ->exists();

                if (! $laterReading) {
                    return (float) $prevReset->new_meter_start;
                }
            }
        }

        return $this->previousMeterValue(
            $customer->id,
            $year,
            $month,
            $customer->initial_meter_reading
        );
    }

    /**
     * Proses reset meter.
     *
     * @return array{reset:MeterReset,bill:MonthlyBill}
     */
    public function performReset(
        Customer $customer,
        int $year,
        int $month,
        ?float $oldMeterFinal,
        float $newMeterStart,
        string $reason,
        ?int $userId,
    ): array {
        return DB::transaction(function () use ($customer, $year, $month, $oldMeterFinal, $newMeterStart, $reason, $userId) {
            $existingReset = $this->resetForPeriod($customer->id, $year, $month);
            if ($existingReset) {
                throw new \RuntimeException('Meter pelanggan ini sudah pernah direset pada periode tersebut.');
            }

            $package = $customer->ticket?->package;
            if (! $package) {
                throw new \RuntimeException('Pelanggan ini belum memiliki paket aktif sehingga tidak bisa direset.');
            }

            $reading = MeterReading::where('customer_id', $customer->id)
                ->where('reading_year', $year)
                ->where('reading_month', $month)
                ->first();

            // Kalau meter akhir periode ini sudah tercatat, pakai angka itu
            // (tidak perlu input ulang). Kalau belum, pakai input admin.
            $oldFinal = $reading
                ? (float) $reading->meter_value
                : (float) ($oldMeterFinal ?? 0);

            $previousMeter = $this->previousMeterValue(
                $customer->id,
                $year,
                $month,
                (float) $customer->initial_meter_reading
            );

            if ($oldFinal < $previousMeter) {
                throw new \RuntimeException(
                    'Meter akhir meter lama tidak boleh lebih kecil dari meter sebelumnya ('.rtrim(rtrim(number_format($previousMeter, 2, '.', ''), '0'), '.').' m³).'
                );
            }

            $reset = MeterReset::create([
                'customer_id' => $customer->id,
                'period_year' => $year,
                'period_month' => $month,
                'previous_meter_value' => $previousMeter,
                'old_meter_final' => $oldFinal,
                'new_meter_start' => $newMeterStart,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            $usage = max(0, $oldFinal - $previousMeter);
            $usageCharge = round($this->tariffCalculator->charge($package, $usage));

            // Tagihan meter lama = biaya pemakaian saja. Abodemen & denda 0
            // karena meter sudah dihentikan (tidak dobel dengan tagihan baru).
            $status = $usageCharge <= 0 ? 'paid' : 'unpaid';

            $settings = Setting::first();
            $toleransi = (int) ($settings?->toleransi_tunggakan ?? 27);
            if ($toleransi < 1) {
                $toleransi = 27;
            }
            $dueDate = $this->computeDueDate($year, $month, $toleransi);

            $bill = MonthlyBill::where('customer_id', $customer->id)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->where('meter_segment', '!=', 'lama')
                ->first();

            $payload = [
                'meter_reset_id' => $reset->id,
                'meter_segment' => 'lama',
                'meter_reading_start' => $previousMeter,
                'meter_reading_end' => $oldFinal,
                'usage_m3' => $usage,
                'usage_charge' => $usageCharge,
                'abodemen' => 0,
                'penalty_amount' => 0,
                'total_amount' => $usageCharge,
                'status' => $status,
                'due_date' => $dueDate,
            ];

            if ($bill) {
                $bill->update($payload);
            } else {
                $bill = MonthlyBill::create(array_merge($payload, [
                    'customer_id' => $customer->id,
                    'billing_period_year' => $year,
                    'billing_period_month' => $month,
                ]));
            }

            // `meter_readings` menyimpan angka akhir meter lama sebagai
            // penutup periode. Saat meter baru dicatat, baris ini akan
            // diperbarui menjadi angka meter baru (lihat MeterReadingController).
            if ($reading) {
                $reading->update(['meter_value' => $oldFinal]);
            } else {
                $recordedAt = Carbon::create($year, $month, 1)->setTimeFrom(now());

                MeterReading::create([
                    'customer_id' => $customer->id,
                    'recorded_by' => $userId ?? $customer->ticket?->user_id,
                    'reading_year' => $year,
                    'reading_month' => $month,
                    'meter_value' => $oldFinal,
                    'photo_url' => null,
                    'recorded_at' => $recordedAt,
                ]);
            }

            return ['reset' => $reset, 'bill' => $bill];
        });
    }

    private function computeDueDate(int $year, int $month, int $day = 27): string
    {
        $carbon = Carbon::create($year, $month, 1);
        $next = $carbon->copy()->addMonthNoOverflow();

        $maxDay = $next->daysInMonth;
        $day = min(max(1, $day), $maxDay);

        return $next->setDay($day)->toDateString();
    }
}
