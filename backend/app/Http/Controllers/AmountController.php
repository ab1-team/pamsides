<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    public function accountsWithSaldo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'bulan' => 'nullable|string|size:2',
            'prefix' => 'nullable|string|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $tahun = $request->query('tahun') ?: now()->year;
        $bulan = $request->query('bulan') ?: str_pad((string) now()->month, 2, '0', STR_PAD_LEFT);

        $rawPrefix = (string) $request->query('prefix', '4,5');
        $prefixes  = collect(explode(',', $rawPrefix))
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values()
            ->all();

        $query = DB::table('accounts as a')
            ->leftJoin('amount as am', function ($j) use ($tahun, $bulan) {
                $j->on('am.account_id', '=', 'a.id')
                  ->where('am.tahun', '=', $tahun)
                  ->where('am.bulan', '=', $bulan);
            })
            ->select(
                'a.id as account_id',
                'a.kode_akun',
                'a.nama_akun',
                'a.lev1',
                DB::raw('COALESCE(am.debit, 0) as debit'),
                DB::raw('COALESCE(am.kredit, 0) as kredit')
            );

        if (! empty($prefixes)) {
            $query->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $p) {
                    $q->orWhere('a.kode_akun', 'like', $p . '.%');
                }
            });
        }

        $rows = $query->orderBy('a.kode_akun')->get();

        $items = [];
        $totalSeluruh = 0;

        foreach ($rows as $r) {
            $debit  = (float) $r->debit;
            $kredit = (float) $r->kredit;
            $lev1   = (int) $r->lev1;

            if ($lev1 === 1 || $lev1 === 5) {
                $saldo = $debit - $kredit;
            } else {
                $saldo = $kredit - $debit;
            }

            $totalSeluruh += $saldo;

            $items[] = [
                'account_id' => (int) $r->account_id,
                'kode_akun'  => $r->kode_akun,
                'nama_akun'  => $r->nama_akun,
                'saldo'      => $saldo,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'  => (int) $tahun,
                'bulan'  => $bulan,
                'total'  => $totalSeluruh,
                'items'  => $items,
            ],
        ]);
    }
}
