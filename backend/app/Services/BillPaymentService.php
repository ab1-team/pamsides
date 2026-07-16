<?php

namespace App\Services;

use App\Models\BillPayment;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BillPaymentService
{
    private const UTANG_KODE_AKUN = '2.1.02.02';
    private const TRANSACTION_GROUP = 2;

    public function recordPayment(
        MonthlyBill $bill,
        string $accountKas,
        string $tglTransaksi,
        string $keterangan,
        ?int $penerimaKomisiId = null
    ): BillPayment {
        $now = now();
        $userId = Auth::id();

        $payment = BillPayment::create([
            'bill_id'      => $bill->id,
            'amount_paid'  => $bill->total_amount,
            'confirmed_by' => $userId,
            'paid_at'      => $now,
        ]);

        $billRelasi = optional($bill->customer?->user)->name
            ?? $bill->customer?->ticket?->applicant_name
            ?? 'Pelanggan';

        $transaksi = Transaction::create([
            'tgl_transaksi'        => $tglTransaksi,
            'account_debet'        => $accountKas,
            'account_kredit'       => self::UTANG_KODE_AKUN,
            'transaction_group'    => self::TRANSACTION_GROUP,
            'reverence_type'       => 'bill_payment',
            'reverence_id'         => $payment->id,
            'penerima_komisi_id'   => $penerimaKomisiId,
            'keterangan_transaksi' => $keterangan,
            'relasi'               => $billRelasi,
            'saldo'                => $bill->total_amount,
            'urutan'               => 1,
            'id_user'              => $userId,
        ]);
        $transaksi->update(['urutan' => $transaksi->id]);

        return $payment;
    }
}
