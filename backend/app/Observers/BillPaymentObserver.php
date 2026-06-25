<?php

namespace App\Observers;

use App\Models\BillPayment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BillPaymentObserver
{
    public function created(BillPayment $billPayment): void
    {
        $this->createTransactions($billPayment);
    }

    private function createTransactions(BillPayment $billPayment): void
    {
        if ((float) $billPayment->amount_paid <= 0) {
            return;
        }

        if (! $billPayment->bill_id) {
            return;
        }

        $bill = $billPayment->bill;
        if (! $bill) {
            return;
        }

        $abodemen = (float) ($bill->abodemen ?? 0);
        $denda = (float) ($bill->penalty_amount ?? 0);

        DB::transaction(function () use ($billPayment, $bill, $abodemen, $denda) {
            // Transaksi abodemen
            if ($abodemen > 0) {
                $trx = Transaction::create([
                    'tgl_transaksi'        => $billPayment->paid_at ?: now(),
                    'account_debet'        => '1.1.01.01',
                    'account_kredit'       => '4.1.01.02',
                    'transaction_group'    => null,
                    'reverence_type'       => 'bill_payment',
                    'reverence_id'         => $billPayment->id,
                    'keterangan_transaksi' => 'Abodemen - ' . ($bill->customer?->customer_code ?? 'Bill #' . $bill->id),
                    'relasi'               => $bill->customer?->customer_code ?? 'Bill #' . $bill->id,
                    'saldo'                => $abodemen,
                    'id_user'              => $billPayment->confirmed_by,
                ]);
                $trx->update(['urutan' => $trx->id]);
            }

            // Transaksi denda (jika ada)
            if ($denda > 0) {
                $trx = Transaction::create([
                    'tgl_transaksi'        => $billPayment->paid_at ?: now(),
                    'account_debet'        => '1.1.01.01',
                    'account_kredit'       => '4.1.01.04',
                    'transaction_group'    => null,
                    'reverence_type'       => 'bill_payment',
                    'reverence_id'         => $billPayment->id,
                    'keterangan_transaksi' => 'Denda - ' . ($bill->customer?->customer_code ?? 'Bill #' . $bill->id),
                    'relasi'               => $bill->customer?->customer_code ?? 'Bill #' . $bill->id,
                    'saldo'                => $denda,
                    'id_user'              => $billPayment->confirmed_by,
                ]);
                $trx->update(['urutan' => $trx->id]);
            }
        });
    }
}
