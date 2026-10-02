<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\InstallationTicketHistory;
use App\Models\MonthlyBill;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InstallationTicketHistoryController extends Controller
{
    /**
     * GET /installation-tickets/{ticket}/history
     *
     * Daftar snapshot paket lama + tiket info + tagihan yang sedang berjalan.
     * Dipakai oleh popup "Detail Paket & Tagihan" di frontend dataInstalasi.
     *
     * Response shape:
     * {
     *   success: true,
     *   data: {
     *     ticket: { id, kode_instalasi, nama, alamat, status, package: {...} },
     *     customer: { id, code, activated_at } | null,
     *     current_package: { id, name, installation_fee, monthly_abodemen, late_penalty, tariff_blocks: [...] },
     *     history: [ { id, change_type, change_type_label, change_type_color, package: {...},
     *                  new_package: {...}|null, effective_from, effective_until,
     *                  total_paid_on_old_package, total_billed_on_old_package,
     *                  remaining_on_old_package, has_outstanding, reason, changed_by: {...}, created_at } ],
     *     billing_summary: {
     *       total_billed, total_paid, outstanding,
     *       unpaid_bills_count, latest_bill: {...} | null
     *     }
     *   }
     * }
     */
    public function index(Request $request, InstallationTicket $installationTicket)
    {
        $installationTicket->load([
            'package.tariffBlocks',
            'village:id,village_name',
            'customer:id,customer_code,ticket_id,activated_at',
            'history.oldPackage:id,name',
            'history.newPackage:id,name',
            'history.changedByUser:id,name',
        ]);

        $customer = $installationTicket->customer->first();

        $billingSummary = $this->buildBillingSummaryForTicket(
            $installationTicket->id,
            $customer?->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => [
                    'id' => $installationTicket->id,
                    'kode_instalasi' => $customer?->customer_code
                        ?? '#INS-' . str_pad((string) $installationTicket->id, 4, '0', STR_PAD_LEFT),
                    'nama' => $installationTicket->applicant_name,
                    'alamat' => $installationTicket->address,
                    'status' => $installationTicket->status,
                    'village' => $installationTicket->village?->village_name,
                ],
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'code' => $customer->customer_code,
                    'activated_at' => $customer->activated_at,
                ] : null,
                'current_package' => $installationTicket->package ? [
                    'id' => $installationTicket->package->id,
                    'name' => $installationTicket->package->name,
                    'installation_fee' => (float) $installationTicket->package->installation_fee,
                    'monthly_abodemen' => (float) $installationTicket->package->monthly_abodemen,
                    'late_penalty' => (float) $installationTicket->package->late_penalty,
                    'tariff_blocks' => $installationTicket->package->tariffBlocks->map(fn ($b) => [
                        'id' => $b->id,
                        'min_m3' => $b->min_m3,
                        'max_m3' => $b->max_m3,
                        'price_per_m3' => (float) $b->price_per_m3,
                    ])->values(),
                ] : null,
                'history' => $installationTicket->history->map(fn ($h) => [
                    'id' => $h->id,
                    'change_type' => $h->change_type,
                    'change_type_label' => $h->change_type_label,
                    'change_type_color' => $h->change_type_color,
                    'package' => $h->oldPackage ? [
                        'id' => $h->oldPackage->id,
                        'name' => $h->oldPackage->name,
                    ] : [
                        'id' => $h->package_id,
                        'name' => $h->package_name,
                    ],
                    'new_package' => $h->newPackage ? [
                        'id' => $h->newPackage->id,
                        'name' => $h->newPackage->name,
                    ] : null,
                    'effective_from' => $h->effective_from,
                    'effective_until' => $h->effective_until,
                    'total_paid_on_old_package' => (float) $h->total_paid_on_old_package,
                    'total_billed_on_old_package' => (float) $h->total_billed_on_old_package,
                    'remaining_on_old_package' => (float) $h->remaining_on_old_package,
                    'has_outstanding' => $h->has_outstanding,
                    'reason' => $h->reason,
                    'changed_by' => $h->changedByUser ? [
                        'id' => $h->changedByUser->id,
                        'name' => $h->changedByUser->name,
                    ] : null,
                    'created_at' => $h->created_at,
                ])->values(),
                'billing_summary' => $billingSummary,
            ],
        ]);
    }

    /**
     * POST /installation-tickets/{ticket}/change-package
     *
     * Body: { new_package_id: int, reason?: string }
     *
     * Logika:
     *   1. Validasi paket baru beda dari paket lama, kecuali paket lama null (initial).
     *   2. Snapshot paket lama + ringkungan tagihan ke installation_ticket_histories
     *      (effective_until = NOW, effective_from = waktu paket lama mulai aktif).
     *   3. Update installation_tickets.package_id ke paket baru.
     *   4. monthly_bills TIDAK diubah — setiap tagihan sudah snapshot abodemen & usage_charge.
     *      Tagihan lama tetap dihitung dengan tarif lama, tagihan baru ke depan pakai tarif baru.
     *
     * Response: index() yang sudah diperbarui.
     */
    public function changePackage(Request $request, InstallationTicket $installationTicket)
    {
        $validator = Validator::make($request->all(), [
            'new_package_id' => ['required', 'integer', 'exists:installation_packages,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'new_package_id.required' => 'Pilih paket baru terlebih dahulu.',
            'new_package_id.exists' => 'Paket baru tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $newPackageId = (int) $request->input('new_package_id');
        $reason = $request->input('reason');
        $userId = $request->user()?->id;

        // Cegah update ke paket yang sama
        if ($installationTicket->package_id === $newPackageId) {
            return response()->json([
                'success' => false,
                'message' => 'Paket baru sama dengan paket saat ini.',
            ], 422);
        }

        $oldPackage = $installationTicket->package; // relasi
        $newPackage = InstallationPackage::find($newPackageId);

        if (! $newPackage) {
            return response()->json([
                'success' => false,
                'message' => 'Paket baru tidak valid.',
            ], 422);
        }

        // Paket lama mungkin null untuk ticket tanpa paket (edge case).
        if (! $oldPackage) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini belum memiliki paket. Tidak ada snapshot untuk disimpan.',
            ], 422);
        }

        try {
            DB::transaction(function () use (
                $installationTicket, $oldPackage, $newPackage, $newPackageId,
                $reason, $userId
            ) {
                // 1) Tentukan change_type: bandingkan monthly_abodemen sebagai proksi
                //    (lebih sederhana & stabil dibanding bandingkan installation_fee).
                $oldAbodemen = (float) $oldPackage->monthly_abodemen;
                $newAbodemen = (float) $newPackage->monthly_abodemen;

                if ($newAbodemen > $oldAbodemen) {
                    $changeType = 'upgrade';
                } elseif ($newAbodemen < $oldAbodemen) {
                    $changeType = 'downgrade';
                } else {
                    // Abodemen sama → reset/replace paket
                    $changeType = 'reset';
                }

                // 2) Cari periode aktif paket lama = effective_from history row
                //    terakhir yang belum punya effective_until, atau customer.activated_at
                //    bila belum pernah ada history.
                $customer = $installationTicket->customer()->first();
                $lastHistory = InstallationTicketHistory::where('installation_ticket_id', $installationTicket->id)
                    ->orderByDesc('created_at')
                    ->first();

                $effectiveFrom = $lastHistory?->effective_until
                    ?? $customer?->activated_at
                    ?? $installationTicket->created_at;

                // 3) Hitung ringkasan tagihan & pembayaran paket lama.
                //    total_billed = total tagihan bulanan customer
                //    total_paid   = SELURUH payment confirmed terkait tiket ini,
                //                    termasuk installation fee (payments) + bill_payments.
                //    Pola ini konsisten dengan implementasi lama: row history existing
                //    pada ticket 1631 menunjukkan total_paid = 750000 dari payments.amount
                //    (bukan bill_payments).
                $totalBilled = 0.0;
                if ($customer) {
                    $totalBilled = (float) MonthlyBill::where('customer_id', $customer->id)
                        ->sum('total_amount');
                }

                $ticketPayments = (float) Payment::where('ticket_id', $installationTicket->id)
                    ->where('status', 'confirmed')
                    ->sum('amount');

                $billPayments = 0.0;
                if ($customer) {
                    $billPayments = (float) DB::table('bill_payments')
                        ->join('monthly_bills', 'bill_payments.bill_id', '=', 'monthly_bills.id')
                        ->where('monthly_bills.customer_id', $customer->id)
                        ->sum('bill_payments.amount_paid');
                }

                $totalPaid = $ticketPayments + $billPayments;

                $remaining = max(0, $totalBilled - $totalPaid);

                // 4) Tutup history paket lama (effective_until = NOW)
                if ($lastHistory && $lastHistory->effective_until === null) {
                    $lastHistory->effective_until = now();
                    $lastHistory->save();
                }

                // 5) Insert history row baru (snapshot paket lama)
                InstallationTicketHistory::create([
                    'installation_ticket_id' => $installationTicket->id,
                    'customer_id' => $customer?->id,
                    'package_id' => $oldPackage->id,                 // paket LAMA
                    'new_package_id' => $newPackage->id,             // paket BARU
                    'package_name' => $oldPackage->name,
                    'installation_fee' => $oldPackage->installation_fee,
                    'monthly_abodemen' => $oldPackage->monthly_abodemen,
                    'late_penalty' => $oldPackage->late_penalty,
                    'effective_from' => $effectiveFrom,
                    'effective_until' => now(),
                    'total_paid_on_old_package' => $totalPaid,
                    'total_billed_on_old_package' => $totalBilled,
                    'remaining_on_old_package' => $remaining,
                    'change_type' => $changeType,
                    'reason' => $reason,
                    'changed_by' => $userId,
                ]);

                // 6) Update ticket ke paket baru
                $installationTicket->package_id = $newPackage->id;
                $installationTicket->save();
            });
        } catch (\Throwable $e) {
            Log::error('changePackage failed', [
                'ticket_id' => $installationTicket->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui paket: ' . $e->getMessage(),
            ], 500);
        }

        // Reload dengan relasi terbaru lalu kembalikan payload yang sama dgn index()
        $installationTicket->refresh();
        $installationTicket->load([
            'package.tariffBlocks',
            'village:id,village_name',
            'customer:id,customer_code,ticket_id,activated_at',
            'history.oldPackage:id,name',
            'history.newPackage:id,name',
            'history.changedByUser:id,name',
        ]);

        return $this->index($request, $installationTicket);
    }

    /* ---------- Helpers ---------- */

    /**
     * Ringkasan tagihan customer: total tagihan, total dibayar, sisa,
     * jumlah tagihan belum bayar, dan tagihan terakhir.
     */
    private function buildBillingSummaryForTicket(int $ticketId, ?int $customerId): array
    {
        $totalBilled = 0.0;
        if ($customerId) {
            $totalBilled = (float) MonthlyBill::where('customer_id', $customerId)->sum('total_amount');
        }

        $ticketPayments = (float) Payment::where('ticket_id', $ticketId)
            ->where('status', 'confirmed')
            ->sum('amount');

        $billPayments = 0.0;
        if ($customerId) {
            $billPayments = (float) DB::table('bill_payments')
                ->join('monthly_bills', 'bill_payments.bill_id', '=', 'monthly_bills.id')
                ->where('monthly_bills.customer_id', $customerId)
                ->sum('bill_payments.amount_paid');
        }

        $totalPaid = $ticketPayments + $billPayments;
        $outstanding = max(0, $totalBilled - $totalPaid);

        $unpaidBillsCount = 0;
        $latestBill = null;
        if ($customerId) {
            $unpaidBillsCount = (int) MonthlyBill::where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->count();
            $latest = MonthlyBill::where('customer_id', $customerId)
                ->orderByDesc('billing_period_year')
                ->orderByDesc('billing_period_month')
                ->first();
            if ($latest) {
                $latestBill = [
                    'id' => $latest->id,
                    'period' => sprintf('%04d-%02d', $latest->billing_period_year, $latest->billing_period_month),
                    'total_amount' => (float) $latest->total_amount,
                    'status' => $latest->status,
                    'due_date' => $latest->due_date,
                ];
            }
        }

        return [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
            'unpaid_bills_count' => $unpaidBillsCount,
            'latest_bill' => $latestBill,
        ];
    }
}