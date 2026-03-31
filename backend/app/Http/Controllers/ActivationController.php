<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\User;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ActivationController extends Controller
{
    public function activate(Request $request, InstallationTicket $installationTicket)
    {
        // Validasi status tiket harus processing
        TicketStateMachine::validate($installationTicket->status, 'completed');

        // Ambil data hasil instalasi dari cache
        $installationResult = cache()->get("installation_result_{$installationTicket->id}");

        if (! $installationResult) {
            return response()->json([
                'success' => false,
                'data'    => ['message' => 'Data hasil instalasi tidak ditemukan. Pastikan teknisi sudah input hasil instalasi.'],
            ], 422);
        }

        // Buat akun user untuk pelanggan
        $user = User::create([
            'name'     => $installationTicket->applicant_name,
            'email'    => Str::slug($installationTicket->applicant_name) . '_' . $installationTicket->nik . '@pelanggan.pdam',
            'password' => Hash::make($installationTicket->nik),
            'role'     => 'pelanggan',
        ]);

        // Generate customer code
        $customerCode = 'PDAM-' . strtoupper(Str::random(3)) . '-' . str_pad(Customer::count() + 1, 5, '0', STR_PAD_LEFT);

        // Buat record customer
        $customer = Customer::create([
            'ticket_id'             => $installationTicket->id,
            'user_id'               => $user->id,
            'customer_code'         => $customerCode,
            'initial_meter_reading' => $installationResult['initial_meter_reading'],
            'meter_photo_url'       => $installationResult['meter_photo_url'],
            'activated_at'          => now(),
        ]);

        // Update status tiket ke completed
        $installationTicket->update(['status' => 'completed']);

        // Hapus cache
        cache()->forget("installation_result_{$installationTicket->id}");

        return response()->json([
            'success' => true,
            'data'    => [
                'customer'      => $customer,
                'user'          => $user,
                'ticket'        => $installationTicket,
            ],
        ], 201);
    }
}
