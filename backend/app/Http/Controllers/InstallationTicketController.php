<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

class InstallationTicketController extends Controller
{
    /**
     * Menampilkan daftar tiket (Bisa diakses Admin & Surveyor).
     * PERBAIKAN: Mendukung pencarian NIK/Nama untuk semua status tanpa terkunci di 'draft'.
     */
    public function index(Request $request)
    {
        $query = InstallationTicket::with([
            'package.tariffBlocks',
            'package',
            'survey',
            'user',
            'village'
        ])->orderBy('created_at', 'desc');

        // 1. Pencarian berdasarkan nama atau NIK jika user mengetik di form search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('applicant_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        // 2. Filter status umum (hanya dipakai jika bukan dari menu dropdown registrasi)
        if ($request->has('status') && $request->status !== 'draft') {
            $query->where('status', $request->status);
        }

        // 3. PERBAIKAN DROPDOWN: Jika Vue meminta data registrasi (biasanya mengirim status=draft)
        // Kita lepas gembok statusnya agar NIK yang sudah pending/completed tetap muncul,
        // lalu di-unique berdasarkan 'nik' agar nama pelanggan tidak double di pilihan dropdown.
        if ($request->has('status') && $request->status === 'draft') {
        $allTickets = $query->get();

        $grouped = $allTickets->groupBy('nik')->map(function ($items) {

            // 🔥 ambil identitas terbaik (yang tidak null)
            $base = $items->firstWhere(fn($item) =>
                $item->phone || $item->gender || $item->birth_place
            ) ?? $items->first();

            // 🔥 kumpulkan semua ticket
            $tickets = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'village_id' => $item->village_id,
                    'lat' => $item->lat,
                    'lng' => $item->lng,
                    'package_id' => $item->package_id,
                    'package'    => $item->package,
                    'user_id' => $item->user_id,
                    'order_date' => $item->order_date,
                    'status' => $item->status,
                ];
            })->values();

            return [
                'id' => $base->id,
                'applicant_name' => $base->applicant_name,
                'nik' => $base->nik,

                // 🔥 IDENTITAS (AMAN)
                'phone' => $items->pluck('phone')->filter()->first(),
                'gender' => $items->pluck('gender')->filter()->first(),
                'birth_place' => $items->pluck('birth_place')->filter()->first(),
                'birth_date' => $items->pluck('birth_date')->filter()->first(),

                // 🔥 semua lokasi
                'tickets' => $tickets
            ];
        })->values();

        $tickets = $grouped;
    }
        else {
            $tickets = $query->paginate(10);
        }

        return response()->json([
            'success' => true,
            'data'    => $tickets,
        ]);
    }

 
    public function registerInstallation(Request $request, $id)
    {
        $request->validate([
            'package_id' => 'required|exists:installation_packages,id',
            'village_id' => 'required|exists:villages,id',
            'lat'        => 'required|numeric|between:-90,90',
            'lng'        => 'required|numeric|between:-180,180',
            'order_date' => 'required|date',
        ], [
            'package_id.required' => 'Paket instalasi wajib dipilih.',
            'package_id.exists'   => 'Paket instalasi tidak ditemukan.',
            'village_id.required' => 'Desa wajib dipilih.',
            'lat.required'        => 'Koordinat latitude wajib diisi.',
            'lng.required'        => 'Koordinat longitude wajib diisi.',
            'order_date.required' => 'Tanggal order wajib diisi.',
        ]);

        try {
            // 1. Cari data referensi pelanggan berdasarkan ID yang dipilih
            $oldTicket = InstallationTicket::findOrFail($id);

            // 2. KONDISI A: Jika data masih 'draft', berarti ini pelengkapan registrasi pertama kali.
            // Cukup UPDATE data tersebut, jangan buat data baru.
            if ($oldTicket->status === 'draft') {
                $oldTicket->update([
                    'package_id' => $request->package_id,
                    'user_id'    => $request->user_id,
                    'order_date' => date('Y-m-d', strtotime($request->order_date)),
                    'village_id' => $request->village_id,
                    'lat'        => $request->lat,
                    'lng'        => $request->lng,
                    'status'     => 'pending', // Naikkan status menjadi pending
                    'created_by' => auth()->id() ?: $oldTicket->created_by,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registrasi pertama berhasil dilengkapi (Data di-update).',
                    'data'    => $oldTicket->load('package'),
                ], 200);
            }

            // 3. KONDISI B: Jika statusnya sudah BUKAN 'draft' (misal sudah pending, processing, completed),
            // Berarti ini adalah registrasi pemasangan titik ke-2 atau seterusnya. Maka BUAT BARIS BARU.
                $newTicket = InstallationTicket::create([
                    'applicant_name' => $oldTicket->applicant_name, 
                    'address'        => $oldTicket->address,        
                    'nik'            => $oldTicket->nik,            

                    'phone'          => $oldTicket->phone,
                    'gender'         => $oldTicket->gender,
                    'birth_place'    => $oldTicket->birth_place,
                    'birth_date'     => $oldTicket->birth_date,

                    'package_id'     => $request->package_id,       
                    'user_id'        => $request->user_id,          
                    'order_date'     => date('Y-m-d', strtotime($request->order_date)),
                    'village_id'     => $request->village_id,       
                    'lat'            => $request->lat,              
                    'lng'            => $request->lng,              
                    'status'         => 'pending',
                    'created_by'     => auth()->id() ?: $oldTicket->created_by,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Instalasi titik baru berhasil didaftarkan tanpa menimpa titik lama.',
                'data'    => $newTicket->load('package'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu tiket (Bisa diakses Admin & Surveyor).
     */
    public function show(InstallationTicket $installationTicket)
    {
        return response()->json([
            'success' => true,
            'data'    => $installationTicket->load([
                'package.tariffBlocks',
                'package',
                'survey',
                'payments',
                'customer',
            ]),
        ]);
    }

    /**
     * Mengubah state/status ticket (Khusus Admin).
     * PERBAIKAN: Menambahkan status 'draft', 'suspended', dan 'terminated' ke dalam aturan validasi 'in'
     */
    public function transition(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'status' => 'required|string|in:draft,pending,surveyed,unpaid,processing,completed,suspended,terminated',
        ], [
            'status.required' => 'Status wajib diisi.',
            'status.in'       => 'Status tidak valid.',
        ]);

        TicketStateMachine::validate($installationTicket->status, $request->status);

        $installationTicket->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'data'    => $installationTicket,
        ]);
    }

    /**
     * Generate laporan bulanan ticket (Khusus Admin).
     */
    public function report(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year'  => 'required|integer',
        ]);

        $tickets = InstallationTicket::whereMonth('created_at', $request->month)
            ->whereYear('created_at', $request->year)
            ->get();

        $summary = $tickets->groupBy('status')->map->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'periode' => $request->month . '-' . $request->year,
                'summary' => $summary,
                'tickets' => $tickets
            ]
        ]);
    }
}
