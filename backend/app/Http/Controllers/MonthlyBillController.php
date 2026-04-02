<?php

namespace App\Http\Controllers;
use App\Models\MonthlyBill;
use App\Models\BillPayment;
use App\Models\WaterTariffBlock;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Services\MonthlyBillService;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MonthlyBillController extends Controller
{
    public function __construct(protected MonthlyBillService $monthlyBillService) {}
    public function index(Request $request)
    {
        $query = MonthlyBill::query();

        // filter by customer
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // filter by bulan & tahun
        if ($request->month && $request->year) {
            $query->where('billing_period_month', $request->month)
                ->where('billing_period_year', $request->year);
        }

        $bills = $query->latest()->get();

        return response()->json([
            'message' => 'Daftar tagihan',
            'data' => $bills
        ]);
    }

    public function pay($id)
    {
        //konfirmasi pembayaran
        //update status
        //simpan payment
        $bill = MonthlyBill::findOrFail($id);

        if ($bill->status == 'paid') {
            return response()->json([
                'message' => 'Tagihan sudah dibayar'
            ], 400);
        }

        // update status
        $bill->update([
            'status' => 'paid'
        ]);

        // simpan pembayaran
        BillPayment::create([
            'bill_id' => $bill->id,
            'amount_paid' => $bill->total_amount,
            'confirmed_by' => Auth::id(),
            'paid_at' => now()
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil dikonfirmasi'
        ]);
    }

    public function generate()
    {
        $result = $this->monthlyBillService->generate();

        if (!$result['status']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        return response()->json($result);
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

        // hitung total
        $total = $bills->sum('total_amount');
        $paid = $bills->where('status', 'paid')->sum('total_amount');
        $unpaid = $bills->where('status', 'unpaid')->sum('total_amount');

        return response()->json([
            'message' => 'Laporan tagihan',
            'periode' => $request->month . '-' . $request->year,
            'summary' => [
                'total_tagihan' => $total,
                'total_dibayar' => $paid,
                'total_belum_dibayar' => $unpaid,
            ],
            'data' => $bills
        ]);
    }
}
