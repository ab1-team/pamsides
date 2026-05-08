<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\InstallationTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['user', 'ticket']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%');
                })
                ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $customers->map(function ($c) {
                return [
                    'id' => $c->id,
                    'customer_code' => $c->customer_code, 
                    'name' => optional($c->user)->name,
                    'nik' => optional($c->ticket)->nik ?? '-',
                    'address' => optional($c->ticket)->address ?? '-',
                    'status' => optional($c->ticket)->status ?? 'draft'
                ];
            })
        ]);
    }

    
    //  SIMPAN REGISTRASI (Sekali klik simpan ke Users, Tickets, dan Customers)
    public function store(Request $request)
    {
        // 1. Validasi input dari form "Registrasi Pelanggan"
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nik' => 'required',
            'address' => 'required',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 2. Buat User (Identitas Login)
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'pelanggan',
                ]);

                // 3. Buat Tiket (Draft Instalasi)
                $ticket = InstallationTicket::create([
                    'package_id' => $request->package_id ?? 1,
                    'user_id' => $user->id,
                    'applicant_name' => $request->name,
                    'nik' => $request->nik,
                    'phone' => $request->phone ?? '-', 
                    'gender' => $request->gender ?? 'male',
                    'birth_place' => $request->birth_place ?? '-',
                    'birth_date' => $request->birth_date ?? now(),
                    'address' => $request->address,
                    'lat' => 0, 
                    'lng' => 0, 
                    'status' => 'draft',
                    'created_by' => auth()->id() ?? 1, 
                ]);

                // 4. Buat Customer Code (Identitas Pembayaran)
                $customerCode = 'PAM-' . date('Ym') . '-' . Str::padLeft($user->id, 4, '0');
                
                $customer = Customer::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'customer_code' => $customerCode,
                    'initial_meter_reading' => 0,
                    'activated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registrasi Berhasil. Data tersimpan',
                    'customer_code' => $customerCode
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error DB: ' . $e->getMessage() 
            ], 500);
        }
    }
}
