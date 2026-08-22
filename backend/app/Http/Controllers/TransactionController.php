<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private function normalisasiSaldo(int $lev1, float $debit, float $kredit): float
    {
        return ($lev1 === 1 || $lev1 === 5) ? $debit - $kredit : $kredit - $debit;
    }

    public function saldoAkun(Request $request)
    {
        $kodeAkun = $request->query('kode_akun');
        if (! $kodeAkun) {
            return response()->json([
                'success' => false,
                'message' => 'kode_akun wajib diisi.',
                'data'    => ['saldo' => 0],
            ], 422);
        }

        $account = DB::table('accounts')->where('kode_akun', $kodeAkun)->first();
        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
                'data'    => ['saldo' => 0],
            ], 404);
        }

        $debit = (float) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('account_debet', $kodeAkun)
            ->sum('saldo');

        $kredit = (float) DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('account_kredit', $kodeAkun)
            ->sum('saldo');

        return response()->json([
            'success' => true,
            'data'    => [
                'kode_akun' => $kodeAkun,
                'nama_akun' => $account->nama_akun,
                'lev1'      => (int) $account->lev1,
                'debit'     => $debit,
                'kredit'    => $kredit,
                'saldo'     => $this->normalisasiSaldo((int) $account->lev1, $debit, $kredit),
            ],
        ]);
    }

    public function bukuBesar(Request $request)
    {
        $kodeAkun = $request->query('kode_akun');
        $tahun    = (int) $request->query('tahun', date('Y'));

        if (! $kodeAkun) {
            return response()->json([
                'success' => false,
                'message' => 'kode_akun wajib diisi.',
            ], 422);
        }

        $account = DB::table('accounts')->where('kode_akun', $kodeAkun)->first();
        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        $lev1 = (int) $account->lev1;

        $sumRange = function (string $endDate) use ($kodeAkun) {
            $debit = (float) DB::table('transactions')
                ->whereNull('deleted_at')
                ->where('account_debet', $kodeAkun)
                ->whereDate('tgl_transaksi', '<', $endDate)
                ->sum('saldo');
            $kredit = (float) DB::table('transactions')
                ->whereNull('deleted_at')
                ->where('account_kredit', $kodeAkun)
                ->whereDate('tgl_transaksi', '<', $endDate)
                ->sum('saldo');
            return [$debit, $kredit];
        };

        [$dAwal, $kAwal] = $sumRange("{$tahun}-01-01");
        $saldoAwalTahun = $this->normalisasiSaldo($lev1, $dAwal, $kAwal);

        $bulan     = $request->query('bulan');
        $tanggal   = $request->query('tanggal');
        $tglDari   = "{$tahun}-01-01";
        $tglSampai = "{$tahun}-12-31";

        if ($bulan) {
            $bulanStr = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
            $lastDay = (int) date('t', strtotime("{$tahun}-{$bulanStr}-01"));
            $tglDari   = "{$tahun}-{$bulanStr}-01";
            $tglSampai = "{$tahun}-{$bulanStr}-" . str_pad((string) $lastDay, 2, '0', STR_PAD_LEFT);

            if ($tanggal) {
                $tglStr = str_pad((string) $tanggal, 2, '0', STR_PAD_LEFT);
                $tglDari = $tglSampai = "{$tahun}-{$bulanStr}-{$tglStr}";
            }

            [$dBulan, $kBulan] = $sumRange($tglDari);
            $saldoAwalBulan = $this->normalisasiSaldo($lev1, $dBulan, $kBulan);
        } else {
            $saldoAwalBulan = null;
        }

        $trx = Transaction::with(['accountDebet', 'accountKredit'])
            ->where(function ($q) use ($kodeAkun) {
                $q->where('account_debet', $kodeAkun)
                  ->orWhere('account_kredit', $kodeAkun);
            })
            ->whereDate('tgl_transaksi', '>=', $tglDari)
            ->whereDate('tgl_transaksi', '<=', $tglSampai)
            ->orderBy('tgl_transaksi')
            ->orderBy('urutan')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'kode_akun'         => $kodeAkun,
                'nama_akun'         => $account->nama_akun,
                'lev1'              => $lev1,
                'tahun'             => $tahun,
                'bulan'             => $bulan ? str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : null,
                'saldo_awal_tahun'  => $saldoAwalTahun,
                'saldo_awal_bulan'  => $saldoAwalBulan,
                'transactions'      => $trx,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'accountDebet', 'accountKredit'])
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

        if ($request->has('account')) {
            $query->where(function ($query) use ($request) {
                $query->where('account_debet', $request->account)
                    ->orWhere('account_kredit', $request->account);
            });
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
            'data'    => $transaction->load(['user', 'accountDebet', 'accountKredit']),
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
