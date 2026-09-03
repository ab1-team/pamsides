<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\StateMachines\TicketStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstallationTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallationTicket::with([
            'package.tariffBlocks',
            'package',
            'survey.surveyor',
            'user',
            'village',
            'customer.meterReadings',
            'customer.monthlyBills',
            'payments',
        ])->orderBy('created_at', 'desc');

        if ($request->has('search') && ! empty($request->search)) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $wantsGrouped = $request->boolean('grouped')
            || $request->status === 'draft';

        if ($request->has('status') && ! $wantsGrouped) {
            $query->where('status', $request->status);
        }

        if ($wantsGrouped) {
            $allTickets = $query->get();

            $grouped = $allTickets->groupBy('nik')->map(function ($items) {
                $base = $items->firstWhere(fn ($item) => $item->phone || $item->gender || $item->birth_place) ?? $items->first();

                $tickets = $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'village_id' => $item->village_id,
                        'lat' => $item->lat,
                        'lng' => $item->lng,
                        'package_id' => $item->package_id,
                        'package' => $item->package,
                        'user_id' => $item->user_id,
                        'order_date' => $item->order_date,
                        'status' => $item->status,
                        'payments' => $item->payments,
                        'customer' => $item->customer,
                    ];
                })->values();

                return [
                    'id' => $base->id,
                    'applicant_name' => $base->applicant_name,
                    'nik' => $base->nik,
                    'phone' => $items->pluck('phone')->filter()->first(),
                    'gender' => $items->pluck('gender')->filter()->first(),
                    'birth_place' => $items->pluck('birth_place')->filter()->first(),
                    'birth_date' => $items->pluck('birth_date')->filter()->first(),
                    'tickets' => $tickets,
                ];
            })->values();

            $tickets = $grouped;
        } else {
            $perPage = (int) $request->get('per_page', 10);
            $tickets = $query->paginate($perPage > 0 ? $perPage : 10);
        }

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Endpoint ringan khusus dropdown Register Instalasi.
     * Tanpa eager-load relasi berat (payments, customer.meterReadings, dll).
     * Return data di-groupBy NIK agar frontend tinggal render.
     */
    public function registerDropdown(Request $request)
    {
        $query = InstallationTicket::query()
            ->select([
                'id',
                'nik',
                'applicant_name',
                'phone',
                'gender',
                'birth_place',
                'birth_date',
                'status',
                'village_id',
                'package_id',
                'user_id',
                'order_date',
                'lat',
                'lng',
            ])
            ->with([
                'package:id,name,installation_fee',
                'village:id,village_name,address',
            ])
            ->orderBy('id', 'asc');

        if ($request->has('search') && ! empty($request->search)) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $allTickets = $query->get();

        $grouped = $allTickets->groupBy('nik')->map(function ($items) {
            $base = $items->firstWhere(fn ($item) => $item->phone || $item->gender || $item->birth_place) ?? $items->first();

            $tickets = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'village_id' => $item->village_id,
                    'village_name' => $item->village?->village_name ?? '',
                    'village_address' => $item->village?->address ?? '',
                    'lat' => $item->lat,
                    'lng' => $item->lng,
                    'package_id' => $item->package_id,
                    'package' => $item->package ? [
                        'id' => $item->package->id,
                        'name' => $item->package->name,
                        'installation_fee' => $item->package->installation_fee,
                    ] : null,
                    'user_id' => $item->user_id,
                    'order_date' => $item->order_date,
                    'status' => $item->status,
                ];
            })->values();

            return [
                'id' => $base->id,
                'applicant_name' => $base->applicant_name,
                'nik' => $base->nik,
                'phone' => $items->pluck('phone')->filter()->first(),
                'gender' => $items->pluck('gender')->filter()->first(),
                'birth_place' => $items->pluck('birth_place')->filter()->first(),
                'birth_date' => $items->pluck('birth_date')->filter()->first(),
                'tickets' => $tickets,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    public function show(InstallationTicket $installationTicket)
    {
        $ticket = $installationTicket->load([
            'package.tariffBlocks',
            'package',
            'survey.surveyor',
            'payments',
            'customer.meterReadings',
            'customer.monthlyBills',
        ]);

        return response()->json([
            'success' => true,
            'data'    => array_merge($ticket->toArray(), [
                'total_fee'  => (float) $ticket->total_fee,
                'paid'       => (float) $ticket->paid_amount,
                'remaining'  => (float) $ticket->remaining,
                'is_paid_off'=> (bool) $ticket->is_fully_paid,
            ]),
        ]);
    }

    public function unpaidTickets(Request $request)
    {
        $tickets = InstallationTicket::with(['package', 'village', 'payments', 'user'])
            ->whereIn('status', ['surveyed', 'unpaid'])
            ->orderByRaw("FIELD(status, 'surveyed', 'unpaid')")
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($ticket) {
                $confirmedTotal = (float) $ticket->payments->where('status', 'confirmed')->sum('amount');
                $pendingTotal   = (float) $ticket->payments->where('status', 'pending')->sum('amount');
                $totalFee       = (float) $ticket->total_fee;

                return [
                    'id'              => $ticket->id,
                    'applicant_name'  => $ticket->applicant_name,
                    'nik'             => $ticket->nik,
                    'phone'           => $ticket->phone,
                    'address'         => $ticket->address,
                    'village'         => $ticket->village?->village_name ?? $ticket->village?->name ?? '-',
                    'package'         => $ticket->package?->name ?? '-',
                    'status'          => $ticket->status,
                    'order_date'      => $ticket->order_date,
                    'total_fee'       => $totalFee,
                    'paid'            => $confirmedTotal,
                    'pending'         => $pendingTotal,
                    'total_paid'      => $confirmedTotal + $pendingTotal,
                    'remaining'       => max(0, $totalFee - $confirmedTotal - $pendingTotal),
                    'is_paid_off'     => $confirmedTotal >= $totalFee,
                    'has_pending'     => $pendingTotal > 0,
                    'payments'        => $ticket->payments->map(fn ($p) => [
                        'id'     => $p->id,
                        'amount' => (float) $p->amount,
                        'paid_at'=> $p->paid_at,
                        'status' => $p->status,
                    ]),
                ];
            })
            ->filter(fn ($t) => ! $t['is_paid_off'])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $isRegistration = $request->filled(['package_id', 'village_id', 'lat', 'lng', 'order_date']);

        if ($isRegistration) {
            $request->validate([
                'package_id'    => 'required|exists:installation_packages,id',
                'village_id'    => 'required|exists:villages,id',
                'lat'           => 'required|numeric|between:-90,90',
                'lng'           => 'required|numeric|between:-180,180',
                'order_date'    => 'required|date',
                'nominal'       => 'nullable|numeric|min:0',
                'ticket_id'     => 'nullable|exists:installation_tickets,id',
                'user_id'       => 'nullable|exists:users,id',
            ], [
                'package_id.required' => 'Paket instalasi wajib dipilih.',
                'package_id.exists'   => 'Paket instalasi tidak ditemukan.',
                'village_id.required' => 'Desa wajib dipilih.',
                'lat.required'        => 'Koordinat latitude wajib diisi.',
                'lng.required'        => 'Koordinat longitude wajib diisi.',
                'order_date.required' => 'Tanggal order wajib diisi.',
            ]);

            $settings = Setting::first();
            $mustBeFullyPaid = (bool) ($settings->status_pembayaran ?? false);

            $nominalRaw = $request->input('nominal');
            $nominalInput = 0.0;
            if ($nominalRaw !== null && $nominalRaw !== '') {
                $nominalInput = (float) str_replace(',', '.', (string) $nominalRaw);
            }

            $packageFee = (float) InstallationPackage::where('id', $request->package_id)->value('installation_fee');

            if ($mustBeFullyPaid) {
                $paymentAmount = $packageFee;
                $paymentStatus = 'confirmed';
            } else {
                $paymentAmount = max(0, min($nominalInput, $packageFee));
                $paymentStatus = $paymentAmount >= $packageFee ? 'confirmed' : ($paymentAmount > 0 ? 'pending' : null);
            }

            $isPartialPayment = ! $mustBeFullyPaid && $paymentAmount > 0 && $paymentAmount < $packageFee;

            try {
                $userId = auth()->id();

                if ($request->ticket_id) {
                    $oldTicket = InstallationTicket::findOrFail($request->ticket_id);

                    if ($oldTicket->status !== 'draft') {
                        $targetTicket = InstallationTicket::create([
                            'applicant_name' => $oldTicket->applicant_name,
                            'address'        => $oldTicket->address,
                            'nik'            => $oldTicket->nik,
                            'phone'          => $oldTicket->phone,
                            'gender'         => $oldTicket->gender,
                            'birth_place'    => $oldTicket->birth_place,
                            'birth_date'     => $oldTicket->birth_date,
                            'package_id'     => $request->package_id,
                            'user_id'        => $request->user_id,
                            'order_date'     => date('Y-m-d', strtotime($request->order_date)),
                            'village_id'     => $request->village_id,
                            'lat'            => $request->lat,
                            'lng'            => $request->lng,
                            'status'         => 'pending',
                            'created_by'     => $userId ?: $oldTicket->created_by,
                        ]);
                    } else {
                        $oldTicket->update([
                            'package_id' => $request->package_id,
                            'user_id'    => $request->user_id,
                            'order_date' => date('Y-m-d', strtotime($request->order_date)),
                            'village_id' => $request->village_id,
                            'lat'        => $request->lat,
                            'lng'        => $request->lng,
                            'status'     => 'pending',
                            'created_by' => $userId ?: $oldTicket->created_by,
                        ]);
                        $targetTicket = $oldTicket;
                    }
                } else {
                    $request->validate([
                        'applicant_name' => 'required|string|max:255',
                        'nik'            => 'required|string|max:20',
                        'address'        => 'required|string',
                    ]);

                    $targetTicket = InstallationTicket::create([
                        'applicant_name' => $request->applicant_name,
                        'nik'            => $request->nik,
                        'address'        => $request->address,
                        'phone'          => $request->phone,
                        'gender'         => $request->gender,
                        'birth_place'    => $request->birth_place,
                        'birth_date'     => $request->birth_date,
                        'package_id'     => $request->package_id,
                        'user_id'        => $request->user_id,
                        'order_date'     => date('Y-m-d', strtotime($request->order_date)),
                        'village_id'     => $request->village_id,
                        'lat'            => $request->lat,
                        'lng'            => $request->lng,
                        'status'         => 'pending',
                        'created_by'     => $userId,
                    ]);
                }

                if ($paymentAmount > 0) {
                    Payment::create([
                        'ticket_id'    => $targetTicket->id,
                        'amount'       => $paymentAmount,
                        'type'         => 'installation_fee',
                        'status'       => $paymentStatus,
                        'confirmed_by' => $paymentStatus === 'confirmed' ? $userId : null,
                        'paid_at'      => $paymentStatus === 'confirmed' ? now() : null,
                    ]);
                }

                if (! $targetTicket->customer()->exists()) {
                    $customerCode = \App\Models\Village::generateCustomerCodeForVillageId(
                        $targetTicket->village_id
                    );

                    $pelanggan = User::where('role', 'pelanggan')
                        ->where('name', $targetTicket->applicant_name)
                        ->first();

                    if (! $pelanggan) {
                        $pelangganEmail = \Illuminate\Support\Str::slug($targetTicket->applicant_name).'_'.$targetTicket->nik.'@pamsides.local';
                        $pelanggan = User::firstOrCreate(
                            ['email' => $pelangganEmail],
                            [
                                'name'     => $targetTicket->applicant_name,
                                'password' => Hash::make('password'),
                                'role'     => 'pelanggan',
                            ]
                        );
                    }

                    Customer::create([
                        'ticket_id'             => $targetTicket->id,
                        'user_id'               => $pelanggan->id,
                        'customer_code'         => $customerCode,
                        'initial_meter_reading' => 0,
                        'meter_photo_url'       => null,
                        'activated_at'          => null,
                    ]);
                }

                $responseMsg = $mustBeFullyPaid
                    ? 'Registrasi berhasil. Pembayaran wajib lunas dicatat (confirmed).'
                    : ($isPartialPayment
                        ? 'Registrasi berhasil. Pembayaran sebagian dicatat (pending). Sisa: Rp '.number_format($packageFee - $paymentAmount, 0, ',', '.')
                        : 'Registrasi tiket berhasil.');

                return response()->json([
                    'success' => true,
                    'message' => $responseMsg,
                    'data'    => $targetTicket->load(['package', 'payments']),
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses data: '.$e->getMessage(),
                ], 500);
            }
        }

        // Draft mode (tanpa data registrasi lengkap)
        $validator = Validator::make($request->all(), [
            'applicant_name' => 'required|string|max:255',
            'nik'            => 'required|string|max:20',
            'address'        => 'required|string',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            'package_id'     => 'nullable|exists:installation_packages,id',
            'village_id'     => 'nullable|exists:villages,id',
            'phone'          => 'nullable|string|max:20',
            'gender'         => 'nullable|in:male,female',
            'birth_place'    => 'nullable|string|max:255',
            'birth_date'     => 'nullable|date',
            'order_date'     => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $ticket = InstallationTicket::create(array_merge(
            $validator->validated(),
            [
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuat (status: draft).',
            'data'    => $ticket->load('package'),
        ], 201);
    }

    public function transition(Request $request, InstallationTicket $installationTicket)
    {
        $request->validate([
            'status' => 'required|string|in:draft,pending,surveyed,unpaid,processing,completed,suspended,terminated',
            'initial_meter_reading' => 'nullable|numeric|min:0',
            'meter_photo_url' => 'nullable|string',
            'installation_date' => 'nullable|date',

        ], [
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status tidak valid.',
        ]);

        TicketStateMachine::validate($installationTicket->status, $request->status);

        DB::beginTransaction();
        try {

            // JIKA STATUS AKAN MENJADI PROCESSING - UPDATE activated_at customer
            if ($request->status === 'processing') {
                $customer = $installationTicket->customer()->first();
                if ($customer) {
                    $customer->update([
                        'activated_at' => $request->installation_date
                            ? date('Y-m-d H:i:s', strtotime($request->installation_date))
                            : now(),
                    ]);
                }
            }

            // JIKA STATUS AKAN MENJADI COMPLETED (AKTIVASI)
            if ($request->status === 'completed') {
                if ($installationTicket->customer()->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aktivasi awal harus melalui endpoint /installation-result dan /activate. Pastikan hasil instalasi sudah diinput oleh teknisi.',
                    ], 422);
                }

                // VALIDASI tambahan saat aktivasi
                if (! $request->initial_meter_reading && $request->initial_meter_reading !== 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Meter awal wajib diisi saat aktivasi.',
                    ], 422);
                }

                // GENERATE CUSTOMER CODE (berdasarkan village.code)
                $customerCode = \App\Models\Village::generateCustomerCodeForVillageId(
                    $installationTicket->village_id
                );

                // INSERT KE TABEL CUSTOMERS
                $pelanggan = User::where('role', 'pelanggan')
                    ->where('name', $installationTicket->applicant_name)
                    ->first();

                if (! $pelanggan) {
                    $pelangganEmail = \Illuminate\Support\Str::slug($installationTicket->applicant_name).'_'.$installationTicket->nik.'@pamsides.local';
                    $pelanggan = User::firstOrCreate(
                        ['email' => $pelangganEmail],
                        [
                            'name'     => $installationTicket->applicant_name,
                            'password' => Hash::make('password'),
                            'role'     => 'pelanggan',
                        ]
                    );
                }

                Customer::create([
                    'ticket_id' => $installationTicket->id,
                    'user_id' => $pelanggan->id,
                    'customer_code' => $customerCode,
                    'initial_meter_reading' => $request->initial_meter_reading,
                    'meter_photo_url' => $request->meter_photo_url ?? null,
                    'activated_at' => $request->installation_date
                        ? date('Y-m-d H:i:s', strtotime($request->installation_date))
                        : now(),
                ]);

            }

            $installationTicket->update(['status' => $request->status]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->status === 'completed'
                    ? 'Status tiket berhasil diperbarui ke completed.'
                    : 'Status tiket berhasil diperbarui.',
                'data' => $installationTicket->fresh()->load('customer'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal update status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function report(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        $tickets = InstallationTicket::whereMonth('created_at', $request->month)
            ->whereYear('created_at', $request->year)
            ->get();

        $summary = $tickets->groupBy('status')->map->count();

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => $request->month.'-'.$request->year,
                'summary' => $summary,
                'tickets' => $tickets,
            ],
        ]);
    }

    public function advanceStage(InstallationTicket $installationTicket)
    {
        $currentStatus = $installationTicket->status;

        if (! in_array($currentStatus, ['surveyed', 'unpaid'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Tiket dengan status '{$currentStatus}' tidak dapat melanjutkan tahap. Hanya berlaku untuk status 'surveyed' atau 'unpaid'.",
            ], 422);
        }

        $totalFee      = (float) ($installationTicket->package?->installation_fee ?? 0);
        $confirmedPaid = (float) $installationTicket->payments()
            ->where('status', 'confirmed')
            ->sum('amount');
        $pendingPaid   = (float) $installationTicket->payments()
            ->where('status', 'pending')
            ->sum('amount');

        if ($totalFee <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Paket instalasi belum memiliki biaya pasang baru.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($confirmedPaid >= $totalFee) {
                TicketStateMachine::validate($currentStatus, 'processing');
                $installationTicket->update(['status' => 'processing']);
                $newStatus = 'processing';
                $message = 'Pembayaran sudah lunas. Tiket lanjut ke tahap Pemasangan.';
            } elseif ($pendingPaid > 0) {
                TicketStateMachine::validate($currentStatus, 'unpaid');
                $installationTicket->update(['status' => 'unpaid']);
                $newStatus = 'unpaid';
                $message = 'Pembayaran masih menunggu konfirmasi. Tiket masuk tahap Pembayaran (Unpaid).';
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada pembayaran. Mohon input pembayaran terlebih dahulu di menu Tagihan Instalasi.',
                ], 422);
            }

            DB::commit();

            $fresh = $installationTicket->fresh();
            $newConfirmedTotal = (float) $fresh->payments()->where('status', 'confirmed')->sum('amount');
            $newPendingTotal   = (float) $fresh->payments()->where('status', 'pending')->sum('amount');
            $newRemaining = max(0, $totalFee - $newConfirmedTotal - $newPendingTotal);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => [
                    'ticket'       => $fresh->load(['package', 'payments']),
                    'status'       => $newStatus,
                    'total_fee'    => $totalFee,
                    'paid'         => $newConfirmedTotal,
                    'pending'      => $newPendingTotal,
                    'total_paid'   => $newConfirmedTotal + $newPendingTotal,
                    'remaining'    => $newRemaining,
                    'is_paid_off'  => $newConfirmedTotal >= $totalFee,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses tahap: '.$e->getMessage(),
            ], 500);
        }
    }
}
