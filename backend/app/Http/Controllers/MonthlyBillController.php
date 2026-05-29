<?php

namespace App\Http\Controllers;
use App\Models\MonthlyBill;
use App\Models\BillPayment;
use App\Models\WaterTariffBlock;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Services\MonthlyBillService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MonthlyBillController extends Controller
{
    public function __construct(protected MonthlyBillService $monthlyBillService) {}
    public function index(Request $request)
    {
        $query = MonthlyBill::query();

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->month && $request->year) {
            $query->where('billing_period_month', $request->month)
                ->where('billing_period_year', $request->year);
        }

        $bills = $query->with('customer.user')->latest()->get();
        return response()->json([
            'success' => true,
            'data' => [
                'bills' => $bills
            ]
        ]);
    }

    //konfirmasi pembayaran
    public function pay($id)
    {
        return DB::transaction(function () use ($id) {

            $bill = MonthlyBill::findOrFail($id);

            if ($bill->status == 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tagihan sudah dibayar'
                ], 400);
            }

            $bill->update([
                'status' => 'paid'
            ]);

            BillPayment::create([
                'bill_id' => $bill->id,
                'amount_paid' => $bill->total_amount,
                'confirmed_by' => Auth::id(),
                'paid_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi'
            ]);
        });
    }
    
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $result = $this->monthlyBillService->generate(
            $request->month,
            $request->year
        );

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ]);
    }
    //laporan tagihan
    public function report(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $bills = MonthlyBill::where('billing_period_month', $request->month)
            ->where('billing_period_year', $request->year)
            ->get();

        $summary = [
            'total_tagihan' => $bills->sum('total_amount'),
            'total_dibayar' => $bills->where('status', 'paid')->sum('total_amount'),
            'total_belum_dibayar' => $bills->where('status', 'unpaid')->sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'bills' => $bills
            ]
        ]);
    }
    public function checkByCustomerCode($code)
    {
        $customer = Customer::where('customer_code', $code)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        $bills = MonthlyBill::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('billing_period_year')
            ->orderBy('billing_period_month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code
                ],
                'bills' => $bills
            ]
        ]);
    }
}
