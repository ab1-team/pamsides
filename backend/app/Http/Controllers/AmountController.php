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
}
