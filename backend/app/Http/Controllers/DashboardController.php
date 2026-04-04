<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function statistics()
    {
        $now   = now();
        $year  = $now->year;
        $month = $now->month;

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

        return response()->json([
            'success' => true,
            'data'    => [
                'total_customers'   => $totalCustomers,
                'tickets_by_status' => $ticketsByStatus,
                'revenue_this_month'=> $revenueThisMonth,
                'bills_this_month'  => $billsThisMonth,
                'latest_tickets'    => $latestTickets,
                'overdue_bills'     => $overdueBills,
            ],
        ]);
    }
}
