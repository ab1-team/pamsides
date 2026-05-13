<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

class InstallationTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallationTicket::with(['package', 'survey'])
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

    /**
     * Melengkapi registrasi instalasi (Update dari draft menjadi pending).
     */
    public function registerInstallation(Request $request, $id)
    {
        $request->validate([
            'package_id' => 'required|exists:installation_packages,id',
            'lat'        => 'required|numeric|between:-90,90',
            'lng'        => 'required|numeric|between:-180,180',
        ], [
            'package_id.required' => 'Paket instalasi wajib dipilih.',
            'package_id.exists'   => 'Paket instalasi tidak ditemukan.',
            'lat.required'        => 'Koordinat latitude wajib diisi.',
            'lat.between'         => 'Koordinat latitude tidak valid.',
            'lng.required'        => 'Koordinat longitude wajib diisi.',
            'lng.between'         => 'Koordinat longitude tidak valid.',
        ]);

        try {
            $ticket = InstallationTicket::findOrFail($id);

            // Melengkapi data draft dan mengubah status menjadi pending
            $ticket->update([
                'package_id' => $request->package_id,
                'lat'        => $request->lat,
                'lng'        => $request->lng,
                'status'     => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi instalasi berhasil dilengkapi dan status menjadi pending.',
                'data'    => $ticket->load('package'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: ' . $e->getMessage(),
            ], 500);
        }
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
