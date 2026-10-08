<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran untuk meter_readings & monthly_bills.
 *
 * Legacy `usages` = 1 baris per pelanggan per bulan. Dari situ app baru
 * butuh dua tabel:
 *
 *   meter_readings : meter_value      = usages.akhir   (angka meter kumulatif)
 *                    recorded_at       = usages.tgl_pemakaian
 *
 *   monthly_bills  : meter_reading_start = usages.awal
 *                    meter_reading_end   = usages.akhir
 *                    usage_m3            = akhir - awal
 *                    usage_charge        = usages.nominal
 *                    abodemen            = installation_packages.monthly_abodemen
 *                    penalty_amount      = late_penalty bila tagihan 2 bulan lalu unpaid
 *                    total_amount        = nominal + abodemen + penalty
 *                    status              = PAID→paid, UNPAID/NON→unpaid
 *                    due_date            = usages.tgl_akhir
 *
 * Relasi Dict嘎gu: usage → customers memakai `installations.kode_instalasi`
 * sebagai kunci, bukan nama pelanggan (nama legacy banyak kembar).
 */
class LegacyUsageSource
{
    /**
     * @return array{
     *   readings: array<string, array>,
     *   total: int,
     *   noCustomer: int,
     *   noDate: int,
     *   duplicates: array
     * }
     */
    public static function collect(int $legacyBusinessId = 5): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        // customers DB baru dikunci lewat kode instalasi (customer_code),
        // yang nilainya persis installations.kode_instalasi di legacy.
        $customerByCode = [];
        foreach ($N->table('customers')->get(['id', 'customer_code']) as $c) {
            $code = trim((string) $c->customer_code);
            if ($code !== '') {
                $customerByCode[$code] = (int) $c->id;
            }
        }

        $codeByInst = $L->table('installations')
            ->where('business_id', $legacyBusinessId)
            ->pluck('kode_instalasi', 'id')->all();

        $usages = $L->table('usages')
            ->where('business_id', $legacyBusinessId)
            ->orderBy('id')
            ->get();

        $readings = [];
        $noCustomer = 0;
        $noDate = 0;
        $duplicates = [];

        foreach ($usages as $u) {
            $code = trim((string) ($codeByInst[(int) $u->id_instalasi] ?? ''));
            $customerId = $code === '' ? null : ($customerByCode[$code] ?? null);
            if ($customerId === null) {
                $noCustomer++;
                continue;
            }

            $date = self::parseDate($u->tgl_pemakaian ?? null);
            if ($date === null) {
                $noDate++;
                continue;
            }

            $start = self::toInt($u->awal ?? null);
            $end = self::toInt($u->akhir ?? null);
            $key = $customerId.'|'.$date->year.'|'.$date->month;

            $row = [
                'customer_id' => $customerId,
                'year' => (int) $date->year,
                'month' => (int) $date->month,
                'reading_start' => $start,
                'reading_end' => $end,
                'usage_m3' => max(0, $end - $start),
                'usage_charge' => self::toFloat($u->nominal ?? null),
                'status' => self::statusMap($u->status ?? null),
                'due_date' => self::parseDate($u->tgl_akhir ?? null)?->toDateString(),
                'recorded_at' => $date->toDateTimeString(),
                'usage_id' => (int) $u->id,
                'cater' => (int) ($u->cater ?? 0),
            ];

            if (isset($readings[$key])) {
                // Legacy punya beberapa baris untuk pelanggan+bulan yang sama
                // (mis. salah input meter = 0). Ambil yang angka meternya
                // masuk akal: bukan 0, dan รนงค่า >= nilai sebelumnya.
                $prev = $readings[$key];
                $duplicates[] = ['key' => $key, 'prev_usage_id' => $prev['usage_id'], 'usage_id' => $row['usage_id']];
                if ($prev['reading_end'] > 0 && $prev['reading_end'] >= $row['reading_end']) {
                    continue; // pertahankan yang lama
                }
            }
            $readings[$key] = $row;
        }

