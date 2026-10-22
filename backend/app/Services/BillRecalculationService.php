<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hitung ulang tagihan (monthly_bill) setelah angka meter diubah.
 *
 * Kenapa perlu service terpisah
 * -----------------------------
 * Mengubah "pemakaian" selalu berarti tiga angka berubah sekaligus:
 * `usage_m3`, `usage_charge`, dan `total_amount`. Kalau hanya `usage_m3`
 * yang di-update, tagihan yang tampil di daftar tidak sinkron dengan
 * tarif paket — persis bug yang dulu muncul: pemakaian berubah, tagihan
 * tetap.
 *
 * Angka komponen lain TIDAK dihitung ulang dari nol:
 *   - `abodemen` berasal dari paket (tidak bergantung meter).
 *   - `penalty_amount` dibekukan saat tagihan dibuat karena relating ke
 *     periode N-1, bukan meter bulan ini. Menghitung ulang akan mengubah
 *     tagihan yang sudah dipublikasikan ke pelanggan.
 */
class BillRecalculationService
{
    public function __construct(private BillingService $billingService) {}

    /**
     * Hitung ulang tagihan untuk satu pelanggan pada satu periode.
     *
     * Kalau tagihan untuk periode itu belum ada, tagihan BARU dibuat
     * dengan angka yang sama seperti generator (komponen penagihan yang
     * sama).
     *
     * @return array{changed:bool, created:bool, before:array, after:array, bill:MonthlyBill}
     */
    public function recalculateForCustomer(Customer $customer, int $year, int $month): array
    {
        $package = $customer->ticket?->package;

        if (! $package) {
            throw new \RuntimeException('Pelanggan ini belum memiliki paket aktif sehingga tidak bisa ditagih.');
        }

        $bill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->first();

        $startMeter = $this->resolveStartMeter($customer, $year, $month);

        $currentReading = MeterReading::where('customer_id', $customer->id)
            ->where('reading_year', $year)
            ->where('reading_month', $month)
            ->first();

        // Kalau tagihan sudah ada, meter akhir diambil dari pencatatan
        // yang SUDAH diperbarui. Kalau belum, pakai nilai tagihan lama.
        $endMeter = $currentReading
            ? (int) $currentReading->meter_value
            : (int) ($bill->meter_reading_end ?? $startMeter);

        $usageM3 = max(0, $endMeter - $startMeter);
        $usageCharge = $this->billingService->calculateProgressiveCharge($package, $usageM3);

        $abodemen = round((float) $package->monthly_abodemen);

        // Denda beku: kalau tagihan sudah ada, nilai lamanya yang dipakai.
        // Kalau tagihan baru, dendanya dihitung dari tagihan bulan
        // sebelumnya (sama seperti generator).
        $penalty = $bill
            ? round((float) $bill->penalty_amount)
            : round($this->billingService->calculatePenalty($customer, $year, $month));

        $totalAmount = round($usageCharge + $abodemen + $penalty);

        $after = [
            'meter_reading_start' => $startMeter,
            'meter_reading_end' => $endMeter,
            'usage_m3' => $usageM3,
            'usage_charge' => $usageCharge,
            'abodemen' => $abodemen,
            'penalty_amount' => $penalty,
            'total_amount' => $totalAmount,
        ];

        // ── Tagihan belum ada → buat baru ──
        if (! $bill) {
            $status = ($usageM3 <= 0 && $totalAmount <= 0) ? 'paid' : 'unpaid';

            if ($usageM3 > 0 && $totalAmount <= 0) {
                Log::warning('Tagihan Rp 0 padahal pelanggan memakai air — konfigurasi paket kemungkinan bermasalah', [
                    'customer_id' => $customer->id,
                    'usage_m3' => $usageM3,
                    'total_amount' => $totalAmount,
                    'period' => $year.'-'.$month,
                ]);
            }

            $settings = \App\Models\Setting::first();
            $batasTagihan = (int) ($settings?->batas_tagihan ?? 27);

            $bill = MonthlyBill::create(array_merge($after, [
                'customer_id' => $customer->id,
                'billing_period_year' => $year,
                'billing_period_month' => $month,
                'status' => $status,
                'due_date' => $this->billingService->computeDueDate($year, $month, $batasTagihan),
            ]));

            return [
                'changed' => true,
                'created' => true,
                'before' => [],
                'after' => $after,
                'bill' => $bill,
            ];
        }

        // ── Tagihan sudah ada → hitung ulang ──
        $before = [
            'meter_reading_start' => (int) $bill->meter_reading_start,
            'meter_reading_end' => (int) $bill->meter_reading_end,
            'usage_m3' => (int) $bill->usage_m3,
            'usage_charge' => (float) $bill->usage_charge,
            'abodemen' => (float) $bill->abodemen,
            'penalty_amount' => (float) $bill->penalty_amount,
            'total_amount' => (float) $bill->total_amount,
        ];

        $changed = $before != $after;

        if ($changed) {
            $bill->update($after);
        }

        return [
            'changed' => $changed,
            'created' => false,
            'before' => $before,
            'after' => $after,
            'bill' => $bill,
        ];
    }

