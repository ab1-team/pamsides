<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeterReadingController extends Controller
{
    /**
     * Ambil data meteran yang SUDAH di-input berdasarkan filter Bulan dan Tahun
     */
    public function completed(Request $request)
    {
        // Validasi input parameter query
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ], [
            'month.required' => 'Filter bulan wajib diisi.',
            'month.between' => 'Bulan harus bernilai antara 1 sampai 12.',
            'year.required' => 'Filter tahun wajib diisi.',
        ]);

        // Mengambil data meteran dengan eager loading customer.user dan customer.ticket
        $readings = MeterReading::with([
            'customer.user',    // Untuk mengambil Nama Pelanggan
            'customer.ticket',   // Untuk mengambil Alamat Pendaftaran tiket
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


    //  Ambil data pelanggan yang BELUM dicatat meternya berdasarkan filter Bulan dan Tahun
    public function index(Request $request)
    {
        // 1. Validasi input parameter query dari frontend Vue
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $bulan = $request->month;
        $tahun = $request->year;

        // 2. Ambil customer yang BELUM ada record meter di bulan & tahun terpilih
        $customers = Customer::with(['user', 'ticket.village']) 
            // Pastikan hanya memunculkan pelanggan yang tiket instalasinya sudah di-aktivasi (completed)
            ->whereHas('ticket', function ($query) {
                $query->where('status', 'completed');
            })
            // Filter pendeteksi pending: Tidak boleh ada record di tabel meter_readings pada periode ini
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
     * Simpan data pencatatan meter baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'meter_value' => 'required|integer|min:0',
            'photo' => 'required|image|max:2048',
            'reading_month' => 'nullable|integer|between:1,12',
            'reading_year' => 'nullable|integer|min:2000',
        ]);

        $bulan = $request->reading_month ?? Carbon::now()->month;
        $tahun = $request->reading_year ?? Carbon::now()->year;

        // Cek apakah sudah ada input pada periode ini
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

        // Ambil data meter terakhir (secara urutan waktu global ke belakang)
        $last = MeterReading::where('customer_id', $request->customer_id)
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        // Validasi meter tidak boleh lebih kecil dari bulan sebelumnya
        if ($last && $request->meter_value < $last->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Angka meter tidak boleh lebih kecil dari bulan sebelumnya (Catatan terakhir: '.$last->meter_value.' m³)',
            ], 400);
        }

        // Upload foto bukti meteran ke public storage
        $path = $request->file('photo')->store('meter-readings', 'public');

        $reading = MeterReading::create([
            'customer_id' => $request->customer_id,
            'recorded_by' => Auth::id(), // Menggunakan ID user yang sedang login
            'reading_month' => $bulan,
            'reading_year' => $tahun,
            'meter_value' => $request->meter_value,
            'photo_url' => $path,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meter berhasil dicatat',
            'data' => $reading,
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

        // Validasi input
        $request->validate([
            'meter_value' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Cari record SEBELUM bulan dari record yang sedang diedit ini
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

        // Validasi angka baru tidak boleh lebih kecil dari bulan sebelumnya
        if ($previous && $request->meter_value < $previous->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Meter tidak boleh lebih kecil dari bulan sebelumnya ('.$previous->meter_value.' m³)',
            ], 400);
        }

        // Update nilai meter
        $reading->meter_value = $request->meter_value;

        // Jika ada foto baru, upload dan update path
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('meter-readings', 'public');
            $reading->photo_url = $path;
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
            fn () => $reading->delete(),
            'METER_READING_IN_USE',
            'Pencatatan meter',
        );
    }
}
