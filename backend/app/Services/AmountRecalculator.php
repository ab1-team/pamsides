<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Hitung ulang tabel `amount` (rekapitulasi bulanan per akun) dari
 * tabel `transactions`.
 *
 * Definisi (dipakai TransactionObserver & PelaporanController):
 *   amount.debit  = SUM(saldo) untuk transaksi di mana akun = account_debet
 *                   pada periode (tahun, bulan) tersebut
 *   amount.kredit = SUM(saldo) untuk transaksi di mana akun = account_kredit
 *
 * id = account_id . tahun . bulan (bulan 2 digit, tanpa separator)
 *
 * CATATAN: nilainya PER BULAN, bukan kumulatif. Laporan AJL memakai
 * `where('bulan','<=',$bulan)` lalu menjumlahkan sendiri
 * (lihat PelaporanController::LPM), jadi kumulatif di sini akan
 * menghitung dobel.
 */
class AmountRecalculator
{
    /**
     * @return array<string,array{account_id:int,tahun:string,bulan:string,debit:float,kredit:float}>
     */
    public static function compute(): array
    {
        $N = DB::connection();

        $accIdByCode = $N->table('accounts')->pluck('id', 'kode_akun')->all();
        $accCodeById = $N->table('accounts')->pluck('kode_akun', 'id')->all();

        $rows = [];

        $add = function (string $kode, int $year, int $month, float $sum) use (&$rows, $accIdByCode) {
            if (! isset($accIdByCode[$kode])) {
                return;
            }
            if ($sum === 0.0) {
                return;
            }
            $accountId = (int) $accIdByCode[$kode];
            $tahun = (string) $year;
            $bulan = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
            $key = $accountId . $tahun . $bulan;
            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'id' => (int) $key,
                    'account_id' => $accountId,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'debit' => 0.0,
                    'kredit' => 0.0,
                ];
            }
            $rows[$key]['debit'] += $sum;
        };

        // Debit
        foreach ($N->select("select account_debet kode,
                                   year(tgl_transaksi) y,
                                   month(tgl_transaksi) m,
                                   sum(saldo) s
                            from transactions
                            where deleted_at is null and account_debet is not null
                            group by account_debet, y, m") as $r) {
            $add((string) $r->kode, (int) $r->y, (int) $r->m, (float) $r->s);
        }

        // Kredit
        foreach ($N->select("select account_kredit kode,
                                   year(tgl_transaksi) y,
                                   month(tgl_transaksi) m,
                                   sum(saldo) s
                            from transactions
                            where deleted_at is null and account_kredit is not null
                            group by account_kredit, y, m") as $r) {
            $kode = (string) $r->kode;
            if (! isset($accIdByCode[$kode])) {
                continue;
            }
            $accountId = (int) $accIdByCode[$kode];
            $tahun = (string) $r->y;
            $bulan = str_pad((string) $r->m, 2, '0', STR_PAD_LEFT);
            $key = $accountId . $tahun . $bulan;
            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'id' => (int) $key,
                    'account_id' => $accountId,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'debit' => 0.0,
                    'kredit' => 0.0,
                ];
            }
            $rows[$key]['kredit'] += (float) $r->s;
        }

        // Bulat 2 desimal & buang row yang 0-0
        foreach ($rows as $k => $row) {
            $rows[$k]['debit'] = round($row['debit'], 2);
            $rows[$k]['kredit'] = round($row['kredit'], 2);
        }

        return array_filter($rows, fn ($r) => abs($r['debit']) >= 0.005 || abs($r['kredit']) >= 0.005);
    }
}