    /**
     * Meter awal periode ini = meter akhir periode sebelumnya, atau meter
     * awal pelanggan kalau belum ada pencatatan sebelumnya.
     *
     * Aturan ini sama dengan BillingService::generateForCustomer().
     */
    public function resolveStartMeter(Customer $customer, int $year, int $month): int
    {
        $lastMonth = $month === 1 ? 12 : $month - 1;
        $lastMonthYear = $month === 1 ? $year - 1 : $year;

        $previousReading = MeterReading::where('customer_id', $customer->id)
            ->where('reading_year', $lastMonthYear)
            ->where('reading_month', $lastMonth)
            ->first();

        return $previousReading
            ? (int) $previousReading->meter_value
            : (int) $customer->initial_meter_reading;
    }

    /**
     * Periode berikutnya dari (tahun, bulan).
     *
     * @return array{0:int,1:int} [tahun, bulan]
     */
    public function nextPeriod(int $year, int $month): array
    {
        return $month === 12
            ? [$year + 1, 1]
            : [$year, $month + 1];
    }

    /**
     * Samakan jurnal piutang tunggakan dengan nominal tagihan yang baru.
     *
     * Kenapa ini wajib
     * -----------------
     * Command `billing:generate-overdue-transactions` membuat jurnal
     * piutang (debit 1.1.03.01, kredit 4.1.01.02 / 4.1.01.03) dengan
     * nominal = tagihan saat itu. Kalau tagihan lalu diperbaiki, nominal
     * jurnal piutang tidak lagi cocok dengan tagihan:
     *
     *   - tagihan naik  → piutang kurang; pelanggan bayar lebih tapi
     *                      piutang yang ditutup cuma nominal lama.
     *   - tagihan turun → piutang lebih; ada sisa piutang tak tertagih.
     *
     * Jurnal piutang TIDAK boleh di-hard-delete karena barisnya adalah
     * jejak audit, dan soft-delete pun berbahaya: command generate menghitung
     * "sudah punya jurnal" lewat global scope SoftDeletes, sehingga baris
     * yang soft-deleted tetap dianggap sudah ada dan jurnal tidak pernah
     * dibuat ulang → piutang hilang selamanya.
     *
     * Karena itu barisnya TETAP ADA, hanya `saldo`-nya yang disesuaikan.
     * Komponen yang jadi nol ditulis jadi saldo 0 (bukan dihapus), supaya
     * command generate tidak membuat baris duplikat dan supaya komponen itu
     * bisa hidup lagi kalau nomornya naik lagi.
     *
     * @return array{updated:int}
     */
    public function syncOverdueJournals(MonthlyBill $bill): array
    {
        $rows = Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->get();

        if ($rows->isEmpty()) {
            return ['updated' => 0];
        }

        // Akun kredit -> nominal baru. 4.1.01.02 = abodemen,
        // 4.1.01.03 = pemakaian. Denda (4.1.01.04) tidak pernah
        // dijurnal sebagai piutang, jadi tidak ada di sini.
        $targets = [
            '4.1.01.02' => round((float) $bill->abodemen),
            '4.1.01.03' => round((float) $bill->usage_charge),
        ];

        $updated = 0;

        foreach ($rows as $row) {
            $kredit = (string) $row->account_kredit;

            if (! array_key_exists($kredit, $targets)) {
                continue;
            }

            $newAmount = $targets[$kredit];

            if ((float) $row->saldo === (float) $newAmount) {
                continue;
            }

            // Update lewat DB::table() agar observer Transaction::updated
            // tidak menjalankan agregasi `amount` penuh sekali lagi;
            // trigger MySQL tetap yang dipakai untuk sinkronisasi
            // `amount` (pola yang sama dipakai MonthlyBillController::pay).
            DB::table('transactions')
                ->where('id', $row->id)
                ->update([
                    'saldo' => $newAmount,
                    'updated_at' => now(),
                ]);

            $updated++;
        }

        return ['updated' => $updated];
    }

    /**
     * True bila tagihan ini sudah dianggap lunas.
     *
     * Tagihan yang sudah lunas tidak boleh diubah pemakaiannya: bukti
     * fisiknya sudah jadi bagian audit dan uangnya sudah masuk kas.
     *
     * Tiga sumber dipakai, semuanya sesuai dengan yang dipakai
     * `MonthlyBillController::usage()` untuk menentukan status PAID:
     *   - flag `monthly_bills.status` = 'paid' (di-set `pay()`),
     *   - ada baris `bill_payments` (pembayaran pernah dikonfirmasi),
     *   - ada jurnal `bill_payment` di tabel `transactions`.
     */
    public function isBillPaid(MonthlyBill $bill): bool
    {
        if (strtolower((string) $bill->status) === 'paid') {
            return true;
        }

        $paymentIds = $bill->billPayments()->pluck('id');

        if ($paymentIds->isEmpty()) {
            return false;
        }

        // Pembayaran pernah dikonfirmasi.
        if ((float) $bill->billPayments()->sum('amount_paid') > 0) {
            return true;
        }

        // Fallback: jurnal pembayarannya yang jadi bukti.
        return Transaction::where('reverence_type', 'bill_payment')
            ->whereIn('reverence_id', $paymentIds)
            ->exists();
    }
}
