<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->createTransactionIfConfirmed($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('status') && $payment->status === 'confirmed') {
            $exists = Transaction::where('reverence_type', 'payment')
                ->where('reverence_id', $payment->id)
                ->exists();

            if (! $exists) {
                $this->createTransactionIfConfirmed($payment);
            }
        }
    }

    private function createTransactionIfConfirmed(Payment $payment): void
    {
        if ($payment->status !== 'confirmed') {
            return;
        }

        if ((float) $payment->amount <= 0) {
            return;
        }

        if (! $payment->ticket_id) {
            return;
        }

        $exists = Transaction::where('reverence_type', 'payment')
            ->where('reverence_id', $payment->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $jenisTransaction = \App\Models\JenisTransaction::where('nama_jt', 'like', '%pasang baru%')
                ->orWhere('nama_jt', 'like', '%instalasi%')
                ->first();

            $transaction = Transaction::create([
                'tgl_transaksi'        => $payment->paid_at ?: now(),
                'account_debet'        => '1.1.01.01',
                'account_kredit'       => '4.1.01.01',
                'transaction_group'    => $jenisTransaction?->id,
                'reverence_type'       => 'payment',
                'reverence_id'         => $payment->id,
                'keterangan_transaksi' => 'Pembayaran pasang baru - ' . ($payment->ticket?->applicant_name ?? 'Tiket #' . $payment->ticket_id),
                'relasi'               => $payment->ticket?->applicant_name ?? 'Tiket #' . $payment->ticket_id,
                'saldo'                => (float) $payment->amount,
                'id_user'              => $payment->confirmed_by ?: $payment->ticket?->created_by,
            ]);

            $transaction->update(['urutan' => $transaction->id]);
        });
    }
}
