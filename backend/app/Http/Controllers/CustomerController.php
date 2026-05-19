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
    $query = InstallationTicket::with('user');

    if ($request->search) {

        $query->where(function ($q) use ($request) {

            $q->where('applicant_name', 'like', '%' . $request->search . '%')
              ->orWhere('nik', 'like', '%' . $request->search . '%');

        });
    }

    $tickets = $query->latest()->paginate(10);

    return response()->json([
        'success' => true,

        'data' => $tickets->map(function ($t) {

            return [

                'id' => $t->id,

                'name' => $t->applicant_name,

                'nik' => $t->nik,

                'no_telp' => $t->phone ?? '-',

                'address' => $t->address ?? '-',

                'status' => $t->status,
            ];
        })
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'nama_lengkap' => 'required|string|max:255',

            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',

            'alamat_lengkap' => 'required',

        ], [

            'nik.required' => 'NIK wajib diisi',

            'nama_lengkap.required' => 'Nama lengkap wajib diisi',

            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',

            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',

            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi',
        ]);

        try {

            return DB::transaction(function () use ($request) {

                // 1. User
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'pelanggan',
                ]);

                // 2. Ticket Instalasi
                $ticket = InstallationTicket::create([

                    'package_id' => $request->package_id ?? 1,

                    'user_id' => $user->id,

                    'applicant_name' => $request->nama_lengkap,

                    'nik' => $request->nik,

                    'address' => $request->alamat_lengkap,

                    'phone' => $request->no_telp ?? '-',

                    'gender' =>
                        $request->jenis_kelamin == 'Perempuan'
                            ? 'female'
                            : 'male',

                    'birth_place' => $request->tempat_lahir ?? '-',

                    'birth_date' => $request->tgl_lahir
                        ? date('Y-m-d', strtotime($request->tgl_lahir))
                        : now(),

                    'lat' => 0,
                    'lng' => 0,

                    // status awal
                    'status' => 'pending',

                    'created_by' => auth()->id() ?? 1,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data pelanggan berhasil disimpan',
                    'data' => $ticket
                ], 201);
            });

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $ticket = InstallationTicket::with('user')->findOrFail($id);

        $request->validate([

            'nik' => 'required',

            'nama_lengkap' => 'required',

            'email' => 'required|email|unique:users,email,' . $ticket->user_id,

        ]);

        DB::transaction(function () use ($request, $ticket) {

            // update user
            $ticket->user->update([

                'name' => $request->nama_lengkap,

                'email' => $request->email,

                'password' => $request->password
                    ? Hash::make($request->password)
                    : $ticket->user->password,
            ]);

            // update ticket
            $ticket->update([

                'applicant_name' => $request->nama_lengkap,

                'nik' => $request->nik,

                'address' => $request->alamat_lengkap,

                'phone' => $request->no_telp,

                'gender' =>
                    $request->jenis_kelamin == 'Perempuan'
                        ? 'female'
                        : 'male',

                'birth_place' => $request->tempat_lahir,

                'birth_date' => $request->tgl_lahir
                    ? date('Y-m-d', strtotime($request->tgl_lahir))
                    : null,
                        ]);
        });

        return response()->json([

            'success' => true,

            'message' => 'Data berhasil diperbarui'
        ]);
    }
    public function destroy($id)
    {
        $ticket = InstallationTicket::with('user')->findOrFail($id);

        DB::transaction(function () use ($ticket) {

            if ($ticket->user) {
                $ticket->user->delete();
            }

            $ticket->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function show($id)
    {
        $ticket = InstallationTicket::with('user')->findOrFail($id);

        return response()->json([

            'success' => true,

            'data' => [

                'id' => $ticket->id,

                'name' => $ticket->applicant_name,

                'email' => optional($ticket->user)->email,

                'nik' => $ticket->nik,

                'phone' => $ticket->phone ?? '-',

                'address' => $ticket->address ?? '-',

                'gender' => $ticket->gender ?? 'male',

                'birth_place' => $ticket->birth_place ?? '-',

                'birth_date' => $ticket->birth_date,

                'status' => $ticket->status,
            ]
        ]);
    }
}
