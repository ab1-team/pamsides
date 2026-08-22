<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallationTicket::with(['user', 'customer']);

        if ($request->search) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Least-privilege: teknisi hanya melihat tiket yang sudah masuk tahap aktif
        if (auth()->check() && auth()->user()->role === 'teknisi') {
            $query->whereIn('status', ['surveyed', 'unpaid', 'processing', 'completed', 'suspended']);
        }

        $tickets = $query->latest()->paginate($request->get('per_page', 10));

        $items = $tickets->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->applicant_name,
                'nik' => $t->nik,
                'no_telp' => $t->phone ?? '-',
                'address' => $t->address ?? '-',
                'status' => $t->status,
                'customer_code' => optional($t->customer->first())->customer_code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $items,
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->get('q', $request->search, '');

        $hasBills = filter_var($request->get('with_bills', false), FILTER_VALIDATE_BOOLEAN);

        $query = InstallationTicket::with([
            'customer',
            'user',
            'package',
            'package.waterTariffBlocks',
            'village',
        ])->whereIn('status', ['completed', 'suspended', 'terminated']);

        if ($hasBills) {
            $query->whereHas('customer.monthlyBills');
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $tickets = $query->limit(20)->get();

        $items = $tickets->map(function ($t) {
            $customer = $t->customer->first();
            $customerId = $customer->id ?? null;
            $package = $t->package;
            $tariffBlocks = $package?->waterTariffBlocks?->map(fn ($b) => [
                'id' => $b->id,
                'usage_min_m3' => (int) $b->usage_min_m3,
                'usage_max_m3' => $b->usage_max_m3 !== null ? (int) $b->usage_max_m3 : null,
                'price_per_m3' => (float) $b->price_per_m3,
                'min' => (float) $b->usage_min_m3,
                'max' => $b->usage_max_m3 !== null ? (float) $b->usage_max_m3 : null,
                'price' => (float) $b->price_per_m3,
            ])->values() ?? [];

            return [
                'id' => $customerId,
                'customer_id' => $customerId,
                'ticket_id' => $t->id,
                'customer_code' => $customer->customer_code ?? null,
                'installationCode' => $customer->customer_code ?? null,
                'name' => $t->applicant_name,
                'nik' => $t->nik,
                'phone' => $t->phone ?? '-',
                'address' => $t->address ?? '-',
                'village' => $t->village?->village_name ?? '-',
                'hamlet' => $t->village?->hamlet_name ?? '-',
                'rt' => $t->village?->rt ?? null,
                'rw' => $t->village?->rw ?? null,
                'cater' => $t->user?->name ?? '-',
                'package_id' => $package?->id ?? null,
                'packageName' => $package?->name ?? 'Paket Standar',
                'installation_fee' => $package ? (float) $package->installation_fee : 0,
                'abodemen' => $package ? (float) $package->monthly_abodemen : 0,
                'penalty' => $package ? (float) $package->late_penalty : 0,
                'tariffBlocks' => $tariffBlocks,
                'status' => 'Aktif',
            ];
        })->filter(fn ($i) => ! empty($i['id']))->values();

        return response()->json([
            'success' => true,
            'data' => $items,
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
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'pelanggan',
                ]);

                $ticket = InstallationTicket::create([
                    'package_id' => $request->package_id ?? 1,
                    'user_id' => $user->id,
                    'applicant_name' => $request->nama_lengkap,
                    'nik' => $request->nik,
                    'address' => $request->alamat_lengkap,
                    'phone' => $request->no_telp ?? '-',
                    'gender' => $request->jenis_kelamin == 'Perempuan' ? 'female' : 'male',
                    'birth_place' => $request->tempat_lahir ?? '-',
                    'birth_date' => $request->tgl_lahir ? date('Y-m-d', strtotime($request->tgl_lahir)) : now(),
                    'lat' => 0,
                    'lng' => 0,
                    'status' => 'draft',
                    'created_by' => auth()->id() ?? 1,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data pelanggan berhasil disimpan',
                    'data' => $ticket,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $ticket = InstallationTicket::with('user')->findOrFail($id);

        $request->validate([
            'nik' => 'required',
            'nama_lengkap' => 'required',
            'email' => 'required|email|unique:users,email,'.$ticket->user_id,
        ]);

        DB::transaction(function () use ($request, $ticket) {
            $ticket->user->update([
                'name' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $ticket->user->password,
            ]);

            $ticket->update([
                'applicant_name' => $request->nama_lengkap,
                'nik' => $request->nik,
                'address' => $request->alamat_lengkap,
                'phone' => $request->no_telp,
                'gender' => $request->jenis_kelamin == 'Perempuan' ? 'female' : 'male',
                'birth_place' => $request->tempat_lahir,
                'birth_date' => $request->tgl_lahir ? date('Y-m-d', strtotime($request->tgl_lahir)) : null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
        ]);
    }

    public function destroy($id)
    {
        $ticket = InstallationTicket::with('user')->findOrFail($id);

        return $this->safeDelete(
            fn () => DB::transaction(function () use ($ticket) {
                if ($ticket->user) {
                    $ticket->user->delete();
                }
                $ticket->delete();
            }),
            'TICKET_IN_USE',
            'Data tiket',
            $ticket->applicant_name,
            'Data berhasil dihapus',
        );
    }

    public function show($id)
    {
        $ticket = InstallationTicket::with(['user', 'customer'])->findOrFail($id);

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
                'customer_code' => optional($ticket->customer->first())->customer_code,
            ],
        ]);
    }
}
