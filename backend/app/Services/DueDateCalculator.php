<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Satu-satunya sumber perhitungan jatuh tempo tagihan.
 *
 * Dipakai oleh BillingService (generate tagihan normal/baru) dan
 * MeterResetService (tagihan meter lama) supaya aturannya tidak berbeda.
 */
class DueDateCalculator
{
    /**
     * Jatuh tempo = tanggal `$day` pada BULAN BERIKUTNYA periode pemakaian,
     * dibatasi jumlah hari bulan tujuan.
     *
     * Contoh: pemakaian Oktober 2026, toleransi 27 -> 2026-11-27.
     */
    public function compute(int $year, int $month, int $day = 27): string
    {
        $carbon = Carbon::create($year, $month, 1);
        $next = $carbon->copy()->addMonthNoOverflow();

        $maxDay = $next->daysInMonth;
        $day = min(max(1, $day), $maxDay);

        return $next->setDay($day)->toDateString();
    }
}
