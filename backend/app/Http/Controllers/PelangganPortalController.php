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
    public function dashboard()
    {
        $user = Auth::user();

        // Find the customer associated with the user
        $customer = Customer::with(['ticket.package'])
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
                ? (float) ($bill?->usage_m3 ?? $this->inferUsageFromReadings($customer->id, $year, $month))
                : (float) ($bill?->usage_m3 ?? 0);

            $series[] = [
                'period_key' => $periodKey,
                'year' => $year,
                'month' => $month,
                'usage_m3' => round($usageM3, 2),
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
                    'name' => $customer->ticket->applicant_name ?? $user->name,
                    'customer_code' => $customer->customer_code,
                    'address' => $customer->ticket->address ?? '-',
                    'package_name' => $customer->ticket->package->name ?? '-',
                ],
                'latest_bill' => $latestBill,
                'usage_history' => array_reverse(array_slice($series, -5)), // 5 terakhir, urut naik (kronologis)
                'distribution' => [
                    'series' => $series,
                    'months_count' => 12,
                    'summary' => [
                        'total_m3' => round($totalUsage, 2),
                        'avg_m3' => round($avgUsage, 2),
                        'max_m3' => round($maxUsage, 2),
                        'min_m3' => round($minUsage, 2),
                        'recorded_months' => count($nonZero),
                        'trend_direction' => $trendDirection,
                        'trend_percent' => $trendPercent,
                        'recent_avg_m3' => round($recentAvg, 2),
                        'previous_avg_m3' => round($previousAvg, 2),
                    ],
                ],
                'balance' => 0
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
        $delta = (float) $current->meter_value - (float) $baseline;

        return $delta > 0 ? $delta : 0.0;
    }

    public function profile()
    {
        $user = Auth::user();
        $customer = Customer::with(['ticket', 'user'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    public function billDetail($id = null)
    {
        $user = Auth::user();
        $customer = Customer::with(['ticket.package'])
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
                'bill' => $bill,
                'customer' => [
                    'name' => $customer->ticket->applicant_name ?? $user->name,
                    'customer_code' => $customer->customer_code,
                    'address' => $customer->ticket->address ?? '-',
                    'package_name' => $customer->ticket->package->name ?? '-',
                    'monthly_abodemen' => $customer->ticket->package->monthly_abodemen ?? 0,
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
                'bills' => $bills->map(fn ($b) => [
                    'id' => $b->id,
                    'customer_id' => $b->customer_id,
                    'billing_period_month' => $b->billing_period_month,
                    'billing_period_year' => $b->billing_period_year,
                    'meter_reading_start' => $b->meter_reading_start,
                    'meter_reading_end' => $b->meter_reading_end,
                    'usage_m3' => $b->usage_m3,
                    'usage_charge' => $b->usage_charge,
                    'abodemen' => $b->abodemen,
                    'penalty_amount' => $b->penalty_amount,
                    'total_amount' => $b->total_amount,
                    'status' => $b->status,
                    'due_date' => $b->due_date,
                ])->values(),
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
