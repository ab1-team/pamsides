<?php

namespace App\Support;

/**
 * Aturan bisnis bersama untuk import pembayaran tagihan legacy.
 *
 * Dipisah dari ImportLegacyBillPaymentsCommand agar bisa diuji tanpa DB dan
 * tanpa data legacy. Command ini memproses puluhan ribu event, jadi setiap
 * aturan yang dipakai lebih dari sekali dipusatkan di sini supaya tidak
 * bisa berbeda Interpretasi antar bagian alur.
 *
 * Dua aturan ini sebelumnya tertulis inline (dan tidak identik) di beberapa
 * tempat:
 *
 *   1. dedupKey()  -- Composite key yang menentukan sebuah event pembayaran
 *      dianggap sudah ada atau belum. Salah baca = insert dobel ATAU
 *      pembayaran lama ikut ter-skip.
 *
 *   2. statusFor() -- Aturan "sudah lunas atau belum" dari total pembayaran
 *      vs total tagihan. Salah baca = tagihan salah status.
 */
class BillPaymentImporter
{
    /**
     * Toleransi pembulatan rupiah (satuan sen).
     *
     * Tagihan dan pembayaran datang dari sumber berbeda dan masing-masing bisa
     * membawa pembulatan sen berbeda, jadi selisih < 1 sen dianggap lunas.
     */
    public const PAID_TOLERANCE = 0.01;

    /**
     * Composite key untuk mendeteksi pembayaran yang sudah pernah diimpor.
     *
     * Format: "{bill_id}|{YYYY-MM-DD}|{amount 2 desimal}".
     *
     * `paid_at` dinormalkan ke tanggal TANPA jam. Waktu pembayaran pada data
     * legacy tidak bermakna untuk identitas pembayaran -- yang menentukan
     * apakah event sama adalah tagihan + tanggal + nominal. Tanpa normalisasi
     * ini, event yang sama dengan `17:00:00` vs `00:00:00` akan terisi dua kali.
     */
    public static function dedupKey(int $billId, string $paidAt, float $amount): string
    {
        return $billId
            .'|'.date('Y-m-d', strtotime($paidAt))
            .'|'.number_format($amount, 2, '.', '');
    }

    /**
     * Status tagihan dari total pembayaran.
     *
     * Bandingkan dengan toleransi: pembayaran >= total - toleransi = 'paid'.
     */
    public static function statusFor(float $totalPaid, float $totalAmount): string
    {
        return $totalPaid >= $totalAmount - self::PAID_TOLERANCE
            ? 'paid'
            : 'unpaid';
    }

    /**
     * Key indeks tagihan bulanan.
     *
     * Indeks (customer_id, billing_period_year, billing_period_month) terbukti
     * unik di data -- tidak ada satu pun customer yang punya dua tagihan untuk
     * periode yang sama -- jadi key ini aman dipakai sebagai peta in-memory.
     * Kalau suatu saat data melanggar itu, import akan menabrak duplicate key
     * (yang lebih mudah ditelusuri daripada diam-diam menimpa tagihan).
     */
    public static function billIndexKey(int $customerId, int $year, int $month): string
    {
        return $customerId.'|'.$year.'|'.$month;
    }
}
