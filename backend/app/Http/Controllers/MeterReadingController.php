<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMeterReadings;
use App\Http\Controllers\Concerns\GeneratesSafeUploadNames;
use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\MeterResetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeterReadingController extends Controller
{
    use AuthorizesMeterReadings;
    use GeneratesSafeUploadNames;

    /**
     * Ambil data meteran yang SUDAH di-input berdasarkan filter Bulan dan Tahun
     */
    public function completed(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ], [
            'month.required' => 'Filter bulan wajib diisi.',
            'month.between' => 'Bulan harus bernilai antara 1 sampai 12.',
            'year.required' => 'Filter tahun wajib diisi.',
        ]);

        $query = MeterReading::with([
            'customer.user',
            'customer.ticket',
        ])
            ->where('reading_month', $request->month)
            ->where('reading_year', $request->year);

        // Least-privilege: teknisi melihat catatan meter yang ia catat sendiri
        // ATAU catatan meter pelanggan di wilayahnya (tiket yang ia tangani).
        // Sama seperti filter di `index()` — supaya daftar "sudah dicatat"
        // konsisten dengan daftar "menunggu dicatat".
        if (! $this->isPrivileged($request)) {
            $userId = $request->user()?->id;

            $query->where(function ($q) use ($userId) {
                $q->where('recorded_by', $userId)
                    ->orWhereHas('customer.ticket', function ($t) use ($userId) {
                        $t->where('user_id', $userId);
                    });
            });
        }

        $readings = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pencatatan meter.',
            'data' => $readings,
        ]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $bulan = $request->month;
        $tahun = $request->year;

        $prevMonth = $bulan === 1 ? 12 : $bulan - 1;
        $prevYear = $bulan === 1 ? $tahun - 1 : $tahun;

        $customers = Customer::with([
            'user',
            'ticket.village',
            'meterReadings' => function ($query) use ($prevMonth, $prevYear) {
                $query->where('reading_month', $prevMonth)
                    ->where('reading_year', $prevYear);
            },
            'monthlyBills' => function ($query) use ($prevMonth, $prevYear) {
                $query->where('billing_period_month', $prevMonth)
                    ->where('billing_period_year', $prevYear);
            },
            // Dipakai frontend untuk tahu baseline meter baru saat periode ini
            // sudah direset (meter baru mulai dari `new_meter_start`).
            'meterResets',
        ])
            // Hanya pelanggan dengan tiket aktif yang bisa ditagih. Tanpa
            // filter ini pelanggan terminated/blokir ikut muncul di daftar
            // pencatatan meter. (MonthlyBillController::usage() sudah pakai
            // daftar status yang sama ΓÇö disamakan di sini.)
            ->whereHas('ticket', function ($query) {
                $query->whereIn('status', ['surveyed', 'unpaid', 'processing', 'completed', 'suspended']);
            })
            ->where(function ($query) use ($bulan, $tahun) {
                // Belum ada bacaan meter periode ini.
                $query->whereDoesntHave('meterReadings', function ($q) use ($bulan, $tahun) {
                    $q->where('reading_month', $bulan)
                        ->where('reading_year', $tahun);
                })
                    // ATAU periode ini sudah direset tetapi tagihan meter baru
                    // belum dibuat — pelanggan tetap muncul supaya angka meter
                    // baru bisa diinput (membuat tagihan `baru`).
                    ->orWhere(function ($q) use ($bulan, $tahun) {
                        $q->whereHas('meterResets', function ($r) use ($bulan, $tahun) {
                            $r->where('period_month', $bulan)
                                ->where('period_year', $tahun);
                        })->whereDoesntHave('monthlyBills', function ($b) use ($bulan, $tahun) {
                            $b->where('billing_period_month', $bulan)
                                ->where('billing_period_year', $tahun)
                                ->where('meter_segment', 'baru');
                        });
                    });
            });

