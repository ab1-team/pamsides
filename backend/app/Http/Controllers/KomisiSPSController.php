<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KomisiSPSController extends Controller
{
    private const CASH_KODE_AKUN = ['1.1.01.01', '1.1.01.02', '1.1.01.03', '1.1.01.05', '1.1.01.06', '1.1.01.07'];
    private const UTANG_KODE_AKUN = '4.1.01.03';
    private const TRANSACTION_GROUP = 2;

    public function cashAccounts()
    {
        $accounts = Account::whereIn('kode_akun', self::CASH_KODE_AKUN)
            ->whereNull('tgl_nonaktif')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        return response()->json([
            'success' => true,
            'data'    => $accounts,
        ]);
    }

    public function unpaidByCustomer(Request $request)
    {
        $customerId = $request->query('customer_id');
        if (! $customerId) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'customer'      => null,
                    'total_unpaid'  => 0,
                    'bill_count'    => 0,
                    'bills'         => [],
                ],
            ]);
        }

        $customer = Customer::with('user', 'ticket.package', 'ticket.village')->find($customerId);
        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak ditemukan.',
            ], 404);
        }

        $bills = MonthlyBill::where('customer_id', $customerId)
            ->where('status', 'unpaid')
            ->orderBy('billing_period_year')
            ->orderBy('billing_period_month')
            ->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month', 'total_amount', 'penalty_amount', 'due_date', 'status']);

        return response()->json([
            'success' => true,
            'data'    => [
                'customer'     => [
                    'id'            => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'nama'          => optional($customer->user)->name ?? $customer->ticket?->applicant_name,
                    'nik'           => $customer->ticket?->nik,
                    'alamat'        => $customer->ticket?->address,
                    'package_name'  => $customer->ticket?->package?->name,
                ],
                'total_unpaid' => (float) $bills->sum('total_amount'),
                'bill_count'   => $bills->count(),
                'bills'        => $bills,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_transaksi'     => 'required|date',
            'customer_id'       => 'required|integer|exists:customers,id',
            'account_kas'       => 'required|string|in:' . implode(',', self::CASH_KODE_AKUN),
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'tgl_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'customer_id.required'   => 'Pelanggan wajib dipilih.',
            'customer_id.exists'     => 'Pelanggan tidak ditemukan.',
            'account_kas.required'   => 'Metode pembayaran wajib dipilih.',
            'account_kas.in'         => 'Metode pembayaran tidak valid.',
        ]);

        $customerId = $request->customer_id;
        $bills = MonthlyBill::where('customer_id', $customerId)
            ->where('status', 'unpaid')
            ->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tagihan unpaid untuk pelanggan ini.',
            ], 422);
        }

        $totalNominal = (float) $bills->sum('total_amount');
        if ($totalNominal <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Total tagihan pelanggan nol.',
            ], 422);
        }

        $customer = Customer::with('user', 'ticket')->find($customerId);
        $relasi = optional($customer->user)->name ?? $customer->ticket?->applicant_name ?? 'Pelanggan';

        DB::transaction(function () use ($request, $bills, $totalNominal, $relasi) {
            $now = now();
            $billIds = $bills->pluck('id')->all();

            MonthlyBill::whereIn('id', $billIds)->update(['status' => 'paid']);

            foreach ($billIds as $bid) {
                $bill = MonthlyBill::find($bid);
                if (! $bill) {
                    continue;
                }
                BillPayment::create([
                    'bill_id'      => $bill->id,
                    'amount_paid'  => $bill->total_amount,
                    'confirmed_by' => Auth::id(),
                    'paid_at'      => $now,
                ]);
            }

            $transaction = Transaction::create([
                'tgl_transaksi'        => Carbon::parse($request->tgl_transaksi)->toDateString(),
                'account_debet'        => $request->account_kas,
                'account_kredit'       => self::UTANG_KODE_AKUN,
                'transaction_group'    => self::TRANSACTION_GROUP,
                'reverence_type'       => 'customer',
                'reverence_id'         => $request->customer_id,
                'keterangan_transaksi' => $request->keterangan ?: 'Pelunasan komisi SPS - ' . $relasi,
                'relasi'               => $relasi,
                'saldo'                => $totalNominal,
                'urutan'               => 1,
                'id_user'              => Auth::id(),
            ]);

            $transaction->update(['urutan' => $transaction->id]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi komisi SPS berhasil disimpan.',
            'data'    => [
                'relasi'        => $relasi,
                'customer_id'   => $request->customer_id,
                'total_nominal' => $totalNominal,
                'bill_count'    => $bills->count(),
                'bill_ids'      => $bills->pluck('id')->all(),
                'account_kas'   => $request->account_kas,
                'account_utang' => self::UTANG_KODE_AKUN,
            ],
        ], 201);
    }
}
