<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\InstallationTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Menampilkan daftar pelanggan aktif (Sederhana).
     */
    public function index()
    {
        // Langsung ambil semua data pelanggan dengan relasi usernya
        $customers = Customer::with(['user', 'ticket'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    
    //  Store: Proses Aktivasi Tiket menjadi Pelanggan Tetap.
    public function store(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:installation_tickets,id',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'initial_meter_reading' => 'required',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Ambil data tiket pendaftaran
                $ticket = InstallationTicket::findOrFail($request->ticket_id);

                // 2. Buat User baru Role wajib 'pelanggan'
                $user = User::create([
                    'name' => $ticket->applicant_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'pelanggan', 
                ]);

                // 3. Buat Kode Pelanggan Otomatis (Contoh: PDAM-00001)
                $customerCode = 'PDAM-' . str_pad(Customer::count() + 1, 5, '0', STR_PAD_LEFT);

                // 4. Masukkan ke tabel customers 
                $customer = Customer::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'customer_code' => $customerCode,
                    'initial_meter_reading' => $request->initial_meter_reading,
                    'meter_photo_url' => $request->meter_photo_url,
                    'activated_at' => now(),
                ]);

                // 5. Update status tiket asal menjadi 'completed'[cite: 1]
                $ticket->update(['status' => 'completed']);

                return response()->json([
                    'success' => true,
                    'message' => 'Data pelanggan berhasil dibuat',
                    'customer' => $customer
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete(); 

        return response()->json(['success' => true, 'message' => 'Berhasil dihapus']);
    }
}
