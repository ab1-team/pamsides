<?php

namespace App\Http\Controllers;

use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\MonthlyBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyBillController extends Controller
{
    public function __construct(protected MonthlyBillService $monthlyBillService) {}

    public function index(Request $request)
    {
        $query = MonthlyBill::with([
            'customer.user',
            'customer.ticket.package',
            'customer.ticket.village',
            'billPayments',
        ])->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc');

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->month) {
            $query->where('billing_period_month', $request->month);
        }

        if ($request->year) {
            $query->where('billing_period_year', $request->year);
        }

        $bills = $query->get();

        $items = $bills->map(function ($b) {
            try {
                $customer = $b->customer;
                $ticket = $customer?->ticket;
                $user = $customer?->user;

                return [
                    'id' => $b->id,
                    'customer_id' => $b->customer_id,
                    'billing_period_month' => $b->billing_period_month,
                    'billing_period_year' => $b->billing_period_year,
                    'meter_reading_start' => $b->meter_reading_start,
                    'meter_reading_end' => $b->meter_reading_end,
                    'usage_m3' => $b->usage_m3,
                    'usage_charge' => $b->usage_charge,
                    'abodemen' => $b->abodemen,
                    'penalty_amount' => $b->penalty_amount,
                    'total_amount' => $b->total_amount,
                    'status' => $b->status,
                    'due_date' => $b->due_date,
                    'bill_payments' => $b->billPayments->map(fn ($p) => [
                        'id' => $p->id,
                        'amount_paid' => $p->amount_paid,
                        'confirmed_by' => $p->confirmed_by,
                        'paid_at' => $p->paid_at,
                    ]),
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'initial_meter_reading' => $customer->initial_meter_reading,
                        'activated_at' => $customer->activated_at,
                        'meter_photo_url' => $customer->meter_photo_url ?: null,
                        'user' => $user ? [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ] : null,
                        'ticket' => $ticket ? [
                            'id' => $ticket->id,
                            'applicant_name' => $ticket->applicant_name,
                            'nik' => $ticket->nik,
                            'address' => $ticket->address,
                            'phone' => $ticket->phone,
                            'village' => $ticket->village,
                            'package' => $ticket->package,
                        ] : null,
                    ] : null,
                ];
            } catch (\Throwable $e) {
                \Log::warning('MonthlyBill::index map error', [
                    'bill_id' => $b->id ?? null,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'id' => $b->id,
                    'customer_id' => $b->customer_id,
                    'billing_period_month' => $b->billing_period_month,
                    'billing_period_year' => $b->billing_period_year,
                    'meter_reading_start' => $b->meter_reading_start,
                    'meter_reading_end' => $b->meter_reading_end,
                    'usage_m3' => $b->usage_m3,
                    'usage_charge' => $b->usage_charge,
                    'abodemen' => $b->abodemen,
                    'penalty_amount' => $b->penalty_amount,
                    'total_amount' => $b->total_amount,
                    'status' => $b->status,
                    'due_date' => $b->due_date,
                    'customer' => null,
                ];
            }
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'bills' => $items,
            ],
        ]);
    }

    public function usage(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2000',
        ]);

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        // Hanya pelanggan yang tiketnya sudah aktif/berjalan (bukan draft/pending)
        $customers = Customer::with(['user', 'ticket.package', 'ticket.village'])
            ->whereHas('ticket', function ($q) {
                $q->whereIn('status', ['surveyed', 'unpaid', 'processing', 'completed', 'suspended']);
            })
            ->get();

        $items = $customers->map(function ($customer) use ($month, $year) {
            $reading = $customer->meterReadings()
                ->where('reading_month', $month)
                ->where('reading_year', $year)
                ->first();

            $bill = MonthlyBill::where('customer_id', $customer->id)
                ->where('billing_period_month', $month)
                ->where('billing_period_year', $year)
                ->first();

            $status = $bill
                ? $bill->status
                : ($reading ? 'unpaid' : 'pending');

            $statusLabel = match (strtoupper($status)) {
                'PAID' => 'PAID',
                'UNPAID' => 'UNPAID',
                default => 'PENDING',
            };

            return [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'nama' => optional($customer->user)->name ?? $customer->ticket?->applicant_name,
                'nik' => $customer->ticket?->nik,
                'alamat' => $customer->ticket?->address,
                'dusun' => $customer->ticket?->village?->hamlet_name,
                'desa' => $customer->ticket?->village?->village_name,
                'package_name' => $customer->ticket?->package?->name,
                'meter_awal' => $bill?->meter_reading_start ?? $customer->initial_meter_reading,
                'meter_akhir' => $bill?->meter_reading_end ?? $reading?->meter_value,
                'pemakaian' => $bill?->usage_m3,
                'tagihan' => $bill?->total_amount,
                'denda' => $bill?->penalty_amount,
                'abodemen' => $bill?->abodemen,
                'status' => $statusLabel,
                'due_date' => $bill?->due_date,
                'reading_photo' => $reading?->photo_url ?: null,
                'reading_recorded_at' => $reading?->recorded_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'nullable|string|in:cash,transfer',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $bill = MonthlyBill::findOrFail($id);

        if ($bill->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan sudah dibayar',
            ], 400);
        }

        $bill->update(['status' => 'paid']);

        $payment = BillPayment::create([
            'bill_id' => $bill->id,
            'amount_paid' => $request->amount_paid ?? $bill->total_amount,
            'confirmed_by' => Auth::id(),
            'paid_at' => now(),
        ]);

        $bill->load('customer.user', 'customer.ticket.package');

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dikonfirmasi',
            'data' => [
                'bill' => $bill,
                'payment' => $payment,
            ],
        ]);
    }

    public function generate()
    {
        $result = $this->monthlyBillService->generate();

        if (! $result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function report(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $bills = MonthlyBill::with('customer.user')
            ->where('billing_period_month', $request->month)
            ->where('billing_period_year', $request->year)
            ->get();

        $summary = [
            'total_tagihan' => $bills->sum('total_amount'),
            'total_dibayar' => $bills->where('status', 'paid')->sum('total_amount'),
            'total_belum_dibayar' => $bills->where('status', 'unpaid')->sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'bills' => $bills,
            ],
        ]);
    }

    public function show($id)
    {
        $bill = MonthlyBill::with([
            'customer',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bill,
        ]);
    }

    public function destroy($id)
    {
        $bill = MonthlyBill::findOrFail($id);

        if ($bill->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tagihan yang sudah lunas yang bisa di-rollback.',
            ], 400);
        }

        $bill->update(['status' => 'unpaid']);

        BillPayment::where('bill_id', $bill->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tagihan dikembalikan ke status belum dibayar.',
            'data' => $bill,
        ]);
    }
}
