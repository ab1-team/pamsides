<?php

namespace App\Observers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    public function created(Transaction $transaction)
    {
        $this->syncAmount($transaction);
    }

    public function updated(Transaction $transaction)
    {
        // Lewati sinkronisasi `amount` kalau update TIDAK menyentuh kolom yang
        // menentukan saldo (tgl_transaksi / account_debet / account_kredit).
        //
        // Contoh nyata: pola `Transaction::create([...]); $trx->update(['urutan' => $trx->id]);`
        // di MonthlyBillController::pay. Update `urutan` tidak mengubah saldo,
        // tapi tetap memicu agregasi SUM() penuh (1+ detik per call) — dan
        // trigger `update_amount_debit` MySQL juga jalan lagi.
        //
        // Dengan skip ini, observer tidak lagi agregasi penuh untuk update yang
        // tidak relevan (mis. hanya mengisi kolom `urutan`).
        $watches = ['tgl_transaksi', 'account_debet', 'account_kredit'];

        if (! $this->wasChangedAmong($transaction, $watches)) {
            return;
        }

        $oldDate = $transaction->getOriginal('tgl_transaksi');
        $newDate = $transaction->tgl_transaksi;

        if ($oldDate != $newDate) {
            $this->syncAmountForPeriod($transaction, $oldDate);
        }
        $this->syncAmount($transaction);
    }

    /**
     * True kalau update ini mengubah salah satu kolom yang dipantau.
     *
     * Pakai perbandingan nilai LAMA vs BARU (bukan `wasChanged`) supaya aman
     * dipanggil baik setelah `save()` maupun setelah refresh attribute.
     */
    protected function wasChangedAmong(Transaction $transaction, array $keys): bool
    {
        foreach ($keys as $key) {
            $before = $transaction->getOriginal($key);
            $after = $transaction->getAttribute($key);

            // Bandingkan sebagai tanggal agar '2026-10-06' vs Carbon(2026-10-06)
            // dianggap sama (tgl_transaksi di-cast jadi date).
            if ($key === 'tgl_transaksi') {
                if (! $this->sameDate($before, $after)) {
                    return true;
                }

                continue;
            }

            if ((string) $before !== (string) $after) {
                return true;
            }
        }

        return false;
    }

    protected function sameDate($before, $after): bool
    {
        if ($before === null && $after === null) {
            return true;
        }
        if ($before === null || $after === null) {
            return false;
        }

        try {
            return Carbon::parse($before)->toDateString() === Carbon::parse($after)->toDateString();
        } catch (\Throwable $e) {
            return (string) $before === (string) $after;
        }
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

        $this->invalidateJurnalCache($tahun, $bulan);
    }

    protected function invalidateJurnalCache(int $tahun, string $bulan): void
    {
        $key = "jurnal_transaksi:{$tahun}:{$bulan}";
        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
        }
    }

    protected function updateAmountForAccount($kodeAkun, $tahun, $bulan)
    {
        $account = DB::table('accounts')->where('kode_akun', $kodeAkun)->first();
        if (! $account) {
            return;
        }

        $startOfYear = "{$tahun}-01-01";
        $endOfMonth = Carbon::create($tahun, (int) $bulan, 1)->endOfMonth()->toDateString();

        $row = DB::table('transactions')
            ->selectRaw('COALESCE(SUM(CASE WHEN account_debet = ? THEN saldo ELSE 0 END), 0) as debit', [$kodeAkun])
            ->selectRaw('COALESCE(SUM(CASE WHEN account_kredit = ? THEN saldo ELSE 0 END), 0) as kredit', [$kodeAkun])
            ->whereNull('deleted_at')
            ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
            ->where(function ($q) use ($kodeAkun) {
                $q->where('account_debet', $kodeAkun)
                    ->orWhere('account_kredit', $kodeAkun);
            })
            ->first();

        $id = (string) $account->id.$tahun.$bulan;

        DB::table('amount')->updateOrInsert(
            ['id' => $id],
            [
                'account_id' => $account->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'debit' => $row->debit ?? 0,
                'kredit' => $row->kredit ?? 0,
            ]
        );
    }
}
