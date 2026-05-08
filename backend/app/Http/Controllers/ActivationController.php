<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivationController extends Controller
{
    public function activate(Request $request, InstallationTicket $installationTicket)
    {
        // 1. Validasi status tiket dari pending/processing ke completed
        TicketStateMachine::validate($installationTicket->status, 'completed');

        // 2. Ambil data hasil instalasi dari cache
        $installationResult = cache()->get("installation_result_{$installationTicket->id}");

        if (! $installationResult) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'Data hasil instalasi tidak ditemukan. Pastikan teknisi sudah input hasil instalasi.'],
            ], 422);
        }

        // 3. Gunakan user yang sudah ada saat registrasi awal
        $user = $installationTicket->user; // Relasi ke User

        // 4. Buat customer_code dengan format yang seragam
        $customerCode = 'PAM-' . date('Ym') . '-' . Str::padLeft($user->id, 4, '0');

        // 5. Buat record customer
        $customer = Customer::updateOrCreate(
            ['ticket_id' => $installationTicket->id, 'user_id' => $user->id],
            [
                'customer_code'         => $customerCode,
                'initial_meter_reading' => $installationResult['initial_meter_reading'] ?? 0,
                'meter_photo_url'       => $installationResult['meter_photo_url'] ?? null,
                'activated_at'          => now(),
            ]
        );

        // 6. Update status tiket ke completed
        $installationTicket->update(['status' => 'completed']);

        // 7. Hapus cache setelah selesai
        cache()->forget("installation_result_{$installationTicket->id}");

        return response()->json([
            'success' => true,
            'message' => 'Aktivasi pelanggan berhasil. Kode pelanggan telah dibuat.',
            'data'    => [
                'customer' => $customer,
                'user'     => $user,
                'ticket'   => $installationTicket,
            ],
        ], 200);
    }
}
