<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionObserver
{
    public function created(Transaction $transaction)
    {
        $this->syncAmount($transaction);
    }

    public function updated(Transaction $transaction)
    {
        $oldDate = $transaction->getOriginal('tgl_transaksi');
        $newDate = $transaction->tgl_transaksi;

        if ($oldDate != $newDate) {
            $this->syncAmountForPeriod($transaction, $oldDate);
        }
        $this->syncAmount($transaction);
    }

    public function deleted(Transaction $transaction)
    {
        $this->syncAmountForPeriod($transaction, $transaction->tgl_transaksi);
    }

    public function restored(Transaction $transaction)
    {
        $this->syncAmount($transaction);
    }

    public function forceDeleted(Transaction $transaction)
    {
        $this->syncAmountForPeriod($transaction, $transaction->tgl_transaksi);
    }

    protected function syncAmount(Transaction $transaction)
    {
        $this->syncAmountForPeriod($transaction, $transaction->tgl_transaksi);
    }

    protected function syncAmountForPeriod(Transaction $transaction, $date)
    {
        $date = Carbon::parse($date);
        $tahun = $date->year;
        $bulan = str_pad($date->month, 2, '0', STR_PAD_LEFT);

        $this->updateAmountForAccount($transaction->account_debet, $tahun, $bulan);
        $this->updateAmountForAccount($transaction->account_kredit, $tahun, $bulan);
    }

    protected function updateAmountForAccount($kodeAkun, $tahun, $bulan)
    {
        $account = DB::table('accounts')->where('kode_akun', $kodeAkun)->first();
        if (!$account) return;

        $startOfYear = "{$tahun}-01-01";
        $endOfMonth = Carbon::create($tahun, (int) $bulan, 1)->endOfMonth()->toDateString();

        $debit = DB::table('transactions')
            ->where('account_debet', $kodeAkun)
            ->whereNull('deleted_at')
            ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
            ->sum('saldo');

        $kredit = DB::table('transactions')
            ->where('account_kredit', $kodeAkun)
            ->whereNull('deleted_at')
            ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
            ->sum('saldo');

        $id = (string) $account->id . $tahun . $bulan;

        DB::table('amount')->updateOrInsert(
            ['id' => $id],
            [
                'account_id' => $account->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'debit' => $debit,
                'kredit' => $kredit,
            ]
        );
    }
}
