<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\Models\User;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstallationTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallationTicket::with('package')
            ->orderBy('created_at', 'desc');

        // Filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Pencarian by nama atau NIK
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('applicant_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        $tickets = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $tickets,
        ]);
    }

    public function searchCandidates(Request $request)
    {
        $search = $request->search;

        // Mencari user yang sudah terdaftar sebagai pelanggan
        $users = User::with(['customer.ticket'])
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('customer.ticket', function($ticketQuery) use ($search) {
                      $ticketQuery->where('nik', 'like', "%{$search}%");
                  });
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                // Mengambil NIK dan Alamat dari pendaftaran terakhir (tiket lama)
                $lastTicket = optional($user->customer)->ticket;
                return [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'nik'     => $lastTicket ? $lastTicket->nik : null,
                    'address' => $lastTicket ? $lastTicket->address : null,
                    'lat'     => $lastTicket ? $lastTicket->lat : null,
                    'lng'     => $lastTicket ? $lastTicket->lng : null,
                ];
            })
        ]);
    }

    // REGISTER INSTALLATION
    // Menyimpan data pendaftaran baru ke tabel installation_tickets

    public function store(Request $request)
    {
        $request->validate([
            'package_id'     => 'required|exists:installation_packages,id',
            'applicant_name' => 'required|string|max:255',
            'nik'            => [
                'required',
                'string',
                'max:20',
                Rule::unique('installation_tickets')->where(function ($query) {
                    return $query->whereIn('status', ['pending', 'surveyed', 'unpaid', 'processing']);
                })
            ],
            
            'address'        => 'required|string',
            'lat'            => 'required|numeric',
            'lng'            => 'required|numeric',
        ], [
            'nik.unique' => 'Gagal! Pelanggan dengan NIK ini masih memiliki proses pendaftaran yang sedang berjalan.',
            'nik.required' => 'NIK wajib diisi.',
            'applicant_name.required' => 'Nama pemohon wajib diisi.',
        ]);

        $ticket = InstallationTicket::create([
            'package_id'     => $request->package_id,
            'applicant_name' => $request->applicant_name,
            'nik'            => $request->nik, 
            'address'        => $request->address,
            'lat'            => $request->lat,
            'lng'            => $request->lng,
            'status'         => 'pending', 
            'created_by'     => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran instalasi baru berhasil disimpan.',
            'data'    => $ticket->load('package'),
        ], 201);
    }

    public function show(InstallationTicket $installationTicket)
    {
        return response()->json([
            'success' => true,
            'data'    => $installationTicket->load([
                'package',
                'survey',
                'payments',
                'customer',
            ]),
        ]);
    }

    public function transition(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'status' => 'required|string|in:pending,surveyed,unpaid,processing,completed',
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
    public function report(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $tickets = InstallationTicket::whereMonth('created_at', $request->month)
            ->whereYear('created_at', $request->year)
            ->get();

        $summary = $tickets->groupBy('status')->map->count();

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => $request->month . '-' . $request->year,
                'summary' => $summary,
                'tickets' => $tickets
            ]
        ]);
    }
}
