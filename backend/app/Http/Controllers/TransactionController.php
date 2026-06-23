<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'accountDebet', 'accountKredit', 'reverence'])
            ->orderBy('tgl_transaksi', 'desc')
            ->orderBy('urutan');

        if ($request->has('tgl_dari')) {
            $query->whereDate('tgl_transaksi', '>=', $request->tgl_dari);
        }

        if ($request->has('tgl_sampai')) {
            $query->whereDate('tgl_transaksi', '<=', $request->tgl_sampai);
        }

        if ($request->has('account_debet')) {
            $query->where('account_debet', $request->account_debet);
        }

        if ($request->has('account_kredit')) {
            $query->where('account_kredit', $request->account_kredit);
        }

        if ($request->has('transaction_group')) {
            $query->where('transaction_group', $request->transaction_group);
        }

        if ($request->has('reverence_type')) {
            $query->where('reverence_type', $request->reverence_type);
        }

        $transactions = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_transaksi'        => 'required|date',
            'account_debet'        => 'required|string|max:10|exists:accounts,kode_akun',
            'account_kredit'       => 'required|string|max:10|exists:accounts,kode_akun',
            'transaction_group'    => 'nullable|integer',
            'reverence_type'       => 'nullable|string|in:payment,monthly_bill,customer',
            'reverence_id'         => 'nullable|integer|required_with:reverence_type',
            'keterangan_transaksi' => 'nullable|string',
            'relasi'               => 'nullable|string|max:255',
            'saldo'                => 'required|numeric',
            'urutan'               => 'nullable|integer',
        ], [
            'tgl_transaksi.required'  => 'Tanggal transaksi wajib diisi.',
            'account_debet.required'  => 'Akun debet wajib diisi.',
            'account_debet.exists'    => 'Kode akun debet tidak ditemukan.',
            'account_kredit.required' => 'Akun kredit wajib diisi.',
            'account_kredit.exists'   => 'Kode akun kredit tidak ditemukan.',
            'saldo.required'          => 'Saldo wajib diisi.',
            'reverence_id.required_with' => 'Reverence ID wajib diisi jika reverence type diisi.',
        ]);

        $transaction = Transaction::create([
            ...$request->only([
                'tgl_transaksi',
                'account_debet',
                'account_kredit',
                'transaction_group',
                'reverence_type',
                'reverence_id',
                'keterangan_transaksi',
                'relasi',
                'saldo',
                'urutan',
            ]),
            'id_user' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $transaction->load(['user', 'accountDebet', 'accountKredit']),
        ], 201);
    }

    public function show(Transaction $transaction)
    {
        return response()->json([
            'success' => true,
            'data'    => $transaction->load(['user', 'accountDebet', 'accountKredit', 'reverence']),
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'tgl_transaksi'        => 'sometimes|date',
            'account_debet'        => 'sometimes|string|max:10|exists:accounts,kode_akun',
            'account_kredit'       => 'sometimes|string|max:10|exists:accounts,kode_akun',
            'transaction_group'    => 'nullable|integer',
            'reverence_type'       => 'nullable|string|in:payment,monthly_bill,customer',
            'reverence_id'         => 'nullable|integer',
            'keterangan_transaksi' => 'nullable|string',
            'relasi'               => 'nullable|string|max:255',
            'saldo'                => 'sometimes|numeric',
            'urutan'               => 'nullable|integer',
        ]);

        $transaction->update($request->only([
            'tgl_transaksi',
            'account_debet',
            'account_kredit',
            'transaction_group',
            'reverence_type',
            'reverence_id',
            'keterangan_transaksi',
            'relasi',
            'saldo',
            'urutan',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $transaction->load(['user', 'accountDebet', 'accountKredit']),
        ]);
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'data'    => ['message' => 'Transaksi berhasil dihapus.'],
        ]);
    }
}
