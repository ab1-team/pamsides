<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran untuk bill_payments & payments dari legacy `transactions`.
 *
 * Legacy memakai satu tabel `transactions` untuk semua jurnal, dan
 * membedakan jenisnya lewat awalan `keterangan`:
 *
 *   "Pendapatan ..."  → pengakuan pendapatan (bukan pembayaran)
 *   "Bayar ..."       → pelangganactually membayar
 *   "Biaya instalasi %" → biaya pemasangan (installation_tickets)
 *   "Utang ..." / "Piutang Denda ..." → memo, bukan pembayaran
 *
 * Transaksi pembayaran selalu punya `usage_id > 0` yang menunjuk baris
 * `usages` (= tagihan bulan itu), jadi relasinya bisa dipastikan tanpa
 * mengandalkan nama pelanggan.
 *
 * Satu tagihan bisa dibayar berkali-kali (utang air dibayar sebagian),
 * jadi `bill_payments` disimpan per event (usage_id + tanggal).
 */
class LegacyPaymentSource
{
    /**
     * Zona waktu aplikasi legacy (server SIM-SPPG / 103.177.95.92).
     *
     * Legacy menyimpan `tgl_transaksi` sebagai DATE polos di zona ini (WIB).
     * Saat data ditimpakan ke DB baru (app timezone = UTC), tanggal itu
     * tersimpan sebagai tengah-malam WIB yang dikonversi ke UTC -- jadi
     * 2025-03-26 (WIB) menjadi `2025-03-25 17:00:00` (UTC).
     *
     * Semua pencocokan pembayaran yang membandingkan `bill_payments.paid_at`
     * (UTC di DB baru) dengan tanggal event legacy HARUS menormalkan ke zona
     * ini lebih dulu, kalau tidak tanggalnya bergeser satu hari dan matcher
     * menganggap SEMUA pembayaran legacy tidak punya pasangan.
     */
    public const LEGACY_TZ = 'Asia/Jakarta';

