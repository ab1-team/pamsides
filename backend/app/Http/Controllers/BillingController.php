<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billingService) {}

    public function generate(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer|min:2000',
            'month' => 'required|integer|between:1,12',
        ], [
            'year.required'    => 'Tahun wajib diisi.',
            'year.integer'     => 'Tahun harus berupa angka.',
            'month.required'   => 'Bulan wajib diisi.',
            'month.between'    => 'Bulan harus antara 1-12.',
        ]);

        $year  = $request->year;
        $month = $request->month;

        // Cek apakah tagihan bulan ini sudah digenerate
        $alreadyGenerated = MonthlyBill::where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->exists();

        if ($alreadyGenerated) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'Tagihan bulan ini sudah pernah digenerate.'],
            ], 422);
        }

        // Cek apakah semua pelanggan sudah dicatat meternya
        $unrecordedCount = Customer::whereDoesntHave('meterReadings', function ($q) use ($year, $month) {
            $q->where('reading_year', $year)
              ->where('reading_month', $month);
        })->count();

        if ($unrecordedCount > 0) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => "Masih ada {$unrecordedCount} pelanggan yang belum dicatat meternya."],
            ], 422);
        }

        // Generate tagihan untuk semua pelanggan
        $customers = Customer::with([
            'meterReadings',
            'ticket.package.waterTariffBlocks',
        ])->get();

        $bills = [];
        foreach ($customers as $customer) {
            $bills[] = $this->billingService->generateForCustomer($customer, $year, $month);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'message'       => 'Tagihan berhasil digenerate.',
                'total_bills'   => count($bills),
                'bills'         => $bills,
            ],
        ], 201);
    }
}
