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

        if (! in_array($installationTicket->status, ['pending', 'surveyed', 'unpaid'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Pembayaran hanya dapat dilakukan untuk tiket berstatus 'pending', 'surveyed', atau 'unpaid'. Status saat ini: '{$installationTicket->status}'.",
            ], 422);
        }

        $totalFee     = (float) ($installationTicket->package?->installation_fee ?? 0);
        $confirmedPaid = (float) $installationTicket->payments()
            ->where('status', 'confirmed')
            ->sum('amount');
        $pendingPaid   = (float) $installationTicket->payments()
            ->where('status', 'pending')
            ->sum('amount');
        $alreadyPaid   = $confirmedPaid + $pendingPaid;
        $remaining     = max(0, $totalFee - $alreadyPaid);

        if ($remaining <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan instalasi ini sudah lunas.',
            ], 422);
        }

        $amount = (float) $request->amount;
        if ($amount > $remaining) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran melebihi sisa tagihan. Sisa tagihan: Rp '.number_format($remaining, 0, ',', '.'),
            ], 422);
        }

        $isFullyPaid = $amount >= $remaining;
        $userId      = $request->user()->id;

        if ($isFullyPaid) {
            $installationTicket->payments()
                ->where('status', 'pending')
                ->update([
                    'status'       => 'confirmed',
                    'confirmed_by' => $userId,
                    'paid_at'      => now(),
                    'updated_at'   => now(),
                ]);

            $payment = Payment::create([
                'ticket_id'    => $installationTicket->id,
                'amount'       => $amount,
                'type'         => 'installation_fee',
                'status'       => 'confirmed',
                'confirmed_by' => $userId,
                'paid_at'      => now(),
            ]);
        } else {
            $payment = Payment::create([
                'ticket_id'    => $installationTicket->id,
                'amount'       => $amount,
                'type'         => 'installation_fee',
                'status'       => 'pending',
                'confirmed_by' => null,
                'paid_at'      => null,
            ]);
        }

        $installationTicket->refresh();
        $currentStatus = $installationTicket->status;
        if ($isFullyPaid && in_array($currentStatus, ['pending', 'surveyed', 'unpaid'], true)) {
            TicketStateMachine::validate($currentStatus, 'processing');
            $installationTicket->update(['status' => 'processing']);
        } elseif (! $isFullyPaid && in_array($currentStatus, ['pending', 'surveyed'], true)) {
            TicketStateMachine::validate($currentStatus, 'unpaid');
            $installationTicket->update(['status' => 'unpaid']);
        }

        $fresh = $installationTicket->fresh();
        $newConfirmedTotal = (float) $fresh->payments()->where('status', 'confirmed')->sum('amount');
        $newPendingTotal   = (float) $fresh->payments()->where('status', 'pending')->sum('amount');
        $newRemaining = max(0, $totalFee - $newConfirmedTotal - $newPendingTotal);

        return response()->json([
            'success' => true,
            'message' => $isFullyPaid
                ? 'Pelunasan berhasil. Pembayaran lunas akan diverifikasi saat survey.'
                : 'Pembayaran sebagian berhasil (status: pending). Sisa tagihan: Rp '.number_format($newRemaining, 0, ',', '.'),
            'data'    => [
                'payment'     => $payment->load('ticket'),
                'total_fee'   => $totalFee,
                'paid'        => $newConfirmedTotal,
                'pending'     => $newPendingTotal,
                'total_paid'  => $newConfirmedTotal + $newPendingTotal,
                'remaining'   => $newRemaining,
                'is_paid_off' => $isFullyPaid,
                'ticket'      => $fresh->load(['package', 'payments']),
            ],
        ], 201);
    }
}