    /**
     * Tanggal lokal-legacy (Y-m-d) dari `bill_payments.paid_at` DB baru.
     *
     * DB baru menyimpan waktu dalam UTC; legacy memakai WIB. Helper ini
     * memulihkan tanggal asli legacy dari timestamp UTC tersebut sehingga
     * kunci `bill_id|YYYY-MM-DD` di sisi legacy dan sisi DB baru sebanding.
     */
    public static function legacyDateOfPaidAt(?string $paidAt): string
    {
        $v = trim((string) $paidAt);
        if ($v === '' || str_starts_with($v, '0000-00-00')) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($v, 'UTC')
                ->setTimezone(self::LEGACY_TZ)
                ->toDateString();
        } catch (\Throwable) {
            return substr($v, 0, 10);
        }
    }

    /**
     * Klasifikasi `keterangan` legacy.
     *
     * Di legacy, pembayaran pelanggan dicatat dengan awalan "Pendapatan"
     * (pengakuan pendapatan = pelanggan dianggap lunas), sedangkan "Bayar"
     * hanya dipakai untuk pelunasan piutang secara manual. Keduanya
     * menunjukkan tagihan lunas, jadi keduanya dipakai.
     *
     * "Utang", "Piutang Denda", dan "Biaya instalasi" BUKAN pembayaran tagihan.
     */
    private static function isPayment(?string $keterangan): bool
    {
        return str_starts_with((string) $keterangan, 'Pendapatan')
            || str_starts_with((string) $keterangan, 'Bayar');
    }

    /**
     * @return array{
     *   payments: array<string,array>,
     *   billTotals: array<int,float>,
     *   unmatched: array,
     *   noUsage: int
     * }
     */
    public static function collect(int $legacyBusinessId = 5, bool $includeRevenue = true): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        // usage_id → monthly_bills.id (relasi pasti lewat kode instalasi)
        $usageToBill = self::usageToBillMap($legacyBusinessId);

        // events: "bill_id|YYYY-MM-DD"
        $events = [];
        $billTotals = [];
        $noUsage = 0;
        $unmatched = [];

        // Streaming via cursor(): ~79.000 baris legacy tidak lagi dimuat
        // sekaligus ke array objek. Urutan & isi baris identik dengan ->get(),
        // jadi hasil akumulasi (events, billTotals, noUsage) tidak berubah.
        $rows = $L->table('transactions')
            ->where('business_id', $legacyBusinessId)
            ->where('usage_id', '>', 0)
            ->orderBy('usage_id')
            ->orderBy('tgl_transaksi')
            ->orderBy('id')
            ->select(['id', 'usage_id', 'tgl_transaksi', 'total', 'user_id', 'keterangan', 'installation_id'])
            ->cursor();

        foreach ($rows as $t) {
            if (! self::isPayment($t->keterangan)) {
                continue;
            }

            $usageId = (int) $t->usage_id;
            $billId = $usageToBill[$usageId] ?? null;
            if ($billId === null) {
                $noUsage++;
                continue;
            }

            $date = self::parseDate($t->tgl_transaksi);
            if ($date === null) {
                $noUsage++;
                continue;
            }

            $amount = (float) $t->total;
            $key = $billId.'|'.$date->toDateString();

            if (! isset($events[$key])) {
                $events[$key] = [
                    'bill_id' => $billId,
                    'usage_id' => $usageId,
                    'paid_at' => $date->toDateTimeString(),
                    'amount_paid' => 0.0,
                    'legacy_user_id' => 0,
                    'parts' => 0,
                ];
            }
            $events[$key]['amount_paid'] += $amount;
            $events[$key]['parts']++;
            // Simpan user_id dari baris pertama yang punya user valid.
            if ((int) $t->user_id > 0 && $events[$key]['legacy_user_id'] === 0) {
                $events[$key]['legacy_user_id'] = (int) $t->user_id;
            }

            $billTotals[$billId] = ($billTotals[$billId] ?? 0.0) + $amount;
        }

        ksort($events);

        return [
            'payments' => $events,
            'billTotals' => $billTotals,
            'noUsage' => $noUsage,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Biaya instalasi dari legacy → payments (type=installation_fee).
     *
     * @return array<int,array> ticket_id => data pembayaran
     */
    public static function collectInstallationFees(int $legacyBusinessId = 5): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        $codeByInst = $L->table('installations')
            ->where('business_id', $legacyBusinessId)
            ->pluck('kode_instalasi', 'id')->all();
        $ticketByCode = $N->table('customers')->pluck('ticket_id', 'customer_code')->all();

        $result = [];
        $unmatched = [];

        $rows = $L->table('transactions')
            ->where('business_id', $legacyBusinessId)
            ->where('keterangan', 'LIKE', 'Biaya instalasi%')
            ->orderBy('id')
            ->select(['id', 'installation_id', 'tgl_transaksi', 'total', 'user_id'])
            ->cursor();

        foreach ($rows as $t) {
            $instId = (int) $t->installation_id;
            $code = trim((string) ($codeByInst[$instId] ?? ''));
            $ticketId = $code === '' ? null : ($ticketByCode[$code] ?? null);
            if ($ticketId === null) {
                $unmatched[] = ['trx_id' => (int) $t->id, 'inst_id' => $instId, 'code' => $code];
                continue;
            }
            $date = self::parseDate($t->tgl_transaksi);
            $result[(int) $ticketId] = [
                'ticket_id' => (int) $ticketId,
                'amount' => (float) $t->total,
                'paid_at' => $date?->toDateTimeString(),
                'legacy_user_id' => (int) $t->user_id,
            ];
        }

        return ['fees' => $result, 'unmatched' => $unmatched];
    }

    /** @return array<int,int> legacy usages.id → monthly_bills.id */
    public static function usageToBillMap(int $legacyBusinessId = 5): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

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

        $map = [];
        $usageRows = $L->table('usages')
            ->where('business_id', $legacyBusinessId)
            ->select(['id', 'id_instalasi', 'tgl_pemakaian'])
            ->cursor();
        foreach ($usageRows as $u) {
            $code = trim((string) ($codeByInst[(int) $u->id_instalasi] ?? ''));
            $customerId = $code === '' ? null : ($customerByCode[$code] ?? null);
            if ($customerId === null) {
                continue;
            }
            $date = self::parseDate($u->tgl_pemakaian);
            if ($date === null) {
                continue;
            }
            $billId = $N->table('monthly_bills')
                ->where('customer_id', $customerId)
                ->where('billing_period_year', $date->year)
                ->where('billing_period_month', $date->month)
                ->value('id');
            if ($billId !== null) {
                $map[(int) $u->id] = (int) $billId;
            }
        }

        return $map;
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
}