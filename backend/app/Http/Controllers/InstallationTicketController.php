<?php

namespace App\Http\Controllers;

use App\Models\InstallationTicket;
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
            'payments',
        ])->orderBy('created_at', 'desc');

        if ($request->has('search') && ! empty($request->search)) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('applicant_name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'draft') {
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
                $query->where('status', $request->status);
            }
        }

        if (! isset($tickets)) {
            $perPage = (int) $request->get('per_page', 10);
            $tickets = $query->paginate($perPage > 0 ? $perPage : 10);
        }

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    public function show(InstallationTicket $installationTicket)
    {
        return response()->json([
            'success' => true,
            'data' => $installationTicket->load([
                'package.tariffBlocks',
                'package',
                'survey.surveyor',
                'payments',
                'customer.meterReadings',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_name' => 'required|string|max:255',
            'nik' => 'required|string|max:20',
            'address' => 'required|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:installation_packages,id',
            'village_id' => 'nullable|exists:villages,id',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'order_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = InstallationTicket::create(array_merge(
            $validator->validated(),
            [
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dibuat (status: draft).',
            'data' => $ticket->load('package'),
        ], 201);
    }

    public function registerInstallation(Request $request, $id)
    {
        $request->validate([
            'package_id' => 'required|exists:installation_packages,id',
            'village_id' => 'required|exists:villages,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'order_date' => 'required|date',
        ], [
            'package_id.required' => 'Paket instalasi wajib dipilih.',
            'package_id.exists' => 'Paket instalasi tidak ditemukan.',
            'village_id.required' => 'Desa wajib dipilih.',
            'lat.required' => 'Koordinat latitude wajib diisi.',
            'lng.required' => 'Koordinat longitude wajib diisi.',
            'order_date.required' => 'Tanggal order wajib diisi.',
        ]);

        try {
            $oldTicket = InstallationTicket::findOrFail($id);

            // 2. KONDISI A: Jika data masih 'draft', berarti ini pelengkapan registrasi pertama kali.
            // Cukup UPDATE data tersebut, jangan buat data baru.
            if ($oldTicket->status === 'draft') {

                $oldTicket->update([
                    'package_id' => $request->package_id,
                    'user_id' => $request->user_id,
                    'order_date' => date('Y-m-d', strtotime($request->order_date)),
                    'village_id' => $request->village_id,
                    'lat' => $request->lat,
                    'lng' => $request->lng,
                    'status' => 'pending',
                    'created_by' => auth()->id() ?: $oldTicket->created_by,

                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registrasi pertama berhasil dilengkapi (Data di-update).',
                    'data' => $oldTicket->load('package'),
                ], 200);
            }

            $newTicket = InstallationTicket::create([
                'applicant_name' => $oldTicket->applicant_name,
                'address' => $oldTicket->address,
                'nik' => $oldTicket->nik,
                'phone' => $oldTicket->phone,
                'gender' => $oldTicket->gender,
                'birth_place' => $oldTicket->birth_place,
                'birth_date' => $oldTicket->birth_date,
                'package_id' => $request->package_id,
                'user_id' => $request->user_id,
                'order_date' => date('Y-m-d', strtotime($request->order_date)),
                'village_id' => $request->village_id,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'status' => 'pending',
                'created_by' => auth()->id() ?: $oldTicket->created_by,

            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instalasi titik baru berhasil didaftarkan tanpa menimpa titik lama.',
                'data' => $newTicket->load('package'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: '.$e->getMessage(),
            ], 500);
        }
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

                // GENERATE CUSTOMER CODE (PAM-YYYYMM-0001)
                $yearMonth = now()->format('Ym');

                $latestCustomer = Customer::where('customer_code', 'like', 'PAM-'.$yearMonth.'-%')
                    ->orderBy('customer_code', 'desc')
                    ->first();

                $nextNumber = $latestCustomer
                    ? str_pad((int) substr($latestCustomer->customer_code, -4) + 1, 4, '0', STR_PAD_LEFT)
                    : '0001';

                $customerCode = 'PAM-'.$yearMonth.'-'.$nextNumber;

                // INSERT KE TABEL CUSTOMERS
                Customer::create([
                    'ticket_id' => $installationTicket->id,
                    'user_id' => $installationTicket->user_id,
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
}
