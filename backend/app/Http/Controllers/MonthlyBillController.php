<?php

namespace App\Http\Controllers;
use App\Models\MonthlyBill;
use App\Models\BillPayment;
use App\Models\WaterTariffBlock;
use App\Models\Customer;
use App\Models\MeterReading;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MonthlyBillController extends Controller
{
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
        $now = now();
        $bulan = $now->month;
        $tahun = $now->year;

        // CEGAH GENERATE DOBEL
        $exists = MonthlyBill::where('billing_period_month', $bulan)
            ->where('billing_period_year', $tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Tagihan bulan ini sudah pernah digenerate'
            ], 400);
        }

        // VALIDASI SEMUA METER SUDAH MASUK
        $totalCustomer = Customer::count();

        $totalReading = MeterReading::where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->count();

        if ($totalReading < $totalCustomer) {
            return response()->json([
                'message' => 'Masih ada pelanggan yang belum dicatat meternya'
            ], 400);
        }

        $readings = MeterReading::where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->get();

        $count = 0; // counter hasil generate

        foreach ($readings as $reading) {

            // ambil meter sebelumnya
            $last = MeterReading::where('customer_id', $reading->customer_id)
                ->where(function ($q) use ($bulan, $tahun) {
                    $q->where('reading_year', '<', $tahun)
                    ->orWhere(function ($q2) use ($bulan, $tahun) {
                        $q2->where('reading_year', $tahun)
                            ->where('reading_month', '<', $bulan);
                    });
                })
                ->orderByDesc('reading_year')
                ->orderByDesc('reading_month')
                ->first();

            if (!$last) continue;

            // hitung pemakaian
            $usage = $reading->meter_value - $last->meter_value;

            //  PROTEKSI DATA ANEH
            if ($usage < 0) continue;

            $customer = Customer::with('ticket.package')->find($reading->customer_id);

            // JAGA-JAGA RELASI KOSONG
            if (!$customer || !$customer->ticket || !$customer->ticket->package) {
                continue;
            }

            $package = $customer->ticket->package;

            // tarif progresif
            $usage_charge = $this->calculateTariff($usage, $package->id);

            // abodemen
            $abodemen = $package->monthly_abodemen;

            // denda otomatis
            $penalty = $this->calculatePenalty(
                $reading->customer_id,
                $bulan,
                $tahun,
                $package
            );

            $total = $usage_charge + $abodemen + $penalty;

            MonthlyBill::create([
                'customer_id' => $reading->customer_id,
                'billing_period_month' => $bulan,
                'billing_period_year' => $tahun,
                'meter_reading_start' => $last->meter_value,
                'meter_reading_end' => $reading->meter_value,
                'usage_m3' => $usage,
                'usage_charge' => $usage_charge,
                'abodemen' => $abodemen,
                'penalty_amount' => $penalty,
                'total_amount' => $total,
                'status' => 'unpaid',
                'due_date' => now()->addDays(20)
            ]);

            $count++;
        }

        return response()->json([
            'message' => 'Tagihan berhasil digenerate',
            'total_generated' => $count
        ]);
    }
    private function calculateTariff($usage, $package_id)
    {
        $blocks = WaterTariffBlock::where('package_id', $package_id)
            ->orderBy('usage_min_m3')
            ->get();

        $remaining = $usage;
        $total = 0;

        foreach ($blocks as $block) {

            if ($remaining <= 0) break;

            $min = $block->usage_min_m3;
            $max = $block->usage_max_m3 ?? $remaining;

            $range = $max - $min + 1;

            $used = min($remaining, $range);

            $total += $used * $block->price_per_m3;

            $remaining -= $used;
        }

        return $total;
    }
    private function calculatePenalty($customer_id, $bulan, $tahun, $package)
    {
        // cari bulan N-2
        $date = Carbon::create($tahun, $bulan, 1)->subMonths(2);

        $bill = MonthlyBill::where('customer_id', $customer_id)
            ->where('billing_period_month', $date->month)
            ->where('billing_period_year', $date->year)
            ->first();

        if ($bill && $bill->status == 'unpaid') {
            return $package->late_penalty;
        }

        return 0;
    }
}
