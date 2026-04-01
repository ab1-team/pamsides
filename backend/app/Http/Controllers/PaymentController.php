<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
use App\Models\Payment;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ], [
            'amount.required' => 'Jumlah pembayaran wajib diisi.',
            'amount.numeric'  => 'Jumlah pembayaran harus berupa angka.',
            'amount.min'      => 'Jumlah pembayaran tidak boleh negatif.',
        ]);

        // Validasi status tiket harus surveyed
        TicketStateMachine::validate($installationTicket->status, 'unpaid');

        // Buat record pembayaran
        $payment = Payment::create([
            'ticket_id'    => $installationTicket->id,
            'amount'       => $request->amount,
            'type'         => 'installation_fee',
            'status'       => 'confirmed',
            'confirmed_by' => $request->user()->id,
            'paid_at'      => now(),
        ]);

        // Update status tiket ke unpaid lalu processing
        $installationTicket->update(['status' => 'unpaid']);
        $installationTicket->update(['status' => 'processing']);

        return response()->json([
            'success' => true,
            'data'    => $payment->load('ticket'),
        ], 201);
    }
}