// Filter TEKNISI dari halaman "Data Pemakaian Air" (admin memilih
        // teknisi di kotak filter, lalu menekan "Input Pemakaian").
        //
        // Tanpa filter ini, daftar pencatatan menampilkan SEMUA pelanggan yang
        // belum dicatat meter — termasuk milik teknisi lain. Admin pun jadi
        // bisa salah memilih pelanggan: kelihatannya milik teknisi yang
        // dipilih, padahal tiketnya dipegang teknisi lain. Setelah disimpan,
        // redirect membawa `ticket.user_id` pelanggan itu, sehingga filter
        // di halaman daftar berubah ke teknisi yang berbeda dari yang
        // dipilih — persis kasus "filter Mardi (100),保存 Adi Kamari milik
        // Wanto (101), lalu halaman daftar切换 ke 101".
        //
        // Sumber kebenaran: `installation_tickets.user_id` = teknisi yang
        // menangani pelanggan (sama dengan scoping di `MonthlyBillController`).
        $requestedUserId = $request->get('user_id');

        if ($requestedUserId) {
            $customers->whereHas('ticket', function ($t) use ($requestedUserId) {
                $t->where('user_id', $requestedUserId);
            });
        }

        // Least-privilege: teknisi hanya ditagih pelanggan di WILAYAHNYA —
        // pelanggan yang tiketnya ia tangani (`installation_tickets.user_id`).
        // Sama dengan `MonthlyBillController` (index/unpaidSummary/show),
        // supaya satu pelanggan tidak terlihat milik teknisi A di halaman
        // pencatatan tapi milik teknisi B di halaman tagihan.
        //
        // Fallback: pelanggan yang pernah ia catat meter-nya (data legacy
        // tanpa `ticket.user_id`) tetap boleh, supaya pencatatan bulan
        // berikutnya tidak terkunci.
        if (! $this->isPrivileged($request)) {
            $userId = $request->user()?->id;
            $customers->where(function ($query) use ($userId) {
                $query->whereHas('ticket', function ($t) use ($userId) {
                    $t->where('user_id', $userId);
                })->orWhereHas('meterReadings', function ($q) use ($userId) {
                    $q->where('recorded_by', $userId);
                });
            });
        }

        $customers = $customers->get();

        // Untuk pelanggan yang pernah direset, hitung baseline meter periode
        // ini (meter baru bisa mulai dari 0 walau meter lama lebih besar).
        // Hanya dihitung bila memang punya reset, supaya tidak menambah
        // query untuk pelanggan normal.
        $resetService = app(MeterResetService::class);
        $customers->each(function ($customer) use ($resetService, $bulan, $tahun) {
            if ($customer->meterResets->isNotEmpty()) {
                $customer->setAttribute(
                    'meter_start_value',
                    $resetService->startMeterForPeriod($customer, $tahun, $bulan)
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pelanggan yang belum dicatat meter periode terpilih',
            'total_customers' => $customers->count(),
            'data' => $customers,
        ]);
    }

    /**
     * Simpan data pencatatan meter baru + generate tagihan + cek tunggakan
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'meter_value' => 'required|integer|min:0|max:99999999',
            // Foto meteran hanya BUKTI pendukung, bukan data wajib. Techno-
            // logi lapangan sering tidak bisa memotret meteran (lampu mati,
            // meter di bawah tanah, pelanggan tidak ada di tempat), jadi
            // kolom `photo_url` nullable dipakai: pencatatan TIDAK boleh
            // ditolak hanya karena foto tidak ada.
            'photo' => 'nullable|image|max:2048',
            'reading_month' => 'required|integer|between:1,12',
            'reading_year' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->reading_month;
        $tahun = (int) $request->reading_year;

        // Pastikan pelanggan benar-benar punya tiket & paket. BillingService
        // memakai $customer->ticket->package tanpa cek null; tanpa guard di
        // sini permintaan untuk pelanggan tanpa tiket berakhir 500.
        $customer = Customer::with(['ticket.package.waterTariffBlocks'])->findOrFail($request->customer_id);

        if (! $customer->ticket || ! $customer->ticket->package) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan ini belum memiliki paket aktif sehingga tidak bisa ditagih.',
            ], 422);
        }

        // Least-privilege: teknisi boleh mencatat untuk pelanggan di
        // WILAYAHNYA — yaitu pelanggan yang tiketnya dia tangani
        // (`installation_tickets.user_id` = dia), sama seperti admin yang
        // bebas untuk semua pelanggan. store() ikut membuat MonthlyBill dan
        // bisa menyuspend tiket, jadi tanpa cek ini teknisi bisa menagih &
        // mengubah status pelanggan milik teknisi lain.
        //
        // Fallback: kalau tiket tidak punya user_id (data legacy), teknisi
        // tetap boleh untuk pelanggan yang pernah ia catat meter-nya.
        if (! $this->isPrivileged($request)) {
            $isOwnArea = $this->isWithinTechnicianArea($customer, $request);

            $isOwnCustomer = MeterReading::where('customer_id', $customer->id)
                ->where('recorded_by', $request->user()?->id)
                ->exists();

            if (! $isOwnArea && ! $isOwnCustomer) {
                abort(403, 'Akses ditolak. Pelanggan ini bukan dalam wilayah pencatatan Anda.');
            }
        }

        $resetService = app(MeterResetService::class);

        // Reset meter pada periode ini: baris `meter_readings` yang ada
        // adalah ANGKA AKHIR METER LAMA (penutup periode). Baris itu boleh
        // diperbarui menjadi angka meter BARU. Duplikat baru terjadi kalau
        // sudah ada bacaan meter baru (angkanya beda dari akhir meter lama).
        $reset = $resetService->resetForPeriod($customer->id, $tahun, $bulan);

        $existing = MeterReading::where('customer_id', $request->customer_id)
            ->where('reading_month', $bulan)
            ->where('reading_year', $tahun)
            ->first();

        if ($existing) {
            $isOldClosing = $reset
                && (float) $existing->meter_value === (float) $reset->old_meter_final;

            if (! $isOldClosing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meter pelanggan untuk periode bulan dan tahun ini sudah dicatat.',
                ], 400);
            }
        }

        // Meter awal periode. Untuk pelanggan yang baru reset, baseline-nya
        // adalah `new_meter_start` (meter baru), bukan meter akhir bulan lalu.
        $startMeter = $resetService->startMeterForPeriod($customer, $tahun, $bulan);

        if ((float) $request->meter_value < $startMeter) {
            return response()->json([
                'success' => false,
                'message' => 'Angka meter tidak boleh lebih kecil dari meter awal periode ini ('.$startMeter.' m3).',
            ], 400);
        }

        // Catatan: validasi "meter mundur" sudah ditangani oleh
        // `startMeterForPeriod()` di atas. Untuk pelanggan yang baru reset,
        // baseline-nya `new_meter_start` (mis. 0), sehingga meter baru yang
        // lebih kecil dari angka akhir meter lama tetap diterima.

        // Foto meteran opsional: kalau tidak ada, `photo_url` disimpan null.
        $fileName = $request->hasFile('photo')
            ? $this->storeUploadedImage($request->file('photo'), 'meter-readings')
            : null;

        $settings = Setting::first();
        $batasTagihan = (int) ($settings?->batas_tagihan ?? 27);
        $toleransi = (int) ($settings?->toleransi_tunggakan ?? 0);

        // Jatuh tempo tagihan memakai `toleransi_tunggakan` (tanggal bayar
        // 1-28) di bulan SETELAH periode pemakaian. `batas_tagihan` bukan
        // untuk itu — lihat `BillingService::computeDueDate()`.
        $toleransiTagihan = $toleransi > 0 ? $toleransi : 27;

        $recordedAt = Carbon::create($tahun, $bulan, 1)
            ->setDay(min($batasTagihan, Carbon::create($tahun, $bulan, 1)->daysInMonth))
            ->setTimeFrom(now());

        $result = DB::transaction(function () use ($request, $customer, $bulan, $tahun, $fileName, $recordedAt, $toleransiTagihan, $reset, $existing) {
            if ($reset && $existing) {
                // Baris yang ada adalah angka akhir meter lama (penutup).
                // Sekarang diisi angka meter BARU untuk periode yang sama.
                $existing->update([
                    'recorded_by' => Auth::id(),
                    'meter_value' => $request->meter_value,
                    'photo_url' => $fileName ?: $existing->photo_url,
                    'recorded_at' => $recordedAt,
                ]);

                $reading = $existing->fresh();
            } else {
                $reading = MeterReading::create([
                    'customer_id' => $customer->id,
                    'recorded_by' => Auth::id(),
                    'reading_month' => $bulan,
                    'reading_year' => $tahun,
                    'meter_value' => $request->meter_value,
                    'photo_url' => $fileName,
                    'recorded_at' => $recordedAt,
                ]);
            }

            $bill = app(BillingService::class)
                ->generateForCustomer($customer, $tahun, $bulan, $toleransiTagihan);

            return ['reading' => $reading, 'bill' => $bill, 'customer' => $customer];
        });

        $suspended = false;
        if ($toleransi > 0) {
            $unpaidCount = MonthlyBill::where('customer_id', $result['customer']->id)
                ->where('status', 'unpaid')
                ->count();

            if ($unpaidCount > $toleransi && $result['customer']->ticket) {
                $result['customer']->ticket->update(['status' => 'suspended']);
                $suspended = true;
            }
        }

        $result['reading']->load(['customer.user', 'customer.ticket']);

        return response()->json([
            'success' => true,
            'message' => $suspended
                ? 'Pencatatan meter & tagihan tersimpan. Pelanggan disuspend karena tunggakan melebihi toleransi.'
                : 'Pencatatan meter & tagihan berhasil disimpan.',
            'data' => [
                'reading' => $result['reading'],
                'bill' => $result['bill'],
                'suspended' => $suspended,
            ],
        ]);
    }

    /**
     * Reset / ganti meteran (admin saja).
     *
     * Menutup meter lama pada periode ini dan menandai awal meter baru.
     * Tagihan meter lama hanya biaya pemakaian (tanpa abodemen/denda),
     * tagihan meter baru dibuat saat angka meter baru diinput.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'old_meter_final' => 'nullable|integer|min:0|max:99999999',
            'new_meter_start' => 'nullable|integer|min:0|max:99999999',
            'reason' => 'required|string|max:1000',
        ], [
            'customer_id.required' => 'Pelanggan wajib diisi.',
            'customer_id.exists' => 'Pelanggan tidak ditemukan.',
            'month.required' => 'Periode bulan wajib diisi.',
            'year.required' => 'Periode tahun wajib diisi.',
            'reason.required' => 'Alasan reset wajib diisi.',
        ]);

        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Reset meter hanya dapat dilakukan oleh admin.',
            ], 403);
        }

        $month = (int) $request->month;
        $year = (int) $request->year;

        $customer = Customer::with(['ticket.package', 'ticket.user', 'user'])
            ->findOrFail($request->customer_id);

        if (! $customer->ticket || ! $customer->ticket->package) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan ini belum memiliki paket aktif sehingga tidak bisa direset.',
            ], 422);
        }

        $service = app(MeterResetService::class);

        if ($service->resetForPeriod($customer->id, $year, $month)) {
            return response()->json([
                'success' => false,
                'message' => 'Meter pelanggan ini sudah pernah direset pada periode tersebut.',
            ], 409);
        }

        // Tagihan periode ini tidak boleh sudah lunas.
        $bill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->where('meter_segment', '!=', 'lama')
            ->first();

        if ($bill) {
            $recalc = app(\App\Services\BillRecalculationService::class);

            if ($recalc->isBillPaid($bill)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tagihan periode ini sudah lunas sehingga tidak bisa direset. Reset dapat dilakukan pada periode berikutnya.',
                ], 409);
            }
        }

        try {
            $result = $service->performReset(
                $customer,
                $year,
                $month,
                $request->filled('old_meter_final') ? (float) $request->old_meter_final : null,
                (float) ($request->new_meter_start ?? 0),
                (string) $request->reason,
                $request->user()?->id,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meter berhasil direset. Meter lama ditutup, meter baru mulai dari '.$result['reset']->new_meter_start.' m3.',
            'data' => [
                'reset' => $result['reset'],
                'bill' => $result['bill'],
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $reading = MeterReading::with(['customer.user', 'customer.ticket'])->find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        $this->ensureMeterReadingOwner($request, $reading);

        return response()->json([
            'success' => true,
            'message' => 'Detail pencatatan meter ditemukan',
            'data' => $reading,
        ]);
    }

    public function update(Request $request, string $id)
    {
        // Relasi customer.ticket dimuat karena pengecekan wilayah teknisi
        // (see AuthorizesMeterReadings::isWithinTechnicianArea) membacanya.
        $reading = MeterReading::with(['customer.ticket'])->find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        $this->ensureMeterReadingOwner($request, $reading);

        $request->validate([
            'meter_value' => 'required|integer|min:0|max:99999999',
            'photo' => 'nullable|image|max:2048',
        ]);

        $previous = MeterReading::where('customer_id', $reading->customer_id)
            ->where(function ($query) use ($reading) {
                $query->where('reading_year', '<', $reading->reading_year)
                    ->orWhere(function ($q) use ($reading) {
                        $q->where('reading_year', $reading->reading_year)
                            ->where('reading_month', '<', $reading->reading_month);
                    });
            })
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        if ($previous && $request->meter_value < $previous->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Meter tidak boleh lebih kecil dari bulan sebelumnya ('.$previous->meter_value.' m┬│)',
            ], 400);
        }

        // Rekam tagihan yang sudah dibayar tidak boleh diubah nilainya:
        // bukti fisiknya sudah jadi bagian audit.
        $paidBill = MonthlyBill::where('customer_id', $reading->customer_id)
            ->where('billing_period_month', $reading->reading_month)
            ->where('billing_period_year', $reading->reading_year)
            ->whereIn('status', ['paid', 'processing'])
            ->exists();

        if ($paidBill && (int) $request->meter_value !== (int) $reading->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Meter periode ini sudah ditagih dan dibayar sehingga tidak bisa diubah.',
            ], 409);
        }

        $reading->meter_value = $request->meter_value;

        if ($request->hasFile('photo')) {
            if ($reading->photo_url) {
                Storage::disk('public')->delete('meter-readings/'.$reading->photo_url);
            }
            $reading->photo_url = $this->storeUploadedImage($request->file('photo'), 'meter-readings');
        }

        $reading->save();

        return response()->json([
            'success' => true,
            'message' => 'Pencatatan meter berhasil diperbarui',
            'data' => $reading,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        // Relasi customer.ticket dimuat karena pengecekan wilayah teknisi
        // (see AuthorizesMeterReadings::isWithinTechnicianArea) membacanya.
        $reading = MeterReading::with(['customer.ticket'])->find($id);

        if (! $reading) {
            return response()->json([
                'success' => false,
                'message' => 'Pencatatan meter tidak ditemukan',
            ], 404);
        }

        $this->ensureMeterReadingOwner($request, $reading);

        // monthly_bills tidak punya FK ke meter_readings, jadi hapus catatan
        // tetap berhasil walau periodenya sudah dibayar. Itu menghilangkan
        // bukti audit ΓÇö tolak di sini.
        $hasSettledBill = MonthlyBill::where('customer_id', $reading->customer_id)
            ->where('billing_period_month', $reading->reading_month)
            ->where('billing_period_year', $reading->reading_year)
            ->whereIn('status', ['paid', 'processing'])
            ->exists();

        if ($hasSettledBill) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan meter ini sudah ditagih dan dibayar sehingga tidak bisa dihapus.',
            ], 409);
        }

        return $this->safeDelete(
            function () use ($reading) {
                if ($reading->photo_url) {
                    Storage::disk('public')->delete('meter-readings/'.$reading->photo_url);
                }
                $reading->delete();
            },
            'METER_READING_IN_USE',
            'Pencatatan meter',
        );
    }
}
