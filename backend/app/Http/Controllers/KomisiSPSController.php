<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BillPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KomisiSPSController extends Controller
{
    private const CASH_KODE_AKUN = ['1.1.01.01', '1.1.01.02', '1.1.01.03', '1.1.01.05', '1.1.01.06', '1.1.01.07'];
    private const UTANG_KODE_AKUN = '2.1.02.02';
    private const BEBAN_KODE_AKUN = '5.1.02.04';
    private const TRANSACTION_GROUP = 2;
    private const KOMISI_RATE = 0.1;

    public function __construct(protected BillPaymentService $billPaymentService) {}

    public function cashAccounts()
    {
        $accounts = Account::whereIn('kode_akun', self::CASH_KODE_AKUN)
            ->whereNull('tgl_nonaktif')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        return response()->json([
            'success' => true,
            'data'    => $accounts,
            'rate'    => self::KOMISI_RATE,
        ]);
    }

    public function penerimaKomisi(Request $request)
    {
        $defaultTeknisi = User::where('role', 'teknisi')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $others = collect();
        if ($request->boolean('include_all')) {
            $others = User::where('role', '!=', 'teknisi')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role']);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'default' => $defaultTeknisi,
                'others'  => $others,
                'rate'    => self::KOMISI_RATE,
            ],
        ]);
    }

    public function pelangganWithUnpaid(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Customer::query()
            ->whereHas('monthlyBills', function ($sub) {
                $sub->where('status', 'unpaid');
            })
            ->with(['user', 'ticket']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('customer_code', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('ticket', function ($t) use ($q) {
                        $t->where('applicant_name', 'like', "%{$q}%")
                          ->orWhere('nik', 'like', "%{$q}%");
                    });
            });
        }

        $customers = $query
            ->orderBy('customer_code')
            ->limit(50)
            ->get();

        $items = $customers->map(function ($c) {
            $unpaidTotal = (float) $c->monthlyBills()
                ->where('status', 'unpaid')
                ->sum('total_amount');

            return [
                'id'             => $c->id,
                'customer_code'  => $c->customer_code,
                'customerId'     => $c->id,
                'nama'           => optional($c->user)->name ?? $c->ticket?->applicant_name,
                'name'           => optional($c->user)->name ?? $c->ticket?->applicant_name,
                'nik'            => $c->ticket?->nik,
                'total_unpaid'   => $unpaidTotal,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'rate'    => self::KOMISI_RATE,
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
                    'nominal_komisi' => 0,
                    'rate'          => self::KOMISI_RATE,
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

        $totalUnpaid   = (float) $bills->sum('total_amount');
        $nominalKomisi = round($totalUnpaid * self::KOMISI_RATE, 2);

        return response()->json([
            'success' => true,
            'data'    => [
                'customer'        => [
                    'id'            => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'nama'          => optional($customer->user)->name ?? $customer->ticket?->applicant_name,
                    'nik'           => $customer->ticket?->nik,
                    'alamat'        => $customer->ticket?->address,
                    'package_name'  => $customer->ticket?->package?->name,
                ],
                'total_unpaid'    => $totalUnpaid,
                'nominal_komisi'  => $nominalKomisi,
                'bill_count'      => $bills->count(),
                'bills'           => $bills,
                'rate'            => self::KOMISI_RATE,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_transaksi'      => 'required|date',
            'customer_ids'       => 'required|array|min:1',
            'customer_ids.*'     => 'integer|exists:customers,id',
            'account_kas'        => 'required|string|in:' . implode(',', self::CASH_KODE_AKUN),
            'penerima_komisi_id' => 'required|integer|exists:users,id',
            'keterangan'         => 'nullable|string|max:255',
        ], [
            'tgl_transaksi.required'      => 'Tanggal transaksi wajib diisi.',
            'customer_ids.required'       => 'Pelanggan wajib dipilih.',
            'customer_ids.array'          => 'Pelanggan tidak valid.',
            'customer_ids.min'            => 'Minimal pilih 1 pelanggan.',
            'customer_ids.*.exists'       => 'Pelanggan tidak ditemukan.',
            'account_kas.required'        => 'Metode pembayaran wajib dipilih.',
            'account_kas.in'              => 'Metode pembayaran tidak valid.',
            'penerima_komisi_id.required' => 'Penerima komisi wajib dipilih.',
            'penerima_komisi_id.exists'   => 'Penerima komisi tidak ditemukan.',
        ]);

        $customerIds = array_values(array_unique(array_map('intval', $request->customer_ids)));

        $bills = MonthlyBill::with('customer.user', 'customer.ticket')
            ->whereIn('customer_id', $customerIds)
            ->where('status', 'unpaid')
            ->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada tagihan unpaid untuk pelanggan yang dipilih.',
            ], 422);
        }

        $totalUnpaid = (float) $bills->sum('total_amount');
        if ($totalUnpaid <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Total tagihan pelanggan nol.',
            ], 422);
        }

        $nominalKomisi = round($totalUnpaid * self::KOMISI_RATE, 2);
        if ($nominalKomisi <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal komisi nol, transaksi tidak dapat disimpan.',
            ], 422);
        }

        $customers = Customer::with('user', 'ticket')
            ->whereIn('id', $customerIds)
            ->get();
        $relasiNames = $customers->map(function ($c) {
            return optional($c->user)->name ?? $c->ticket?->applicant_name ?? null;
        })->filter()->values()->all();
        $relasi = !empty($relasiNames)
            ? implode(', ', $relasiNames)
            : count($customerIds) . ' pelanggan';

        $penerima = User::find($request->penerima_komisi_id);
        $penerimaName = $penerima?->name ?? 'Penerima';

        $billPaymentIds = [];
        $komisiTransactionIds = [];

        $result = DB::transaction(function () use ($request, $bills, $totalUnpaid, $nominalKomisi, $relasi, $penerimaName, $customerIds, &$billPaymentIds, &$komisiTransactionIds) {
            $tgl = Carbon::parse($request->tgl_transaksi)->toDateString();
            $keteranganBase = $request->keterangan ?: 'Komisi SPS - ' . $relasi . ' (Penerima: ' . $penerimaName . ')';

            $billIds = $bills->pluck('id')->all();
            MonthlyBill::whereIn('id', $billIds)->update(['status' => 'paid']);

            foreach ($bills as $bill) {
                $komisiPerBill = round($bill->total_amount * self::KOMISI_RATE, 2);

                $billRelasi = optional($bill->customer?->user)->name ?? $bill->customer?->ticket?->applicant_name ?? 'Pelanggan';
                $billKeterangan = $keteranganBase . ' | Bill #' . $bill->id . ' (' . number_format($bill->total_amount, 0, ',', '.') . ')';

                $bp = $this->billPaymentService->recordPayment(
                    $bill,
                    $request->account_kas,
                    $tgl,
                    $billKeterangan,
                    (int) $request->penerima_komisi_id
                );
                $billPaymentIds[] = $bp->id;

                $komisiKeterangan = 'Beban Komisi SPS - ' . $billRelasi . ' (10% × ' . number_format($bill->total_amount, 0, ',', '.') . ' = ' . number_format($komisiPerBill, 0, ',', '.') . ') - Penerima: ' . $penerimaName;

                $komisiTrans = Transaction::create([
                    'tgl_transaksi'        => $tgl,
                    'account_debet'        => self::BEBAN_KODE_AKUN,
                    'account_kredit'       => self::UTANG_KODE_AKUN,
                    'transaction_group'    => self::TRANSACTION_GROUP,
                    'reverence_type'       => 'bill_payment',
                    'reverence_id'         => $bp->id,
                    'penerima_komisi_id'   => $request->penerima_komisi_id,
                    'keterangan_transaksi' => $komisiKeterangan,
                    'relasi'               => $billRelasi,
                    'saldo'                => $komisiPerBill,
                    'urutan'               => 1,
                    'id_user'              => Auth::id(),
                ]);
                $komisiTrans->update(['urutan' => $komisiTrans->id]);
                $komisiTransactionIds[] = $komisiTrans->id;
            }

            return [
                'nominal_komisi' => $nominalKomisi,
            ];
        });

        $billDetails = $bills->map(function ($bill) {
            $komisiPerBill = round($bill->total_amount * self::KOMISI_RATE, 2);
            return [
                'bill_id'         => $bill->id,
                'customer_id'     => $bill->customer_id,
                'amount'          => (float) $bill->total_amount,
                'komisi_per_bill' => $komisiPerBill,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi komisi SPS berhasil disimpan.',
            'data'    => [
                'relasi'                 => $relasi,
                'penerima_komisi_id'     => (int) $request->penerima_komisi_id,
                'penerima_komisi_name'   => $penerimaName,
                'customer_ids'           => $customerIds,
                'total_unpaid'           => $totalUnpaid,
                'nominal_komisi'         => $result['nominal_komisi'],
                'rate'                   => self::KOMISI_RATE,
                'bill_count'             => $bills->count(),
                'bill_details'           => $billDetails,
                'account_kas'            => $request->account_kas,
                'account_utang'          => self::UTANG_KODE_AKUN,
                'account_beban_komisi'   => self::BEBAN_KODE_AKUN,
                'jurnal'                 => [
                    'debet'  => self::BEBAN_KODE_AKUN,
                    'kredit' => self::UTANG_KODE_AKUN,
                ],
            ],
        ], 201);
    }
}
