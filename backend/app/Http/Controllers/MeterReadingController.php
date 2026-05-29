<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MeterReadingController extends Controller
{
    /**
     * Display a listing of customers yang belum dicatat bulan ini.
     */
    public function index()
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        $customers = Customer::with('user') // 🔥 penting untuk tampil nama
            ->whereDoesntHave('meterReadings', function ($query) use ($bulan, $tahun) {
                $query->where('reading_month', $bulan)
                      ->where('reading_year', $tahun);
            })
            ->get();

        return response()->json([
            'message' => 'Daftar pelanggan yang belum dicatat meter bulan ini',
            'data' => $customers
        ]);
    }

    /**
     * Store meter reading
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'meter_value' => 'required|integer|min:0',
            'photo' => 'required|image'
        ]);

        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // CEK DUPLIKAT BULAN INI
        $exists = MeterReading::where('customer_id', $request->customer_id)
            ->where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Meter bulan ini sudah dicatat'
            ], 400);
        }

        // AMBIL DATA CUSTOMER
        $customer = Customer::findOrFail($request->customer_id);

        // FIX BUG URUTAN BULAN (WAJIB)
        $last = MeterReading::where('customer_id', $request->customer_id)
            ->orderByDesc(DB::raw('reading_year * 100 + reading_month'))
            ->first();

        // TENTUKAN METER AWAL
        $startMeter = $last 
            ? $last->meter_value 
            : $customer->initial_meter_reading;

        // VALIDASI METER
        if ($request->meter_value < $startMeter) {
            return response()->json([
                'message' => 'Meter tidak boleh lebih kecil dari meter sebelumnya'
            ], 400);
        }

        // UPLOAD FOTO
        $path = $request->file('photo')->store('meter-readings', 'public');

        // SIMPAN DATA
        $reading = MeterReading::create([
            'customer_id' => $request->customer_id,
            'recorded_by' => Auth::id(),
            'reading_month' => $bulan,
            'reading_year' => $tahun,
            'meter_value' => $request->meter_value,
            'photo_url' => $path,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Meter berhasil dicatat',
            'data' => $reading
        ]);
    }

    /**
     * OPTIONAL: Riwayat meter per customer (nanti kepakai di tagihan)
     */
    public function history($customerId)
    {
        $data = MeterReading::where('customer_id', $customerId)
            ->orderByDesc(DB::raw('reading_year * 100 + reading_month'))
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }
}