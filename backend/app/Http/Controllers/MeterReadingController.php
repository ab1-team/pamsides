<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    public function unrecorded()
    {
        $year  = now()->year;
        $month = now()->month;

        $customers = Customer::with(['user', 'ticket.package'])
            ->whereDoesntHave('meterReadings', function ($q) use ($year, $month) {
                $q->where('reading_year', $year)
                  ->where('reading_month', $month);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'year'      => $year,
                'month'     => $month,
                'total'     => $customers->count(),
                'customers' => $customers,
            ],
        ]);
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MeterReadingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // ambil customer yang belum ada meter bulan ini
        $customers = Customer::whereDoesntHave('meterReadings', function ($query) use ($bulan, $tahun) {
            $query->where('reading_month', $bulan)
                ->where('reading_year', $tahun);
        })->get();

        return response()->json([
            'message' => 'Daftar pelanggan yang belum dicatat meter bulan ini',
            'data' => $customers
        ]);
    }

    /**
     * Store a newly created resource in storage.
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

        //CEK apakah sudah input bulan ini
        $exists = MeterReading::where('customer_id', $request->customer_id)
            ->where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Meter bulan ini sudah dicatat'
            ], 400);
        }

        //Ambil meter terakhir
        $last = MeterReading::where('customer_id', $request->customer_id)
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();
            
        //validasi meter tidak boleh lebih kecil
        if ($last && $request->meter_value < $last->meter_value) {
            return response()->json([
                'message' => 'Meter tidak boleh lebih kecil dari bulan sebelumnya'
            ], 400);
        }

        //Upload foto
        $path = $request->file('photo')->store('meter-readings', 'public');

        // Simpan data
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
