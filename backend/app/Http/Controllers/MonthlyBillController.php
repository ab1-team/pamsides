<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMeterReadings;
use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use App\Services\BillRecalculationService;
use App\Services\MonthlyBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MonthlyBillController extends Controller
{
    use AuthorizesMeterReadings;

    public function __construct(
        protected MonthlyBillService $monthlyBillService,
        protected BillRecalculationService $billRecalculationService,
    ) {}

    public function index(Request $request)
    {
        try {
            // Untuk LIST ringan: hanya butuh customer.user (nama + customer_code)
            // + ticket dasar untuk kebutuhan BillingDetail/print.
            // village + package sengaja DIBUANG dari list (tidak dipakai di tabel manapun);
            // tersedia via endpoint /monthly-bills/{id} untuk modal yang butuh lengkap.
            $query = MonthlyBill::with([
                'customer:id,user_id,ticket_id,customer_code',
                'customer.user:id,name',
                'customer.ticket:id,applicant_name,phone,address',
                'billPayments:id,bill_id,amount_paid,paid_at,confirmed_by',
            ])->orderBy('billing_period_year', 'desc')
                ->orderBy('billing_period_month', 'desc');

            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->status && in_array($request->status, ['unpaid', 'paid'], true)) {
                $query->where('status', $request->status);
            }

            if ($request->month) {
                $query->where('billing_period_month', $request->month);
            }

            if ($request->year) {
                $query->where('billing_period_year', $request->year);
            }

            // Server-side search: nama pelanggan (user.name / ticket.applicant_name),
            // customer_code, atau invoice id. Tetap cepat via indeks & like berawalan.
            $q = trim((string) $request->get('q', ''));
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->whereHas('customer.user', function ($u) use ($q) {
                        $u->where('name', 'like', $q.'%');
                    })
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('customer_code', 'like', $q.'%')
                                ->orWhereHas('ticket', function ($t) use ($q) {
                                    $t->where('applicant_name', 'like', $q.'%');
                                });
                        });

                    // Pencarian exact match invoice ID agar bisa cari "INV-123" / "123"
                    $digits = ltrim($q, '0');
                    if ($digits !== '' && ctype_digit($digits)) {
                        $w->orWhere('id', (int) $digits);
                    }
                });
            }

            $all = filter_var($request->get('all'), FILTER_VALIDATE_BOOLEAN);

            if ($all) {
                $bills = $query->get();
                $paginatorMeta = [
                    'mode' => 'all',
                    'total' => $bills->count(),
                ];
            } else {
                $perPage = (int) $request->get('per_page', 50);
                $perPage = max(1, min($perPage, 200));

                $page = $query->paginate($perPage);
                $bills = $page->getCollection();

                $paginatorMeta = [
                    'mode' => 'paginate',
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                ];
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat daftar tagihan: '.$e->getMessage(),
            ], 500);
        }

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
                    'bill_payments' => ($b->billPayments ?? collect())->map(fn ($p) => [
                        'id' => $p->id ?? null,
                        'amount_paid' => $p->amount_paid ?? null,
                        'confirmed_by' => $p->confirmed_by ?? null,
                        'paid_at' => $p->paid_at ?? null,
                    ])->values()->all(),
                    'customer' => $customer ? [
                        'id' => $customer->id ?? null,
                        'customer_code' => $customer->customer_code ?? null,
                        'initial_meter_reading' => $customer->initial_meter_reading ?? null,
                        'activated_at' => $customer->activated_at ?? null,
                        'meter_photo_url' => $customer->meter_photo_url ?: null,
                        'user' => $user ? [
                            'id' => $user->id ?? null,
                            'name' => $user->name ?? null,
                        ] : null,
                        'ticket' => $ticket ? [
                            'id' => $ticket->id ?? null,
                            'applicant_name' => $ticket->applicant_name ?? null,
                            'phone' => $ticket->phone ?? null,
                            'address' => $ticket->address ?? null,
                        ] : null,
                    ] : null,
                ];
            } catch (\Throwable $e) {
                return [
                    'id' => $b->id ?? null,
                    'customer_id' => $b->customer_id ?? null,
                    'billing_period_month' => $b->billing_period_month ?? null,
                    'billing_period_year' => $b->billing_period_year ?? null,
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
            'meta' => $paginatorMeta ?? null,
        ]);
    }

    public function usage(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2000',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $authUser = Auth::user();
        $requestedUserId = $request->get('user_id');

        // Authorization:
        // - Admin boleh query user_id siapa saja (atau tanpa filter = semua teknisi)
        // - Teknisi HANYA boleh query miliknya sendiri; ignore user_id lain
        if ($authUser->role === 'teknisi') {
            $userId = (int) $authUser->id;
        } elseif ($authUser->role === 'admin') {
            $userId = $requestedUserId ? (int) $requestedUserId : null;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data pemakaian.',
            ], 403);
        }

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $query = Customer::with(['user', 'ticket.package', 'ticket.village', 'ticket.user'])
            ->whereHas('ticket', function ($q) use ($userId) {
                $q->whereIn('status', ['surveyed', 'unpaid', 'processing', 'completed', 'suspended']);
                if (! empty($userId)) {
                    $q->where('user_id', $userId);
                }
            });

        $customers = $query->get();

        // Batch-load bills periode ini + bill_payments + transactions (reverence bill_payment)
        // untuk menentukan status "Dibayar" dari tabel transactions (bukan dari monthly_bills.status).
        $currentBills = MonthlyBill::whereIn('customer_id', $customers->pluck('id'))
            ->where('billing_period_month', $month)
            ->where('billing_period_year', $year)
            ->get()
            ->keyBy('customer_id');

        // Periode N-1 - sumber denda. Denda dihitung saat tagihan dibuat bila
        // tagihan bulan sebelumnya masih `unpaid`, jadi bulan N-1 inilah yang
        // menunggak. Cukup dihitung dari index bulan, tidak perlu query tagihan
        // N-1 karena label bulan ini sama untuk semua pelanggan pada periode
        // yang sedang difilter.
        $prevMonthIndex = ($month === 1 ? 12 : $month - 1);
        $prevYearIndex = ($month === 1 ? $year - 1 : $year);

        // Meter kumulatif terakhir yang diketahui SEBELUM periode ini.
        //
        // Dipakai untuk mengisi kolom "Awal" saat tagihan periode ini belum
        // ada — mis. form input Oktober yang belum digenerate tagihannya.
        // Tanpa ini, `meter_awal` jatuh ke `customers.initial_meter_reading`
        // yang 0 semua untuk data legacy, sehingga form cetak Oktober tampil
        // semua nol padahal September sudah ada angkanya.
        //
        // Yang dicari adalah NILAI TERAKHIR yang diketahui, bukan strictly
        // bulan N-1: ada pelanggan yang tagihannya bulan September belum
        // dibuat (mis. tiket suspended), jadi meter terakhirnya masih
        // Agustus. Ambil N-1 saja akan mengembalikan 0 untuk pelanggan itu.
        //
        // Sumber: tagihan (meter_reading_end — sudah final lewat
        // BillingService) dan meter_readings (meter_value). Keduanya
        // cumulative, jadi pilih yang periodenya paling baru.
        //
        // CATATAN PERFORMA — tiga hal yang bikin halaman ini lambat, dan
        // ketiganya sudah ditangani di sini:
        //
        // 1. Window function ROW_NUMBER() memaksa MySQL menyortir SEMUA baris
        //    di dalam partisi sebelum mengambil rn = 1 — artinya memproses
        //    30rb+ baris tagihan per request. Diganti agregat MAX(periode).
        //
        // 2. Daftar id pelanggan dipasang sebagai placeholder `?` satu per
        //    id. Dengan 1.600+ placeholder, MySQL harus mem-parse ulang query
        //    tiap kali (3 detik!). Daftar id TIDAK ditulis sebagai IN list
        //    karena daftar itu panjang; filternya dilakukan di PHP.
        //
        // 3. Query N+1: `meterReadings()` dipanggil di dalam loop per
        //    pelanggan (1.600+ query). Diganti batch-load di atas.
        //
        // Periode dipaket jadi satu angka (tahun*100 + bulan) supaya
        // perbandingan "terakhir" cukup satu operasi MAX — dan tidak perlu
        // CASE WHEN untuk menangani pergantian tahun.
        //
        // Catatan soft-delete: MonthlyBill punya global scope deleted_at.
        // Scope itu TIDAK bisa dipakai di query raw ini (dia akan menyuntik
        // `where monthly_bills.deleted_at is null` ke alias yang tidak punya
        // kolom itu — "Unknown column"). Karena itu filter soft-delete
        // ditulis manual (lihat `deleted_at is null` di bawah), supaya
        // perilakunya sama persis dengan model.
        $maxPeriod = $year * 100 + $month;

        $latestBillRows = collect(DB::select(
            'SELECT lbb.customer_id, lbb.meter_reading_end, lbb.billing_period_year, lbb.billing_period_month
             FROM (
                SELECT customer_id, MAX(billing_period_year * 100 + billing_period_month) AS maxp
                FROM monthly_bills
                WHERE deleted_at IS NULL
                  AND (billing_period_year * 100 + billing_period_month) < ?
                GROUP BY customer_id
             ) lbm
             JOIN monthly_bills lbb
               ON lbb.customer_id = lbm.customer_id
              AND lbb.billing_period_year = FLOOR(lbm.maxp / 100)
              AND lbb.billing_period_month = lbm.maxp % 100
              AND lbb.deleted_at IS NULL',
            [$maxPeriod]
        ))->keyBy('customer_id');

        $latestReadingRows = collect(DB::select(
            'SELECT lrb.customer_id, lrb.meter_value, lrb.reading_year, lrb.reading_month
             FROM (
                SELECT customer_id, MAX(reading_year * 100 + reading_month) AS maxp
                FROM meter_readings
                WHERE (reading_year * 100 + reading_month) < ?
                GROUP BY customer_id
             ) lrm
             JOIN meter_readings lrb
               ON lrb.customer_id = lrm.customer_id
              AND lrb.reading_year = FLOOR(lrm.maxp / 100)
              AND lrb.reading_month = lrm.maxp % 100',
            [$maxPeriod]
        ))->keyBy('customer_id');

        // Batch-load pencatatan meter PERIODE INI. Tanpa ini, tiap pelanggan
        // memicu 1 query sendiri (N+1) — 1.600+ query untuk satu halaman.
        $currentReadings = MeterReading::whereIn('customer_id', $customers->pluck('id'))
            ->where('reading_month', $month)
            ->where('reading_year', $year)
            ->get()
            ->keyBy('customer_id');

        $billPayments = BillPayment::whereIn('bill_id', $currentBills->pluck('id'))
            ->get();

        // Map: bill_payment.id → bill_id (transactions.reverence_id = bill_payment.id)
        $bpIdToBillId = [];
        // Map: monthly_bills.id → bill_payments.amount_paid (fallback jika jurnal transaksi kosong / legacy data)
        $amountPaidFallbackByBillId = [];
        foreach ($billPayments as $bp) {
            $bpIdToBillId[$bp->id] = $bp->bill_id;
            $amountPaidFallbackByBillId[$bp->bill_id] = (float) ($amountPaidFallbackByBillId[$bp->bill_id] ?? 0)
                + (float) ($bp->amount_paid ?? 0);
        }

        // Ambil nominal bayar dari tabel transactions:
        // SUM(transactions.saldo) WHERE reverence_type='bill_payment' AND reverence_id IN (bill_payment_ids)
        // Hasil: total bayar per bill_id (key: monthly_bills.id → total saldo).
        $paidAmountByBill = Transaction::where('reverence_type', 'bill_payment')
            ->whereIn('reverence_id', array_keys($bpIdToBillId))
            ->selectRaw('reverence_id, SUM(saldo) as total')
            ->groupBy('reverence_id')
            ->get();

        $paidAmountByBillId = [];
        foreach ($paidAmountByBill as $row) {
            $billId = $bpIdToBillId[$row->reverence_id] ?? null;
            if ($billId !== null) {
                $paidAmountByBillId[$billId] = (float) ($paidAmountByBillId[$billId] ?? 0) + (float) $row->total;
            }
        }

        $items = $customers->map(function ($customer) use ($month, $year, $currentBills, $currentReadings, $latestBillRows, $latestReadingRows, $prevMonthIndex, $prevYearIndex, $paidAmountByBillId, $amountPaidFallbackByBillId) {
            $reading = $currentReadings->get($customer->id);

            $bill = $currentBills->get($customer->id);

            // Nominal Dibayar: prioritas dari transactions.saldo; fallback ke bill_payments.amount_paid
            // untuk data legacy yang monthly_bills.status='paid' tapi jurnal transaksi belum ada / 0.
            $txPaid = $bill ? (float) ($paidAmountByBillId[$bill->id] ?? 0) : 0;
            $bpPaid = $bill ? (float) ($amountPaidFallbackByBillId[$bill->id] ?? 0) : 0;
            $billStatusPaid = $bill && strtolower((string) $bill->status) === 'paid';

            $paidAmount = $txPaid > 0
                ? $txPaid
                : ($billStatusPaid && $bpPaid > 0 ? $bpPaid : 0);

            // Denda sudah final di monthly_bills.penalty_amount dan sudah termasuk
            // di total_amount (dihitung saat tagihan dibuat). Jangan ditambah runtime:
            // billing akan dobel hitung dan nominal yang tersimpan jadi tidak cocok tagihan.
            $penalty = (float) ($bill?->penalty_amount ?? 0);
            $baseTotal = (float) ($bill?->total_amount ?? 0);

            // Tagihan nihil: total Rp 0, jadi TIDAK ada pembayaran — dan memang
            // tidak perlu ada. Generator menandainya `paid` supaya tidak
            // memenuhi lonceng "tagihan belum bayar" dan tidak menjatuhkan
            // tiket jadi suspended (lihat BillingService::generateForCustomer).
            //
            // Kasus ini harus ikut PAID. Kalau hanya mengandalkan `$paidAmount`,
            // tagihan ini akan tampil UNPAID di halaman ini sementara sudah
            // `paid` di DB dan tampil LUNAS di Daftar Tagihan — dua halaman
            // berselisih soal status tagihan yang sama.
            //
            // Syaratnya `$billStatusPaid` HARUS ikut diperiksa, bukan totalnya
            // saja. Total Rp 0 belum tentu berarti lunas: kalau pelanggan
            // memakai air tapi tagihannya nol (karena paketnya belum punya
            // blok tarif), generator sengaja membiarkannya `unpaid` supaya
            // kelihatan dan bisa dibetulkan admin. Kalau tagihan seperti itu
            // ikut-safe di sini, ia akan tampil PAID di halaman ini tapi
            // UNPAID di Daftar Tagihan — persis masalah yang sama lagi, cuma
            // di arah sebaliknya, dan konfigurasi paket yang rusak ikut
            // tersembunyi.
            $isNihilBill = $bill && $baseTotal <= 0 && $billStatusPaid;

            // status = 'PAID' bila ada nominal bayar (transaksi ATAU fallback
            // bill_payment), ATAU tagihannya nihil yang otomatis lunas.
            // Kalau bill belum ada (belum digenerate), status PENDING.
            // Kalau bill sudah ada tapi reading kosong / belum diinput, status UNPAID.
            $statusLabel = ($paidAmount > 0 || $isNihilBill)
                ? 'PAID'
                : ($bill || $reading ? 'UNPAID' : 'PENDING');

            // Meter awal periode ini.
            //
            // Kalau tagihan periode ini sudah ada, `meter_reading_start`nya
            // sudah final (dihitung BillingService dari reading N-1) — pakai
            // itu.
            //
            // Kalau tagihannya belum ada (mis. Oktober yang belum digenerate),
            // "Awal" harus tetap angka meter kumulatif terakhir yang diketahui:
            // meter akhir September (atau periode terakhir yang ada). Form ini
            // dipakai teknisi untuk mencatat meter akhir, jadi kolom Awal WAJIB
            // terisi — kalau diisi 0, teknisi akan salah menghitung pemakaian.
            //
            // `customers.initial_meter_reading` hanya dipakai sebagai fallback
            // terakhir: untuk data legacy nilainya 0 semua, dan 0 di sini
            // berarti "belum pernah ada meter" — bukan "meter mulai dari 0".
            //
            // Perhatikan: pemilihan sumber memakai `?->` + null coalesce, BUKAN
            // `??` pada nilai numeriknya. `0` itu angka meter yang sah (meter
            // belum pernah dipakai), jadi harus menghormati 0 kalau ada — jangan
            // melompat ke sumber berikutnya hanya karena nilainya nol.
            $meterAwal = $bill?->meter_reading_start;

            if ($meterAwal === null) {
                $prevBill = $latestBillRows->get($customer->id);
                $prevReading = $latestReadingRows->get($customer->id);

                // Pilih sumber dengan periode paling baru. Kalau keduanya satu
                // periode, tagihan menang (nilainya sudah final dan konsisten
                // dengan tagihan yang dicetak).
                $billPeriod = $prevBill
                    ? ((int) $prevBill->billing_period_year * 100 + (int) $prevBill->billing_period_month)
                    : null;
                $readingPeriod = $prevReading
                    ? ((int) $prevReading->reading_year * 100 + (int) $prevReading->reading_month)
                    : null;

                if ($billPeriod !== null && ($readingPeriod === null || $billPeriod >= $readingPeriod)) {
                    $meterAwal = $prevBill->meter_reading_end;
                } elseif ($readingPeriod !== null) {
                    $meterAwal = $prevReading->meter_value;
                } else {
                    $meterAwal = $customer->initial_meter_reading;
                }
            }

            return [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'nama' => optional($customer->user)->name ?? $customer->ticket?->applicant_name,
                'nik' => $customer->ticket?->nik,
                'alamat' => $customer->ticket?->address,
                'rt' => $customer->ticket?->rt,
                'rw' => $customer->ticket?->rw,
                'dusun' => $customer->ticket?->village?->hamlet_name,
                'desa' => $customer->ticket?->village?->village_name,
                'package_name' => $customer->ticket?->package?->name,
                'meter_awal' => $meterAwal,
                'meter_akhir' => $bill?->meter_reading_end ?? $reading?->meter_value,
                'pemakaian' => $bill?->usage_m3,
                'pemakaian_charge' => $bill?->usage_charge ?? 0,
                'tagihan' => $baseTotal > 0 ? $baseTotal : (($bill?->usage_charge ?? 0) + ($bill?->abodemen ?? 0) + $penalty),
                'denda' => $penalty,
                // Bulan yang jadi sumber denda = N-1, hanya dikirim kalau denda
                // memang terpasang. Berasal dari index periode, bukan dari
                // `due_date`, supaya tetap benar walau batas tagihan diubah admin.
                'denda_bulan' => $penalty > 0
                    ? $this->namaBulanIndonesia($prevMonthIndex, $prevYearIndex)
                    : null,
                'abodemen' => $bill?->abodemen,
                'status' => $statusLabel,
                'is_paid' => $statusLabel === 'PAID',
                'paid_amount' => $paidAmount,
                'due_date' => $bill?->due_date,
                // Periode tagihan ikut dikirim supaya frontend tidak
                // perlu menebak dari filter halaman yang sedang aktif.
                // Tanpa ini, edit dari tab lain bisa salah periode.
                'billing_period_month' => $bill?->billing_period_month,
                'billing_period_year' => $bill?->billing_period_year,
                'reading_photo' => $reading?->photo_url ?: null,
                'reading_recorded_at' => $reading?->recorded_at,
                'technician_id' => $customer->ticket?->user_id,
                'technician_name' => $customer->ticket?->user?->name,
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
            // Tanggal pembayaran dari date picker FE. Divalidasi sebagai date
            // supaya string rusak ditolak 422 (bukan 500 dari Carbon::parse).
            //
            // `before_or_equal:today` menolak tanggal masa depan: uangnya belum
            // masuk, jadi membukukannya sekarang membuat saldo kas hari ini
            // meleset. Frontend juga membatasi kalender, tapi validasi di sini
            // yang menjamin data — endpoint bisa dipanggil langsung.
            'paid_at_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $bill = MonthlyBill::findOrFail($id);

        if ($bill->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan sudah dibayar',
            ], 400);
        }

        $bill->load('customer.user', 'customer.ticket');

        $abodemen = (float) ($bill->abodemen ?? 0);
        $usageCharge = (float) ($bill->usage_charge ?? 0);
        $denda = (float) ($bill->penalty_amount ?? 0);
        $relasi = $bill->customer?->customer_code ?? 'Bill #'.$bill->id;
        $namaPelanggan = trim((string) ($bill->customer?->user?->name ?: $bill->customer?->ticket?->applicant_name));

        $userId = Auth::id();

        // Akun DEBET (kas masuk) mengikuti metode pembayaran:
        //   - cash     → 1.1.01.01 (Kas Tunai)
        //   - transfer → 1.1.01.03 (Kas di Bank BRI)
        // Default ke Kas Tunai supaya aman untuk data lama / null.
        $paymentMethod = $request->input('payment_method', 'cash');
        $accountDebetKas = $paymentMethod === 'transfer' ? '1.1.01.03' : '1.1.01.01';

        // Tanggal pembayaran: pakai dari FE kalau ada & valid, fallback ke now().
        // Sudah divalidasi rule `date` di atas, jadi parse di sini aman;
        // try-catch tetap disimpan sebagai jaring pengaman.
        try {
            $paidAtDate = $request->filled('paid_at_date')
                ? \Carbon\Carbon::parse($request->paid_at_date)->startOfDay()
                : \Carbon\Carbon::now();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal pembayaran tidak valid.',
            ], 422);
        }

        // Sumber kebenaran "apakah tagihan ini sudah pernah dibukukan sebagai
        // piutang": ADA JURNAL `overdue_bill` untuk tagihan ini — bukan
        // `$denda > 0`.
        //
        // Proxy lama salah karena menebak dari komponen tagihan: tagihan
        // menunggak yang dendanya 0 (cuma abodemen, atau denda terlewat) akan
        // dianggap TIDAK menunggak dan dikreditkan ke pendapatan, padahal
        // piutangnya sudah tercatat saat generate tunggakan. Akibatnya
        // pendapatan diakui dua kali: sekali saat piutang dibukukan, sekali
        // lagi saat pembayaran — padahal yang kedua harus menutup piutang.
        //
        // Jurnal overdue_bill adalah satu-satunya fakta yang bisa dipercaya:
        // kalau baris ini ada, tagihan ini sudah membebani akun 1.1.03.01 dan
        // pembayarannya wajib menutup piutang, bukan menambah pendapatan.
        $overdueJournalRows = Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->get(['id', 'account_debet', 'account_kredit', 'saldo', 'tgl_transaksi']);

        // Hanya akun PIUTANG USAHA yang relevan. Jurnal overdue_bill lain
        // (kalau ada di masa depan) tidak boleh mengubah routing pembayaran.
        $piutangRows = $overdueJournalRows
            ->filter(fn ($t) => $t->account_debet === '1.1.03.01')
            ->keyBy('account_kredit');

        $hasPiutang = $piutangRows->isNotEmpty();

        // Tagihan dianggap "menunggak" untuk keperluan routing kalau piutangnya
        // benar-benar tercatat. Dendanya sendiri TIDAK boleh jadi penentu:
        // denda dihitung saat generate tunggakan, jadi tagihan bisa punya piutang
        // tanpa sisa denda maupun sebaliknya.
        $isTunggakan = $hasPiutang;

        // Deteksi ADVANCE dari TANGGAL BAYAR, bukan dari ada/tidaknya jurnal piutang.
        //
        // Definisi bisnisnya tetap sama seperti versi lama: uang diterima
        // SEBELUM ambang generate tunggakan, sehingga tagihan ini pada saat
        // dibayar belum/seharusnya belum punya piutang.
        $toleransiTgl = (int) (\App\Models\Setting::first()?->toleransi_tunggakan ?? 0);
        $isAdvancePayment = false;
        $thresholdDate = null;
        if ($toleransiTgl >= 1) {
            $thresholdYear = (int) ($bill->billing_period_year ?? 0);
            $thresholdMonth = (int) ($bill->billing_period_month ?? 0);
            // Fallback ke due_date kalau billing_period tidak ada (legacy data).
            if ($thresholdYear < 1 || $thresholdMonth < 1) {
                $dueRef = \Carbon\Carbon::parse($bill->due_date);
                $thresholdYear = $dueRef->year;
                $thresholdMonth = $dueRef->month;
            }
            $daysInThresholdMonth = \Carbon\Carbon::create($thresholdYear, $thresholdMonth, 1)->daysInMonth;
            $effectiveDay = min($toleransiTgl, $daysInThresholdMonth);
            $thresholdDate = \Carbon\Carbon::create($thresholdYear, $thresholdMonth, $effectiveDay)->startOfDay();
            $isAdvancePayment = $paidAtDate->lt($thresholdDate);
        }

        // Kombinasi yang TIDAK mungkin secara logika tapi tetap dijaga: tagihan
        // terklasifikasi advance (tanggal bayar < ambang) TETAPI punya jurnal
        // piutang. Terjadi kalau generate tunggakan telat, atau tanggal bayar
        // direkam mundur setelah piutang tercatat. Di kasus ini piutang wajib
        // dibalik supaya tidak menggantung — lewat UPDATE, bukan delete.
        $needsPiutangRevert = $piutangRows->isNotEmpty() && $isAdvancePayment;

        // Akun kredit saat pembayaran:
        //   - ada piutang tercatat → menutup piutang (1.1.03.01), komponen yang
        //     TIDAK punya piutang (mis. denda) tetap ke pendapatan/denda.
        //   - tidak ada piutang       → pendapatan langsung.
        //
        // Pemetaan per komponen, bukan satu flag global: tagihan menunggak bisa
        // punya piutang abodemen saja (pemakaian 0), atau sebaliknya. Kredit
        // ke 1.1.03.01 hanya untuk komponen yang BENAR-BENAR punya baris
        // piutang; kalau tidak, jurnal piutang dan jurnal pembayaran tidak akan
        // pernah balance.
        $revenueAbodemen = $piutangRows->has('4.1.01.02') ? '1.1.03.01' : '4.1.01.02';
        $revenuePemakaian = $piutangRows->has('4.1.01.03') ? '1.1.03.01' : '4.1.01.03';

        $restoredTicket = false;
        $overdueRevertedCount = 0;
        // Metode bayar ikut ditulis di keterangan jurnal supaya saat cetak laporan
        // kas / buku besar, sumber dana (Tunai vs Transfer BRI) jelas terlihat.
        $methodLabel = $paymentMethod === 'transfer' ? 'Transfer BRI' : 'Tunai';

        // Keterangan memuat periode tagihan + nama + kode, lalu metode bayar:
        //   "Tagihan Denda bulan Agustus 2026 an. Yuli Iswanto (1.04.0996) - Tunai"
        $ket = fn (string $jenis) => $bill->paymentDescription($jenis, $relasi, $namaPelanggan)
            ? $bill->paymentDescription($jenis, $relasi, $namaPelanggan).' - '.$methodLabel
            : $jenis.' - '.$relasi.' ('.$methodLabel.')';

        $ketAbodemen = $ket('Abodemen');
        $ketPemakaian = $ket('Pemakaian');
        $ketDenda = $ket('Denda');

        // Reklasifikasi jurnal piutang + pembuatan jurnal pembayaran dilakukan
        // di dalam SATU DB::transaction. Kalau closure berikutnya gagal,
        // perubahan piutang ikut rollback sehingga jurnal piutang dan jurnal
        // pembayaran tidak pernah merusak transaksi setengah jadi.
        try {
            DB::transaction(function () use ($bill, $request, $paidAtDate, $abodemen, $usageCharge, $denda, $isTunggakan, $isAdvancePayment, $needsPiutangRevert, $revenueAbodemen, $revenuePemakaian, $relasi, $userId, $accountDebetKas, $methodLabel, $ketAbodemen, $ketPemakaian, $ketDenda, &$restoredTicket, &$overdueRevertedCount, &$payment) {
            // Kunci baris tagihan (SELECT ... FOR UPDATE) supaya 2 request bersamaan
            // tidak bisa sama-sama membaca status 'unpaid' lalu membayarkan dua
            // kali. Cek status diulang di sini dengan baris terkunci — sebelumnya
            // pengecekan terjadi DI LUAS transaksi sehingga race condition.
            $bill = MonthlyBill::lockForUpdate()->findOrFail($bill->id);

            if ($bill->status === 'paid') {
                throw new \RuntimeException('TAGIHAN_SUDAH_DIBAYAR');
            }

            if ($needsPiutangRevert) {
                // Reklasifikasi, BUKAN hapus.
                //
                // Baris overdue_bill diubah sehingga account_debet-nya bukan lagi
                // Piutang Usaha tapi akun kas yang sama dengan metode pembayaran.
                // Efeknya untuk saldo: piutang naik lagi (asli generate) lalu
                // turun lagi (reklas ini) = 0, dan kas naik. Jurnal pembayaran
                // di bawah tetap mengutup piutang, jadi total akhir tetap nol.
                //
                // Kenapa tidak `delete()`:
                //   1. Jurnal piutang adalah jejak audit. Menghapusnya
                //      menghilangkan bukti tagihan pernah masuk piutang, sehingga
                //      selisih kas tidak bisa direkonstruksi.
                //   2. `billing:generate-overdue-transactions` dijalankan ulang
                //      setiap login. Dedup-nya berbasis (reverence_type,
                //      reverence_id, account_kredit). Kalau baris di-soft-delete,
                //      global scope SoftDeletes masih menambahkannya sebagai
                //      "sudah punya jurnal" → jurnal tidak pernah dibuat ulang
                //      dan piutang hilang selamanya dari pembukuan.
                //   3. `->delete()` memicu observer `deleted` yang menjalankan
                //      SUM() penuh per baris; `->save()` di sini dipanggil
                //      maksimum 2 baris, jauh lebih murah.
                //
                // Kolom `account_kredit` SENGAJA tidak diubah: baris tetap
                // berisi kode pendapatan asli supaya auditor bisa melihat
                // piutang mana yang direklas. Yang berubah hanya sisi debet,
                // sehingga piutang di 1.1.03.01 turun dan kas naik.
                //
                // Ditulis lewat DB::table() agar observer `updated` tidak
                // menjalankan agregasi `amount` dua kali; trigger MySQL
                // tetap yang pakai (sudah suspend di caller bila perlu).
                $trxIdsRevert = Transaction::where('reverence_type', 'overdue_bill')
                    ->where('reverence_id', $bill->id)
                    ->where('account_debet', '1.1.03.01')
                    ->pluck('id');

                foreach ($trxIdsRevert as $revertId) {
                    DB::table('transactions')->where('id', $revertId)->update([
                        'account_debet' => $accountDebetKas,
                        'tgl_transaksi' => $paidAtDate->toDateString(),
                        'updated_at' => now(),
                    ]);
                    $overdueRevertedCount++;
                }
            }

            $bill->update(['status' => 'paid']);

            $payment = BillPayment::create([
                'bill_id' => $bill->id,
                'amount_paid' => $request->amount_paid ?? $bill->total_amount,
                'confirmed_by' => $userId,
                'paid_at' => $paidAtDate,
            ]);

            // ── Jurnal Pembayaran ──
            // Kumpulkan id jurnal dulu, lalu isi kolom `urutan` dengan SATU
            // bulk update di akhir.
            //
            // Sebelumnya tiap baris melakukan `->update(['urutan' => $trx->id])`
            // sendiri. Update itu memicu observer `syncAmount` (agregasi SUM()
            // penuh ~1 detik) + trigger `update_amount_debit` MySQL, padahal
            // `urutan` tidak memengaruhi saldo sama sekali. Itu menambah ~5 detik
            // per pembayaran dan ikut memicu "Lock wait timeout".
            $trxIds = [];

            // Abodemen
            if ($abodemen > 0) {
                $trx = Transaction::create([
                    'tgl_transaksi' => $payment->paid_at,
                    'account_debet' => $accountDebetKas,
                    'account_kredit' => $revenueAbodemen,
                    'transaction_group' => null,
                    'reverence_type' => 'bill_payment',
                    'reverence_id' => $payment->id,
                    'keterangan_transaksi' => $ketAbodemen,
                    'relasi' => $relasi,
                    'saldo' => $abodemen,
                    'id_user' => $userId,
                ]);
                $trxIds[] = $trx->id;
            }

            // Tagihan Pemakaian
            if ($usageCharge > 0) {
                $trx = Transaction::create([
                    'tgl_transaksi' => $payment->paid_at,
                    'account_debet' => $accountDebetKas,
                    'account_kredit' => $revenuePemakaian,
                    'transaction_group' => null,
                    'reverence_type' => 'bill_payment',
                    'reverence_id' => $payment->id,
                    'keterangan_transaksi' => $ketPemakaian,
                    'relasi' => $relasi,
                    'saldo' => $usageCharge,
                    'id_user' => $userId,
                ]);
                $trxIds[] = $trx->id;
            }

            // Denda (hanya tunggakan)
            if ($denda > 0) {
                $trx = Transaction::create([
                    'tgl_transaksi' => $payment->paid_at,
                    'account_debet' => $accountDebetKas,
                    'account_kredit' => '4.1.01.04',
                    'transaction_group' => null,
                    'reverence_type' => 'bill_payment',
                    'reverence_id' => $payment->id,
                    'keterangan_transaksi' => $ketDenda,
                    'relasi' => $relasi,
                    'saldo' => $denda,
                    'id_user' => $userId,
                ]);
                $trxIds[] = $trx->id;
            }

            // Satu statement untuk semua baris (tidak lewat observer Eloquent,
            // sehingga tidak memicu agregasi `amount` per baris).
            if ($trxIds !== []) {
                foreach ($trxIds as $tid) {
                    DB::table('transactions')->where('id', $tid)->update(['urutan' => $tid]);
                }
            }

            // ── Auto-restore: jika tiket suspended & semua bill paid → kembalikan ke completed ──
            $customer = $bill->customer;
            if ($customer && $customer->ticket && $customer->ticket->status === 'suspended') {
                $stillUnpaid = MonthlyBill::where('customer_id', $customer->id)
                    ->where('status', 'unpaid')
                    ->count();

                if ($stillUnpaid === 0) {
                    $customer->ticket->update(['status' => 'completed']);
                    $restoredTicket = true;
                }
            }
            });
        } catch (\RuntimeException $e) {
            // Double-pay: request lain baru saja membayar tagihan yang sama.
            if ($e->getMessage() === 'TAGIHAN_SUDAH_DIBAYAR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tagihan sudah dibayar',
                ], 400);
            }

            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Deadlock / lock wait timeout / koneksi putus di tengah jalan.
            // Balas 503 (retryable) dengan pesan yang bisa dibaca FE, jangan 500
            // kosong supaya user bisa menekan ulang dengan aman (transaksi sudah
            // di-rollback oleh DB::transaction, jadi tidak ada data setengah jadi).
            $sqlState = $e->getCode();

            if (in_array($sqlState, ['40001', '40P01', 'HY000'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server sedang sibuk, pembayaran gagal diproses. Silakan coba lagi.',
                ], 503);
            }

            throw $e;
        }

        $bill->load('customer.user', 'customer.ticket.package');

        return response()->json([
            'success' => true,
            'message' => $restoredTicket
                ? 'Pembayaran berhasil. Pelanggan otomatis diaktifkan kembali karena seluruh tagihan telah lunas.'
                : 'Pembayaran berhasil dikonfirmasi',
            'data' => [
                'bill' => $bill,
                'payment' => $payment,
                'ticket_restored' => $restoredTicket,
                // Berapa baris jurnal piutang yang direklas jadi kas masuk.
                // 0 = tidak ada piutang yang perlu dibalik (pembayaran normal).
                'piutang_direklas' => $overdueRevertedCount,
            ],
        ]);
    }

    /**
     * Ringkasan tagihan yang belum dibayar untuk badge icon lonceng di navbar.
     *
     * Cakupannya mengikuti role pemanggil:
     *   - admin/teknisi → seluruh pelanggan yang menunggak, dikelompokkan per
     *     pelanggan (siapa saja yang punya tagihan belum bayar)
     *   - pelanggan     → hanya tagihannya sendiri
     *
     * Agregasi dilakukan di SQL, bukan di PHP. Tabel monthly_bills pada
     * instalasi nyata bisa mencapai puluhan ribu baris `unpaid`, jadi
     * `->get()` lalu dijumlahkan di memory akan menahan seluruh tabel di
     * RAM setiap kali navbar dimuat. Query di bawah mengembalikan satu baris
     * per pelanggan beserta totalnya.
     *
     * Daftar yang dikirim dibatasi 15 baris: panel lonceng adalah daftar
     * sekilas, bukan pengganti halaman Daftar Tagihan yang sudah punya filter
     * & pagination. Angka di `summary` tetap dihitung dari seluruh data.
     */
    public function unpaidSummary(Request $request)
    {
        $user = Auth::user();

        // Route sengaja tidak dibatasi `role:` (lihat routes/api.php) supaya
        // admin, teknisi, dan pelanggan bisa memakai endpoint yang sama.
        // Role lain harus ditolak di sini.
        if (! in_array($user->role, ['admin', 'teknisi', 'pelanggan'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak memiliki akses ke ringkasan tagihan.',
            ], 403);
        }

        $isStaff = in_array($user->role, ['admin', 'teknisi'], true);
        $limit = 15;
        $today = now()->toDateString();

        $emptySummary = [
            'unpaid_count' => 0,
            'unpaid_total' => 0.0,
            'overdue_count' => 0,
            'customer_count' => 0,
        ];

        // Pelanggan: hanya tagihan miliknya sendiri.
        if (! $isStaff) {
            $customerId = Customer::where('user_id', $user->id)->value('id');

            // Pelanggan tanpa record Customer tidak punya tagihan sama sekali
            // (tagihan menempel ke customers). Kembalikan kosong dengan 200
            // supaya badge tidak error, bukan 403 yang membuat navbar gagal.
            if (! $customerId) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'summary' => $emptySummary,
                ]);
            }

            $agg = MonthlyBill::where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->selectRaw(
                    'COUNT(*) AS unpaid_count,'
                    .' COALESCE(SUM(total_amount), 0) AS unpaid_total,'
                    .' SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) AS overdue_count',
                    [$today]
                )
                ->first();

            $summary = [
                'unpaid_count' => (int) ($agg->unpaid_count ?? 0),
                'unpaid_total' => (float) ($agg->unpaid_total ?? 0),
                'overdue_count' => (int) ($agg->overdue_count ?? 0),
                'customer_count' => 1,
            ];

            // Periode terbaru dulu — yang paling mendesak untuk dilihat.
            $bills = MonthlyBill::where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->orderByDesc('billing_period_year')
                ->orderByDesc('billing_period_month')
                ->limit($limit)
                ->get();

            $data = $bills->map(fn ($b) => [
                'bill_id' => $b->id,
                'period_label' => $b->periodLabel()
                    ?: sprintf('%02d/%d', $b->billing_period_month, $b->billing_period_year),
                'total_amount' => (float) $b->total_amount,
                'due_date' => $b->due_date?->toDateString(),
                'is_overdue' => (string) $b->due_date < $today,
            ])->values()->all();

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => $summary,
            ]);
        }

        // Admin & teknisi: pelanggan mana saja yang menunggak. Satu query
        // agregat per pelanggan; total di `summary` diambil dari hasil
        // query yang sama, jadi tidak butuh COUNT kedua.
        $rows = MonthlyBill::where('status', 'unpaid')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->selectRaw(
                'customer_id,'
                .' COUNT(*) AS unpaid_count,'
                .' COALESCE(SUM(total_amount), 0) AS total_unpaid,'
                .' SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) AS overdue_count,'
                .' MIN(due_date) AS oldest_due_date',
                [$today]
            )
            ->orderByDesc('total_unpaid')
            ->get();

        $summary = [
            'unpaid_count' => (int) $rows->sum('unpaid_count'),
            'unpaid_total' => (float) $rows->sum('total_unpaid'),
            'overdue_count' => (int) $rows->sum('overdue_count'),
            'customer_count' => $rows->count(),
        ];

        // Nama & kode pelanggan diambil terpisah hanya untuk baris yang
        // benar-benar dikirim (maks 15), bukan untuk semua pelanggan.
        $customers = Customer::with([
            'user:id,name',
            'ticket:id,applicant_name',
        ])
            ->whereIn('id', $rows->take($limit)->pluck('customer_id'))
            ->get(['id', 'user_id', 'ticket_id', 'customer_code'])
            ->keyBy('id');

        $data = $rows->take($limit)->map(function ($r) use ($customers) {
            $c = $customers->get($r->customer_id);

            return [
                'customer_id' => (int) $r->customer_id,
                'name' => $c?->ticket?->applicant_name ?? $c?->user?->name ?? '-',
                'code' => $c?->customer_code ?? '-',
                'unpaid_count' => (int) $r->unpaid_count,
                'total_unpaid' => (float) $r->total_unpaid,
                'overdue_count' => (int) $r->overdue_count,
                'oldest_due_date' => (string) $r->oldest_due_date,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    // Daftar pelanggan suspended + tagihan unpaid-nya (untuk teknisi)
    public function suspended(Request $request)
    {
        $customers = Customer::with([
            'user',
            'ticket.package',
            'ticket.village',
            'monthlyBills' => fn ($q) => $q->orderBy('billing_period_year')->orderBy('billing_period_month'),
        ])
            ->whereHas('ticket', fn ($q) => $q->where('status', 'suspended'))
            ->get();

        $items = $customers->map(function ($c) {
            $unpaid = $c->monthlyBills->where('status', 'unpaid');

            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->user?->name ?? $c->ticket?->applicant_name,
                'address' => $c->ticket?->address,
                'dusun' => $c->ticket?->village?->hamlet_name,
                'desa' => $c->ticket?->village?->village_name,
                'package_name' => $c->ticket?->package?->name,
                'unpaid_count' => $unpaid->count(),
                'total_unpaid' => $unpaid->sum('total_amount'),
                'bills' => $unpaid->map(fn ($b) => [
                    'id' => $b->id,
                    'period' => $b->billing_period_month.'/'.$b->billing_period_year,
                    'amount' => $b->total_amount,
                    'due_date' => $b->due_date,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    // Teknisi: konfirmasi pelanggan suspended sudah bayar & minta restore ke aktif.
    // Backend cek: jika semua bill paid → ubah ticket status suspended → completed.
    public function restoreCustomer(Request $request, $customerId)
    {
        // Mengaktifkan kembali pelanggan = transisi status layanan +
        // revenue state, sama seperti proses pembayaran. Route-nya pernah
        // dibuka untuk teknisi (group admin+teknisi) sehingga teknisi bisa
        // mengubah status tiket pelanggan mana saja tanpa jejak audit.
        // Now teknisi hanya melihat daftarnya; aksi aktivasinya milik admin.
        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Aktivasi pelanggan hanya dapat dilakukan oleh admin.',
            ], 403);
        }

        $customer = Customer::with('ticket')->findOrFail($customerId);

        if (! $customer->ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket instalasi tidak ditemukan.',
            ], 404);
        }

        if ($customer->ticket->status !== 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan ini tidak dalam status suspended.',
            ], 400);
        }

        $unpaidCount = MonthlyBill::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->count();

        if ($unpaidCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Masih ada {$unpaidCount} tagihan belum lunas. Pembayaran diproses oleh admin terlebih dahulu.",
            ], 400);
        }

        $customer->ticket->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan berhasil diaktifkan kembali.',
            'data' => [
                'customer_id' => $customer->id,
                'new_status' => 'completed',
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

    /**
     * Ubah pemakaian (meter akhir) seorang pelanggan pada satu periode,
     * lalu hitung ulang tagihannya.
     *
     * Kenapa endpoint ini perlu ada
     * ---------------------------
     * Halaman "Data Pemakaian Air" menampilkan angka pemakaian DAN
     * tagihan. Dua-duanya sumbernya berbeda: pemakaian dari
     * `meter_readings`, tagihan dari `monthly_bills`. Kalau hanya meter
     * yang diubah, tagihan tetap memakai tarif lama — pemakaian berubah
     * tapi nominal tagihannya tidak. Endpoint ini menutup keduanya:
     * ubah meter, lalu tagihannya dihitung ulang dari tarif paket yang
     * berlaku.
     *
     * Aturan main
     * -----------
     * 1. Tagihan yang sudah LUNAS tidak boleh diubah. Bukti fisiknya
     *    sudah jadi bagian audit, dan uangnya sudah masuk kas.
     * 2. Meter akhir tidak boleh lebih kecil dari meter akhir periode
     *    sebelumnya (angka meter tidak bisa mundur).
     * 3. Meter awal periode ini berasal dari meter akhir bulan sebelumnya
     *    — jadi meter AWAL tidak bisa diubah langsung dari form ini.
     *    Yang bisa diubah hanya meter AKHIR.
     */
    public function updateUsage(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'meter_value' => 'required|integer|min:0|max:99999999',
        ], [
            'customer_id.required' => 'Pelanggan wajib diisi.',
            'month.required' => 'Periode bulan wajib diisi.',
            'year.required' => 'Periode tahun wajib diisi.',
            'meter_value.required' => 'Meter akhir wajib diisi.',
            'meter_value.integer' => 'Meter akhir harus berupa angka.',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $meterValue = (int) $request->meter_value;

        $customer = Customer::with(['ticket.package', 'ticket.user', 'user'])
            ->findOrFail($request->customer_id);

        if (! $customer->ticket || ! $customer->ticket->package) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan ini belum memiliki paket aktif sehingga tidak bisa ditagih.',
            ], 422);
        }

        $bill = MonthlyBill::where('customer_id', $customer->id)
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->first();

        // ── 0. Least-privilege: teknisi hanya boleh mengedit pelanggan
        //       yang pernah ia tangani. Admin bebas semua. ──
        if (! $this->isPrivileged($request)) {
            $isOwnCustomer = MeterReading::where('customer_id', $customer->id)
                ->where('recorded_by', $request->user()?->id)
                ->exists();

            if (! $isOwnCustomer) {
                abort(403, 'Akses ditolak. Pelanggan ini bukan dalam wilayah pencatatan Anda.');
            }
        }

        // ── 1. Tagihan yang sudah lunas tidak boleh diedit ──
        if ($bill && $this->billRecalculationService->isBillPaid($bill)) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan periode ini sudah lunas sehingga tidak bisa diubah.',
            ], 409);
        }

        // ── 2. Meter tidak boleh mundur ──
        $previousReading = MeterReading::where('customer_id', $customer->id)
            ->where(function ($query) use ($year, $month) {
                $query->where('reading_year', '<', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->where('reading_year', $year)
                            ->where('reading_month', '<', $month);
                    });
            })
            ->orderByDesc('reading_year')
            ->orderByDesc('reading_month')
            ->first();

        if ($previousReading && $meterValue < (int) $previousReading->meter_value) {
            return response()->json([
                'success' => false,
                'message' => 'Meter tidak boleh lebih kecil dari bulan sebelumnya ('.$previousReading->meter_value.' m³).',
            ], 400);
        }

        // ── 3. Terapkan perubahan meter + hitung ulang tagihan ──
        try {
            $result = DB::transaction(function () use ($customer, $year, $month, $meterValue) {
                $reading = MeterReading::where('customer_id', $customer->id)
                    ->where('reading_year', $year)
                    ->where('reading_month', $month)
                    ->first();

                if ($reading) {
                    $reading->update(['meter_value' => $meterValue]);
                } else {
                    // Meter belum pernah dicatat untuk periode ini, tapi
                    // tagihannya sudah ada (mis. generate tagihan duluan).
                    // Catat saja supaya angka pemakaian dan tagihan sinkron.
                    MeterReading::create([
                        'customer_id' => $customer->id,
                        'recorded_by' => Auth::id(),
                        'reading_year' => $year,
                        'reading_month' => $month,
                        'meter_value' => $meterValue,
                        'photo_url' => null,
                        'recorded_at' => now(),
                    ]);
                }

                $recalculation = $this->billRecalculationService->recalculateForCustomer($customer, $year, $month);

                // Jurnal piutang tunggakan harus menyusul nominal tagihan
                // yang baru, kalau tidak piutang dan tagihan tidak cocok.
                $journals = $this->billRecalculationService->syncOverdueJournals($recalculation['bill']);

                // ── Rantai ke periode berikutnya ──
                //
                // Meter akhir bulan ini adalah METER AWAL bulan berikutnya.
                // Jadi mengubah meter bulan ini otomatis mengubah tagihan
                // bulan depan juga. Kalau tidak ikut dihitung ulang, tagihan
                // bulan depan memakai meter awal yang sudah tidak berlaku.
                //
                // Periode berikutnya yang ikut tersentuh HANYA kalau
                // tagihannya belum lunas. Tagihan yang sudah dibayar tetap
                // dikunci — mengedit periode sebelumnya tidak boleh
                // membongkar tagihan yang sudah dibayar.
                [$nextYear, $nextMonth] = $this->billRecalculationService->nextPeriod($year, $month);

                $nextBill = MonthlyBill::where('customer_id', $customer->id)
                    ->where('billing_period_year', $nextYear)
                    ->where('billing_period_month', $nextMonth)
                    ->first();

                $nextRecalculation = null;

                if ($nextBill && ! $this->billRecalculationService->isBillPaid($nextBill)) {
                    $nextRecalculation = $this->billRecalculationService->recalculateForCustomer($customer, $nextYear, $nextMonth);
                    $this->billRecalculationService->syncOverdueJournals($nextRecalculation['bill']);
                }

                return array_merge($recalculation, [
                    'journals' => $journals,
                    'next' => $nextRecalculation,
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $bill = $result['bill'];
        return response()->json([
            'success' => true,
            'message' => $result['created']
                ? 'Pemakaian diperbarui dan tagihan dibuat.'
                : 'Pemakaian dan tagihan berhasil diperbarui.',
            'data' => [
                'bill' => $bill,
                'before' => $result['before'],
                'after' => $result['after'],
                'journal' => $result['journals'],
                // Tagihan periode berikutnya ikut berubah kalau ada dan
                // belum lunas (meter bulan ini = meter awal bulan depan).
                'next' => $result['next']['after'] ?? null,
            ],
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
            'customer.user',
            'customer.ticket.package',
            'customer.ticket.village',
            'billPayments',
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

        // Tagihan Rp 0 tidak bisa di-rollback.
        //
        // Tagihan nihil tidak punya pembayaran: tidak ada baris `bill_payments`
        // dan tidak ada jurnal. Yang ada hanya flag `status = 'paid'` yang
        // dipasang generator. Mengembalikan flag itu ke `unpaid` tidak membatalkan
        // apa pun — dan begitu generator berjalan lagi (atau generate bulanan
        // dijalankan), tagihan yang sama otomatis kembali jadi `paid` karena
        // nilainya memang 0. Akibatnya user melihat aksi "rollback berhasil"
        // tapi statusnya balik sendiri beberapa saat kemudian.
        //
        // Jadi tolak lebih awal dengan pesan yang jelas, daripada memberi hasil
        // yang menipu.
        if ((float) ($bill->total_amount ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan Rp 0 tidak punya pembayaran sehingga tidak bisa di-rollback. Tagihan ini otomatis berstatus lunas.',
            ], 400);
        }

        DB::transaction(function () use ($bill) {
            // Hapus transaksi jurnal terkait pembayaran bill ini
            $paymentIds = $bill->billPayments()->pluck('id');
            if ($paymentIds->isNotEmpty()) {
                Transaction::where('reverence_type', 'bill_payment')
                    ->whereIn('reverence_id', $paymentIds)
                    ->delete();
            }

            // Hapus pembayaran
            BillPayment::where('bill_id', $bill->id)->delete();

            // Kembalikan status ke unpaid
            $bill->update(['status' => 'unpaid']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tagihan dikembalikan ke status belum dibayar.',
            'data' => $bill,
        ]);
    }

    /**
     * "Juli 2026" dari index bulan (1-12) + tahun.
     *
     * Carbon::format('F') menghasilkan nama bulan Bahasa Inggris, sedangkan
     * sisanya di app ini Bahasa Indonesia, jadi pakai daftar sendiri.
     */
    private function namaBulanIndonesia(int $month, int $year): string
    {
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return ($months[$month] ?? (string) $month).' '.$year;
    }
}
