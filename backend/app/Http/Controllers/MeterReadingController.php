<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MeterReadingController extends Controller
{
    /**
     * Ambil data meteran yang SUDAH di-input berdasarkan filter Bulan dan Tahun
     */
    public function completed(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ], [
            'month.required' => 'Filter bulan wajib diisi.',
            'month.between' => 'Bulan harus bernilai antara 1 sampai 12.',
            'year.required' => 'Filter tahun wajib diisi.',
        ]);

        $readings = MeterReading::with([
            'customer.user',
            'customer.ticket',
        ])
            ->where('reading_month', $request->month)
            ->where('reading_year', $request->year)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pencatatan meter.',
            'data' => $readings,
        ]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $bulan = $request->month;
        $tahun = $request->year;

        $customers = Customer::with(['user', 'ticket.village'])
            ->whereHas('ticket', function ($query) {
                $query->where('status', 'completed');
            })
            ->whereDoesntHave('meterReadings', function ($query) use ($bulan, $tahun) {
                $query->where('reading_month', $bulan)
                    ->where('reading_year', $tahun);
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pelanggan yang belum dicatat meter periode terpilih',
            'total_customers' => $customers->count(),
            'data' => $customers,
        ]);
    }

    /**
     * Simpan data pencatatan meter baru + generate tagihan + cek tunggakan
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'meter_value' => 'required|numeric|min:0|max:99999999.99',
            'photo' => 'required|image|max:2048',
            'reading_month' => 'required|integer|between:1,12',
            'reading_year' => 'required|integer|min:2000',
        ]);

        $bulan = $request->reading_month;
        $tahun = $request->reading_year;

        $exists = MeterReading::where('customer_id', $request->customer_id)
            ->where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Meter pelanggan untuk periode bulan dan tahun ini sudah dicatat.',
            ], 400);
        }

        $last = MeterReading::where('customer_id', $request->customer_id)
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        if ($last && $request->meter_value < $last->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Angka meter tidak boleh lebih kecil dari bulan sebelumnya (Catatan terakhir: '.$last->meter_value.' m³)',
            ], 400);
        }

        $file = $request->file('photo');
        $fileName = time().'_'.$file->getClientOriginalName();
        $file->storeAs('meter-readings', $fileName, 'public');

        $reading = MeterReading::create([
            'customer_id' => $request->customer_id,
            'recorded_by' => Auth::id(),
            'reading_month' => $bulan,
            'reading_year' => $tahun,
            'meter_value' => $request->meter_value,
            'photo_url' => $fileName,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result,
        ]);
    }

    public function show(string $id)
    {
        $reading = MeterReading::with(['customer.user', 'customer.ticket'])->find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pencatatan meter ditemukan',
            'data' => $reading,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $reading = MeterReading::find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'meter_value' => 'required|numeric|min:0|max:99999999.99',
            'photo' => 'nullable|image|max:2048',
        ]);

        $previous = MeterReading::where('customer_id', $reading->customer_id)
            ->where(function ($query) use ($reading) {
                $query->where('reading_year', '<', $reading->reading_year)
                    ->orWhere(function ($q) use ($reading) {
                        $q->where('reading_year', $reading->reading_year)
                            ->where('reading_month', '<', $reading->reading_month);
                    });
            })
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        if ($previous && $request->meter_value < $previous->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Meter tidak boleh lebih kecil dari bulan sebelumnya ('.$previous->meter_value.' m³)',
            ], 400);
        }

        $reading->meter_value = $request->meter_value;

        if ($request->hasFile('photo')) {
            if ($reading->photo_url) {
                Storage::disk('public')->delete('meter-readings/'.$reading->photo_url);
            }
            $file = $request->file('photo');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->storeAs('meter-readings', $fileName, 'public');
            $reading->photo_url = $fileName;
        }

        $reading->save();

        return response()->json([
            'success' => true,
            'message' => 'Pencatatan meter berhasil diperbarui',
            'data' => $reading,
        ]);
    }

    public function destroy(string $id)
    {
        $reading = MeterReading::find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        return $this->safeDelete(
            function () use ($reading) {
                if ($reading->photo_url) {
                    Storage::disk('public')->delete('meter-readings/'.$reading->photo_url);
                }
                $reading->delete();
            },
            'METER_READING_IN_USE',
            'Pencatatan meter',
        );
    }
}
