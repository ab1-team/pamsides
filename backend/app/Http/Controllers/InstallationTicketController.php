<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $request->validate([
            'package_id'     => 'required|exists:installation_packages,id',
            'applicant_name' => 'required|string|max:255',
            'nik'            => 'required|string|max:20|unique:installation_tickets,nik',
            'address'        => 'required|string',
            'lat'            => 'required|numeric|between:-90,90',
            'lng'            => 'required|numeric|between:-180,180',
        ], [
            'package_id.required'     => 'Paket instalasi wajib dipilih.',
            'package_id.exists'       => 'Paket instalasi tidak ditemukan.',
            'applicant_name.required' => 'Nama pemohon wajib diisi.',
            'applicant_name.max'      => 'Nama pemohon maksimal 255 karakter.',
            'nik.required'            => 'NIK wajib diisi.',
            'nik.max'                 => 'NIK maksimal 20 karakter.',
            'nik.unique'              => 'NIK sudah terdaftar.',
            'address.required'        => 'Alamat wajib diisi.',
            'lat.required'            => 'Koordinat latitude wajib diisi.',
            'lat.between'             => 'Koordinat latitude tidak valid.',
            'lng.required'            => 'Koordinat longitude wajib diisi.',
            'lng.between'             => 'Koordinat longitude tidak valid.',
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

        return response()->json([
            'message' => 'Laporan instalasi',
            'periode' => $request->month . '-' . $request->year,
            'summary' => [
                'total' => $tickets->count(),
                'pending' => $tickets->where('status', 'pending')->count(),
                'surveyed' => $tickets->where('status', 'surveyed')->count(),
                'processing' => $tickets->where('status', 'processing')->count(),
                'completed' => $tickets->where('status', 'completed')->count(),
            ],
            'data' => $tickets
        ]);
    }
}
