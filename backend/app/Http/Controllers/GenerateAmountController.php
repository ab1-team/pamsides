<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateAmountController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
        ], [
            'bulan.required' => 'Bulan wajib diisi.',
            'bulan.between'  => 'Bulan harus antara 1-12.',
            'tahun.required' => 'Tahun wajib diisi.',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Hapus semua amount untuk tahun tersebut
        DB::table('amount')->where('tahun', $tahun)->delete();

        // Ambil SEMUA akun
        $accounts = DB::table('accounts')->get();

        foreach ($accounts as $account) {
            for ($m = 1; $m <= $bulan; $m++) {
                $bulanStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                $startOfYear = "{$tahun}-01-01";
                $endOfMonth = Carbon::create($tahun, $m, 1)->endOfMonth()->toDateString();

                $debit = (float) DB::table('transactions')
                    ->where('account_debet', $account->kode_akun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                    ->sum('saldo');

                $kredit = (float) DB::table('transactions')
                    ->where('account_kredit', $account->kode_akun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                    ->sum('saldo');

                $id = (string) $account->id . $tahun . $bulanStr;

                if ($debit > 0 || $kredit > 0) {
                    DB::table('amount')->updateOrInsert(
                        ['id' => $id],
                        [
                            'account_id' => $account->id,
                            'tahun' => $tahun,
                            'bulan' => $bulanStr,
                            'debit' => $debit,
                            'kredit' => $kredit,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'message' => 'Rekalibrasi amount berhasil.',
                'tahun'   => $tahun,
                'bulan'   => $bulan,
            ],
        ]);
    }
}
