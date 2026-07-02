<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statistics(Request $request)
    {
        $now   = now();
        $year  = (int) $request->query('year', $now->year);
        $month = (int) $request->query('month', $now->month);

        // Jumlah pelanggan aktif
        $totalCustomers = Customer::count();

        // Tiket per status
        $ticketsByStatus = InstallationTicket::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Pendapatan bulan ini (tagihan yang sudah paid)
        $revenueThisMonth = MonthlyBill::where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->where('status', 'paid')
            ->sum('total_amount');

        // Tagihan bulan ini
        $billsThisMonth = MonthlyBill::where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Tiket terbaru
        $latestTickets = InstallationTicket::with('package')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Tagihan jatuh tempo (unpaid & due_date <= hari ini)
        $overdueBills = MonthlyBill::with('customer.user')
            ->where('status', 'unpaid')
            ->where('due_date', '<=', $now->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Keuangan bulan ini dari jurnal umum (akun pendapatan 4.x di-kredit, akun beban 5.x di-debet)
        $pendapatanThisMonth = Transaction::whereYear('tgl_transaksi', $year)
            ->whereMonth('tgl_transaksi', $month)
            ->where('account_kredit', 'like', '4.%')
            ->sum('saldo');

        $bebanThisMonth = Transaction::whereYear('tgl_transaksi', $year)
            ->whereMonth('tgl_transaksi', $month)
            ->where('account_debet', 'like', '5.%')
            ->sum('saldo');

        $surplusThisMonth = $pendapatanThisMonth - $bebanThisMonth;

        // Riwayat keuangan per bulan dari jurnal umum pada tahun fiskal $year
        $monthlyRows = Transaction::selectRaw('YEAR(tgl_transaksi) as y, MONTH(tgl_transaksi) as m,
                COALESCE(SUM(CASE WHEN account_kredit LIKE ? THEN saldo ELSE 0 END), 0) as pendapatan,
                COALESCE(SUM(CASE WHEN account_debet LIKE ? THEN saldo ELSE 0 END), 0) as beban', ['4.%', '5.%'])
            ->whereYear('tgl_transaksi', $year)
            ->groupBy(DB::raw('YEAR(tgl_transaksi)'), DB::raw('MONTH(tgl_transaksi)'))
            ->orderBy(DB::raw('YEAR(tgl_transaksi)'))
            ->orderBy(DB::raw('MONTH(tgl_transaksi)'))
            ->get();

        $financeChart = $monthlyRows->map(function ($r) {
            $p = (float) $r->pendapatan;
            $b = (float) $r->beban;
            return [
                'year'      => (int) $r->y,
                'month'     => (int) $r->m,
                'pendapatan'=> $p,
                'beban'     => $b,
                'surplus'   => $p - $b,
            ];
        })->values();

        $availableYears = Transaction::selectRaw('DISTINCT YEAR(tgl_transaksi) as y')
            ->whereNotNull('tgl_transaksi')
            ->orderBy('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_customers'   => $totalCustomers,
                'tickets_by_status' => $ticketsByStatus,
                'revenue_this_month'=> $revenueThisMonth,
                'bills_this_month'  => $billsThisMonth,
                'latest_tickets'    => $latestTickets,
                'overdue_bills'     => $overdueBills,
                'finance'           => [
                    'pendapatan' => $pendapatanThisMonth,
                    'beban'      => $bebanThisMonth,
                    'surplus'    => $surplusThisMonth,
                    'year'       => $year,
                    'month'      => $month,
                ],
                'finance_chart'     => $financeChart,
                'available_years'   => $availableYears,
            ],
        ]);
    }

    public function getNotification()
    {
        $userId = auth()->id();
        $dismissed = Cache::get('overdue_gen_dismissed_' . $userId, false);

        if ($dismissed) {
            return response()->json([
                'success' => true,
                'data'    => null,
            ]);
        }

        $notification = Cache::get('overdue_gen_notification');

        return response()->json([
            'success' => true,
            'data'    => $notification,
        ]);
    }

    public function dismissNotification()
    {
        $userId = auth()->id();
        Cache::put('overdue_gen_dismissed_' . $userId, true, now()->addDays(7));

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditutup',
        ]);
    }
}
