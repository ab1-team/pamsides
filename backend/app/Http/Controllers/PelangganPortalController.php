<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelangganPortalController extends Controller
{
    /**
     * Nama pelanggan dengan urutan prioritas yang SAMA dengan sisi admin.
     *
     * Admin membaca `customer.user.name` dulu lalu jatuh ke
     * `customer.ticket.applicant_name` (lihat `CustomerController`,
     * `BillingController`, dan `MonthlyBillController`). Portal dulu memakai
     * urutan terbalik (`applicant_name` dulu), jadi begitu nama akun dan
     * nama pemohon berbeda — misalnya akun dibuat ulang dengan ejaan lain —
     * pelanggan melihat nama yang berbeda dari yang tampil di admin untuk
     * tagihan yang sama. Ini yang harus dicegah: nama yang melekat pada
     * tagihan harus selalu identik dengan yang dibaca admin.
     */
    private function customerName(Customer $customer): string
    {
        return $customer->user?->name
            ?: $customer->ticket?->applicant_name
            ?: 'Pelanggan Pamsimas';
    }

    /**
     * Bentuk payload tagihan yang sama dengan yang dibaca admin.
     *
     * Semua nominal diambil dari baris `monthly_bills` milik pelanggan itu
     * sendiri, bukan dihitung ulang dari paket/strata harga saat runtime:
     * nominal yang tersimpan itulah yang dicetak di invoice admin, jadi
     * inilah yang harus tampil untuk pelanggan.
     */
    private function billPayload(MonthlyBill $bill): array
    {
        return [
            'id' => $bill->id,
            'customer_id' => $bill->customer_id,
            'billing_period_month' => $bill->billing_period_month,
            'billing_period_year' => $bill->billing_period_year,
            'meter_reading_start' => $bill->meter_reading_start,
            'meter_reading_end' => $bill->meter_reading_end,
            'usage_m3' => $bill->usage_m3,
            'usage_charge' => $bill->usage_charge,
            'abodemen' => $bill->abodemen,
            'penalty_amount' => $bill->penalty_amount,
            'total_amount' => $bill->total_amount,
            'status' => $bill->status,
            'due_date' => $bill->due_date?->toDateString(),
        ];
    }

    public function dashboard()
    {
        $user = Auth::user();

        // Find the customer associated with the user
        $customer = Customer::with(['ticket.package', 'user'])
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan.'
            ], 404);
        }

        // Get latest unpaid bill
        $latestBill = MonthlyBill::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->first();

        // If no unpaid bill, get the latest paid one
        if (!$latestBill) {
            $latestBill = MonthlyBill::where('customer_id', $customer->id)
                ->orderBy('billing_period_year', 'desc')
                ->orderBy('billing_period_month', 'desc')
                ->first();
        }

        // Ringkasan tunggakan.
        //
        // Definisi "tunggakan" harus sama dengan sisi admin supaya pelanggan
        // dan petugas melihat angka yang sama untuk tagihan yang sama:
        //   belum lunas  = `monthly_bills.status = 'unpaid'`
        //   lewat jatuh tempo = `due_date` sudah lewat dari hari ini
        //
        // Admin memakai definisi yang sama persis di
        // `DashboardController::stats()` (`overdue_bills`) dan
        // `MonthlyBillController::report()` (`total_belum_dibayar`), jadi
        // hitungan di sini tidak boleh mengulang definisinya sendiri.
        $allUnpaid = MonthlyBill::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->get();

        $today = Carbon::now()->toDateString();

        $overdueCount = 0;
        $maxOverdueDays = 0;
        $overdueAmount = 0.0;
        $totalUnpaidAmount = 0.0;

        foreach ($allUnpaid as $b) {
            $totalUnpaidAmount += (float) $b->total_amount;

            $due = $b->due_date?->toDateString();

            // Tanpa due_date tidak bisa dihitung usianya, jadi jangan ikut
            // dihitung sebagai tunggakan — sama seperti filter admin yang
            // memakai `whereDate('due_date', '<=', today)` (null tersingkir).
            if ($due === null || $due > $today) {
                // Belum lewat jatuh tempo: masih dalam tempo, bukan tunggakan.
                continue;
            }

            $overdueCount++;
            $overdueAmount += (float) $b->total_amount;

            // Selisih hari jatuh tempo → hari ini.
            $days = Carbon::parse($due)->diffInDays(Carbon::parse($today));
            if ($days > $maxOverdueDays) {
                $maxOverdueDays = (int) $days;
            }
        }

        $arrears = [
            'unpaid_count' => $allUnpaid->count(),
            'total_unpaid_amount' => round($totalUnpaidAmount, 2),
            'overdue_count' => $overdueCount,
            'overdue_amount' => round($overdueAmount, 2),
            'max_overdue_days' => $maxOverdueDays,
        ];

        // Build 12-month usage series from meter_readings (fallback) or monthly_bills.
        // Pakai meter_readings dulu karena lebih real-time (termasuk bulan berjalan yg belum jadi tagihan).
        $now = Carbon::now();
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $ref = $now->copy()->subMonthsNoOverflow($i);
            $year = (int) $ref->year;
            $month = (int) $ref->month;
            $periodKey = sprintf('%04d-%02d', $year, $month);

            $reading = MeterReading::where('customer_id', $customer->id)
                ->where('reading_year', $year)
                ->where('reading_month', $month)
                ->first();

            $bill = MonthlyBill::where('customer_id', $customer->id)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->first();

            $usageM3 = $reading?->meter_value !== null
                ? (int) ($bill?->usage_m3 ?? $this->inferUsageFromReadings($customer->id, $year, $month))
                : (int) ($bill?->usage_m3 ?? 0);

            $series[] = [
                'period_key' => $periodKey,
                'year' => $year,
                'month' => $month,
                'usage_m3' => round($usageM3),
                'bill_amount' => $bill ? (float) $bill->total_amount : 0,
                'bill_status' => $bill?->status,
                'has_reading' => $reading !== null,
                'has_bill' => $bill !== null,
                'is_current' => $ref->isSameMonth($now),
            ];
        }

        $usageValues = array_column($series, 'usage_m3');
        $totalUsage = array_sum($usageValues);
        $nonZero = array_values(array_filter($usageValues, fn ($v) => $v > 0));
        $avgUsage = count($nonZero) > 0 ? array_sum($nonZero) / count($nonZero) : 0;
        $maxUsage = count($nonZero) > 0 ? max($nonZero) : 0;
        $minUsage = count($nonZero) > 0 ? min($nonZero) : 0;

        // Trend: bandingkan 3 bulan terakhir vs 3 bulan sebelumnya
        $recent = array_slice($usageValues, -3);
        $previous = array_slice($usageValues, -6, 3);
        $recentAvg = count($recent) > 0 ? array_sum($recent) / count($recent) : 0;
        $previousAvg = count($previous) > 0 ? array_sum($previous) / count($previous) : 0;
        $trendDelta = $recentAvg - $previousAvg;
        $trendDirection = $trendDelta > 0.01 ? 'up' : ($trendDelta < -0.01 ? 'down' : 'flat');
        $trendPercent = $previousAvg > 0 ? round(($trendDelta / $previousAvg) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $this->customerName($customer),
                    'customer_code' => $customer->customer_code,
                    'address' => $customer->ticket?->address ?? '-',
                    'package_name' => $customer->ticket?->package?->name ?? '-',
                ],
                'latest_bill' => $latestBill ? $this->billPayload($latestBill) : null,
                // `arrears` berisi tunggakan aktual. Nilai `balance` lama
                // selalu 0 padahal ada tagihan unpaid — jadi tampil sebagai
                // "Rp 0" padahal pelanggan punya utang. Sekarang dipakai
                // jumlah seluruh tagihan yang belum lunas.
                'arrears' => $arrears,
                'balance' => $arrears['total_unpaid_amount'],
                'usage_history' => array_reverse(array_slice($series, -5)), // 5 terakhir, urut naik (kronologis)
                'distribution' => [
                    'series' => $series,
                    'months_count' => 12,
                    'summary' => [
                        'total_m3' => round($totalUsage),
                        'avg_m3' => round($avgUsage),
                        'max_m3' => round($maxUsage),
                        'min_m3' => round($minUsage),
                        'recorded_months' => count($nonZero),
                        'trend_direction' => $trendDirection,
                        'trend_percent' => $trendPercent,
                        'recent_avg_m3' => round($recentAvg),
                        'previous_avg_m3' => round($previousAvg),
                    ],
                ],
            ]
        ]);
    }

    /**
     * Hitung pemakaian dari selisih 2 meter reading terakhir jika monthly_bills belum tersedia.
     */
    private function inferUsageFromReadings(int $customerId, int $year, int $month): float
    {
        $current = MeterReading::where('customer_id', $customerId)
            ->where('reading_year', $year)
            ->where('reading_month', $month)
            ->first();

        if (! $current) {
            return 0.0;
        }

        $previous = MeterReading::where('customer_id', $customerId)
            ->where(function ($q) use ($year, $month) {
                $q->where('reading_year', '<', $year)
                  ->orWhere(function ($q2) use ($year, $month) {
                      $q2->where('reading_year', $year)->where('reading_month', '<', $month);
                  });
            })
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        $baseline = $previous?->meter_value ?? 0;
        $delta = (int) $current->meter_value - (int) $baseline;

        return $delta > 0 ? $delta : 0.0;
    }

    public function profile()
    {
        $user = Auth::user();
        $customer = Customer::with(['ticket', 'user'])
            ->where('user_id', $user->id)
            ->first();

        // Jangan pernah kembalikan null dengan 200:billDetail() dan
        // billHistory() menolak dengan 403, jadi bentuk kegagalan untuk
        // "pelanggan tanpa record Customer" harus konsisten di semua endpoint.
        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan.',
            ], 404);
        }

        // Pilih field secara eksplisit. `$customer` dengan relasi `user`
        // ikut memuat model User utuh; endpoint ini satu-satunya yang
        // mengembalikan model mentah seperti itu, jadi jangan
        // bergantung pada atribut #[Hidden] pada model.
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'initial_meter_reading' => $customer->initial_meter_reading,
                'meter_photo_url' => $customer->meter_photo_url,
                'activated_at' => $customer->activated_at,
                'created_at' => $customer->created_at,
                'ticket' => $customer->ticket ? [
                    'id' => $customer->ticket->id,
                    'applicant_name' => $customer->ticket->applicant_name,
                    'address' => $customer->ticket->address,
                    'phone' => $customer->ticket->phone,
                    'status' => $customer->ticket->status,
                    'village_id' => $customer->ticket->village_id,
                ] : null,
                'user' => $customer->user ? [
                    'id' => $customer->user->id,
                    'name' => $customer->user->name,
                    'email' => $customer->user->email,
                ] : null,
            ],
        ]);
    }

    public function billDetail($id = null)
    {
        $user = Auth::user();
        $customer = Customer::with(['ticket.package', 'user'])
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($id) {
            $bill = MonthlyBill::where('customer_id', $customer->id)->where('id', $id)->first();
        } else {
            // Default to latest
            $bill = MonthlyBill::where('customer_id', $customer->id)
                ->orderBy('billing_period_year', 'desc')
                ->orderBy('billing_period_month', 'desc')
                ->first();
        }

        // Pastikan bill benar-benar milik pelanggan yang login
        if (!$bill || (int) $bill->customer_id !== (int) $customer->id) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                // Jangan kirim model mentah: `abodemen` di baris tagihan
                // adalah angka yang benar-benar ditagihkan (sudah final saat
                // tagihan dibuat), sedangkan `package.monthly_abodemen` bisa
                // berbeda karena paket diubah setelah tagihan terbit.
                'bill' => $this->billPayload($bill),
                'customer' => [
                    'name' => $this->customerName($customer),
                    'customer_code' => $customer->customer_code,
                    'address' => $customer->ticket?->address ?? '-',
                    'package_name' => $customer->ticket?->package?->name ?? '-',
                ]
            ]
        ]);
    }
    public function billHistory()
    {
        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Hanya tagihan milik sendiri (sudah pasti karena customer_id = $customer->id,
        // dan $customer hanya ada kalau user_id cocok — lapisan tambahan di sini)
        $bills = MonthlyBill::where('customer_id', $customer->id)
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->get();

        // Calculate some stats (gauge 3 bulan terakhir yang sudah dibayar ATAU seluruh data)
        $totalUsage = $bills->take(3)->sum('usage_m3');
        $avgAmount = $bills->count() > 0 ? $bills->avg('total_amount') : 0;
        $status = $bills->where('status', 'unpaid')->count() > 0 ? 'Tertunggak' : 'Lancar';

        return response()->json([
            'success' => true,
            'data' => [
                'bills' => $bills->map(fn ($b) => $this->billPayload($b))->values(),
                'stats' => [
                    'total_usage_3_months' => $totalUsage,
                    'avg_amount' => $avgAmount,
                    'current_status' => $status
                ],
                'customer_code' => $customer->customer_code
            ]
        ]);
    }
}