        return [
            'readings' => $readings,
            'total' => count($readings),
            'noCustomer' => $noCustomer,
            'noDate' => $noDate,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Hitung ulang seluruh monthly_bills dari sumber legacy + aturan
     * app baru (abodemen dari paket, denda bila tunggakan 2 bulan).
     *
     * @param  array<string,array>  $readings  hasil collect()
     * @return array<string,array> kunci "customer|year|month"
     */
    public static function buildBills(array $readings, bool $applyPenalty = true): array
    {
        $N = DB::connection();

        $packageByCustomer = [];
        foreach ($N->table('customers')
            ->join('installation_tickets', 'installation_tickets.id', '=', 'customers.ticket_id')
            ->get(['customers.id', 'installation_tickets.package_id']) as $r) {
            $packageByCustomer[(int) $r->id] = (int) $r->package_id;
        }

        $package = [];
        foreach ($N->table('installation_packages')->get(['id', 'monthly_abodemen', 'late_penalty']) as $p) {
            $package[(int) $p->id] = [
                'abodemen' => (float) $p->monthly_abodemen,
                'penalty' => (float) $p->late_penalty,
            ];
        }

        // Kelompokkan per pelanggan, urut kronologis — dipakai untuk deteksi denda.
        $byCustomer = [];
        foreach ($readings as $key => $r) {
            $byCustomer[$r['customer_id']][$key] = $r;
        }
        foreach ($byCustomer as &$rows) {
            uasort($rows, fn ($a, $b) => [$a['year'] * 12 + $a['month']] <=> [$b['year'] * 12 + $b['month']]);
        }
        unset($rows);

        $bills = [];
        foreach ($readings as $key => $r) {
            $packageId = $packageByCustomer[$r['customer_id']] ?? 1;
            $abodemen = $package[$packageId]['abodemen'] ?? 0.0;

            $penalty = 0.0;
            if ($applyPenalty) {
                $prevKey = $r['customer_id'].'|'.self::shiftPeriod($r['year'], $r['month'], -2);
                $prev = $byCustomer[$r['customer_id']][$prevKey] ?? null;
                if ($prev !== null && $prev['status'] === 'unpaid') {
                    $penalty = $package[$packageId]['penalty'] ?? 0.0;
                }
            }

            $bills[$key] = [
                'customer_id' => $r['customer_id'],
                'billing_period_year' => $r['year'],
                'billing_period_month' => $r['month'],
                'meter_reading_start' => $r['reading_start'],
                'meter_reading_end' => $r['reading_end'],
                'usage_m3' => $r['usage_m3'],
                'usage_charge' => $r['usage_charge'],
                'abodemen' => $abodemen,
                'penalty_amount' => $penalty,
                'total_amount' => $r['usage_charge'] + $abodemen + $penalty,
                'status' => $r['status'],
                'due_date' => $r['due_date'],
            ];
        }

        return $bills;
    }

    private static function shiftPeriod(int $year, int $month, int $delta): string
    {
        $index = $year * 12 + ($month - 1) + $delta;
        return (int) floor($index / 12).'|'.(($index % 12) + 1);
    }

    public static function statusMap($status): string
    {
        return match (strtoupper(trim((string) $status))) {
            'PAID' => 'paid',
            'UNPAID', 'NON' => 'unpaid',
            default => 'unpaid',
        };
    }

    private static function parseDate($value): ?\Illuminate\Support\Carbon
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '' || str_starts_with($v, '0000-00-00')) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function toInt($v): int
    {
        if ($v === null) {
            return 0;
        }
        $n = (int) filter_var((string) $v, FILTER_SANITIZE_NUMBER_INT);

        return $n < 0 ? 0 : $n;
    }

    private static function toFloat($v): float
    {
        if ($v === null) {
            return 0.0;
        }
        $f = (float) preg_replace('/[^\d.\-]/', '', (string) $v);

        return $f < 0 ? 0.0 : $f;
    }
}