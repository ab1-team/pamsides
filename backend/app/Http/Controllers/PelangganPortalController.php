<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\MeterReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelangganPortalController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Find the customer associated with the user
        $customer = Customer::with(['ticket'])
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

        // Get usage history (last 5 months)
        $usageHistory = MonthlyBill::where('customer_id', $customer->id)
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->limit(5)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $customer->ticket->applicant_name ?? $user->name,
                    'customer_code' => $customer->customer_code,
                ],
                'latest_bill' => $latestBill,
                'usage_history' => $usageHistory,
                // Add saldo/balance if we eventually implement it, for now hardcode 0 or from user
                'balance' => 0 
            ]
        ]);
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

        if (!$bill) {
            return response()->json(['success' => false, 'message' => 'Bill not found'], 404);
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

        $bills = MonthlyBill::where('customer_id', $customer->id)
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->get();

        // Calculate some stats
        $totalUsage = $bills->take(3)->sum('usage_m3');
        $avgAmount = $bills->count() > 0 ? $bills->avg('total_amount') : 0;
        $status = $bills->where('status', 'unpaid')->count() > 0 ? 'Tertunggak' : 'Lancar';

        return response()->json([
            'success' => true,
            'data' => [
                'bills' => $bills,
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
