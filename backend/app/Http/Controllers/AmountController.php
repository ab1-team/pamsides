<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmountController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'bulan'    => 'required|string|size:2',
            'tahun'    => 'required|string|min:4|max:10',
            'account_id' => 'required|integer|exists:accounts,id',
        ], [
            'bulan.required'    => 'Bulan wajib diisi.',
            'bulan.size'        => 'Format bulan harus 2 digit (01-12).',
            'tahun.required'    => 'Tahun wajib diisi.',
            'account_id.required' => 'Account ID wajib diisi.',
            'account_id.exists'   => 'Account tidak ditemukan.',
        ]);

        $amount = DB::table('amount')
            ->where('account_id', $request->account_id)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->first();

        if (! $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Data amount tidak ditemukan.',
                'data'    => [
                    'account_id' => (int) $request->account_id,
                    'bulan'      => $request->bulan,
                    'tahun'      => $request->tahun,
                    'debit'      => 0,
                    'kredit'     => 0,
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => (int) $amount->id,
                'account_id'  => (int) $amount->account_id,
                'bulan'       => $amount->bulan,
                'tahun'       => $amount->tahun,
                'debit'       => (float) $amount->debit,
                'kredit'      => (float) $amount->kredit,
            ],
        ]);
    }

    public function getTotalSaldo(Request $request)
    {
        $accounts = DB::table('accounts')->select('id', 'kode_akun', 'nama_akun', 'lev1')->get();

        $totalAset = 0;
        $totalKewajiban = 0;
        $totalModal = 0;
        $totalPendapatan = 0;
        $totalBeban = 0;
        $detailSaldo = [];

        foreach ($accounts as $account) {
            // Ambil amount terbaru per akun (bulan & tahun terbesar)
            $amount = DB::table('amount')
                ->where('account_id', $account->id)
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->first();

            $debit  = $amount ? (float) $amount->debit : 0;
            $kredit = $amount ? (float) $amount->kredit : 0;

            $lev1 = (int) $account->lev1;
            $saldo = 0;

            if ($lev1 === 1) {
                $saldo = $debit - $kredit;
                $totalAset += $saldo;
            } elseif ($lev1 === 2) {
                $saldo = $kredit - $debit;
                $totalKewajiban += $saldo;
            } elseif ($lev1 === 3) {
                $saldo = $kredit - $debit;
                $totalModal += $saldo;
            } elseif ($lev1 === 4) {
                $saldo = $kredit - $debit;
                $totalPendapatan += $saldo;
            } elseif ($lev1 === 5) {
                $saldo = $debit - $kredit;
                $totalBeban += $saldo;
            }

            $detailSaldo[] = [
                'account_id' => $account->id,
                'kode_akun'  => $account->kode_akun,
                'nama_akun'  => $account->nama_akun,
                'debit'      => $debit,
                'kredit'     => $kredit,
                'saldo'      => $saldo,
            ];
        }

        $saldoBersih = $totalAset - $totalKewajiban;

        return response()->json([
            'success' => true,
            'data' => [
                'saldo'  => $saldoBersih,
                'aset'   => $totalAset,
                'kewajiban' => $totalKewajiban,
                'modal'  => $totalModal,
                'pendapatan' => $totalPendapatan,
                'beban'  => $totalBeban,
                'detail' => $detailSaldo,
            ],
        ]);
    }
}
