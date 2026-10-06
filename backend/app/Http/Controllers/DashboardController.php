<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * TTL cache untuk statistik dashboard.
     * - Stat umum: 60 detik (data berubah saat bayar / tiket baru)
     * - Finance chart & available_years: 5 menit (lebih stabil)
     */
    private const STATS_CACHE_TTL  = 60;   // detik
    private const FINANCE_CACHE_TTL = 300;  // detik (5 menit)
    private const YEARS_CACHE_TTL   = 3600; // 1 jam

    public function statistics(Request $request)
    {
        $now   = now();
        $year  = (int) $request->query('year', $now->year);
        $month = (int) $request->query('month', $now->month);
        $todayYmd = $now->toDateString();

        // ─── CACHE 1: stat-global (independent of year/month) ───
        $statsGlobal = Cache::remember(
            'dashboard:stats:global:' . $todayYmd,
            self::STATS_CACHE_TTL,
            function () {
                return [
                    'total_customers' => Customer::count(),
                    'tickets_by_status' => InstallationTicket::selectRaw('status, count(*) as total')
                        ->groupBy('status')
                        ->pluck('total', 'status')
                        ->all(),
                    'bills_unpaid' => MonthlyBill::where('status', 'unpaid')->count(),
                    'pemakaian_count' => InstallationTicket::where('status', 'completed')->count(),
                    'tunggakan_total' => MonthlyBill::where('status', 'unpaid')
                        ->where('penalty_amount', '>', 0)
                        ->count(),
                    'latest_tickets' => InstallationTicket::with('package:id,name')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get(['id', 'applicant_name', 'status', 'package_id', 'created_at']),
                    'overdue_bills' => MonthlyBill::with([
                        'customer:id,user_id,customer_code',
                        'customer.user:id,name',
                    ])
                        ->where('status', 'unpaid')
                        ->where('due_date', '<=', now()->toDateString())
                        ->orderBy('due_date')
                        ->limit(5)
                        ->get([
                            'id', 'customer_id', 'billing_period_year', 'billing_period_month',
                            'total_amount', 'penalty_amount', 'due_date', 'status',
                        ]),
                ];
            }
        );

        // ─── CACHE 2: finance data (per year+month) ───
        $financeData = Cache::remember(
            "dashboard:finance:{$year}:{$month}",
            self::FINANCE_CACHE_TTL,
            function () use ($year, $month) {
                // Pakai range tanggal supaya index tgl_transaksi optimal
                $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

                // Aggregate pendapatan + beban dalam 1 query (bukan 2)
                $monthAgg = Transaction::selectRaw(
                    "COALESCE(SUM(CASE WHEN account_kredit LIKE '4.%' THEN saldo ELSE 0 END), 0) AS pendapatan,"
                    . " COALESCE(SUM(CASE WHEN account_debet  LIKE '5.%' THEN saldo ELSE 0 END), 0) AS beban"
                )
                    ->whereBetween('tgl_transaksi', [$start, $end])
                    ->first();

                $pendapatan = (float) ($monthAgg->pendapatan ?? 0);
                $beban      = (float) ($monthAgg->beban ?? 0);

                // Chart per bulan dalam 1 tahun
                $yearStart = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
                $yearEnd   = Carbon::create($year, 12, 31)->endOfYear()->toDateString();

                $rowsPendapatan = Transaction::selectRaw(
                    "MONTH(tgl_transaksi) AS m,"
                    . " COALESCE(SUM(saldo), 0) AS total"
                )
                    ->whereBetween('tgl_transaksi', [$yearStart, $yearEnd])
                    ->where('account_kredit', 'like', '4.%')
                    ->groupBy(DB::raw('MONTH(tgl_transaksi)'))
                    ->pluck('total', 'm');

                $rowsBeban = Transaction::selectRaw(
                    "MONTH(tgl_transaksi) AS m,"
                    . " COALESCE(SUM(saldo), 0) AS total"
                )
                    ->whereBetween('tgl_transaksi', [$yearStart, $yearEnd])
                    ->where('account_debet', 'like', '5.%')
                    ->groupBy(DB::raw('MONTH(tgl_transaksi)'))
                    ->pluck('total', 'm');

                $chart = [];
                for ($m = 1; $m <= 12; $m++) {
                    $p = (float) ($rowsPendapatan[$m] ?? 0);
                    $b = (float) ($rowsBeban[$m] ?? 0);
                    $chart[] = [
                        'year'       => $year,
                        'month'      => $m,
                        'pendapatan' => $p,
                        'beban'      => $b,
                        'surplus'    => $p - $b,
                    ];
                }

                return [
                    'finance' => [
                        'pendapatan' => $pendapatan,
                        'beban'      => $beban,
                        'surplus'    => $pendapatan - $beban,
                        'year'       => $year,
                        'month'      => $month,
                    ],
                    'finance_chart' => $chart,
                ];
            }
        );

        // ─── CACHE 3: available_years (jarang berubah) ───
        $availableYears = Cache::remember(
            'dashboard:available_years',
            self::YEARS_CACHE_TTL,
            function () {
                return Transaction::selectRaw('DISTINCT YEAR(tgl_transaksi) as y')
                    ->whereNotNull('tgl_transaksi')
                    ->orderBy('y')
                    ->pluck('y')
                    ->map(fn ($y) => (int) $y)
                    ->values()
                    ->all();
            }
        );

        // Revenue bulan ini (ringan, index ada)
        $revenueThisMonth = MonthlyBill::where('status', 'paid')
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->sum('total_amount');

        // Kompatibilitas dengan frontend lama: bills_this_month.unpaid
        $billsThisMonth = ['unpaid' => $statsGlobal['bills_unpaid']];

        return response()->json([
            'success' => true,
            'data'    => array_merge($statsGlobal, [
                'revenue_this_month' => $revenueThisMonth,
                'bills_this_month'   => $billsThisMonth,
                'finance'            => $financeData['finance'],
                'finance_chart'      => $financeData['finance_chart'],
                'available_years'    => $availableYears,
            ]),
        ]);
    }

    /**
     * Endpoint RINGAN khusus untuk popup 4 kotak dashboard.
     * Filter & paginasi dilakukan di SERVER sehingga frontend tidak perlu
     * menarik semua data dan filter client-side (yang menyebabkan loop
     * getAllBills() sebelumnya menarik ratusan halaman).
     *
     * Query params:
     *   - type     : instalasi | tunggakan | tagihan | pemakaian (wajib)
     *   - search   : string pencarian (opsional)
     *   - page     : halaman (default 1)
     *   - per_page : rows per page (default 10, max 50)
     *
     * Response shape seragam: { success, data: [...], meta: { ... } }
     */
    public function popupData(Request $request)
    {
        $type    = (string) $request->query('type', '');
        $perPage = max(1, min((int) $request->query('per_page', 10), 50));
        $page    = max(1, (int) $request->query('page', 1));
        $search  = trim((string) $request->query('search', ''));

        switch ($type) {
            case 'instalasi':
                return $this->popupInstalasi($perPage, $page, $search);
            case 'tunggakan':
                return $this->popupTunggakan($perPage, $page, $search);
            case 'tagihan':
                return $this->popupTagihan($perPage, $page, $search);
            case 'pemakaian':
                return $this->popupPemakaian($request, $perPage, $page, $search);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe popup tidak dikenali. Gunakan: instalasi | tunggakan | tagihan | pemakaian.',
                ], 422);
        }
    }

    /**
     * Arsip Instalasi: tiket dengan status draft / pending / surveyed / unpaid.
     * Hanya SELECT field minimum + filter server-side.
     */
    private function popupInstalasi(int $perPage, int $page, string $search)
    {
        $query = InstallationTicket::query()
            ->whereIn('status', ['draft', 'pending', 'surveyed', 'unpaid'])
            ->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('applicant_name', 'like', $search . '%')
                    ->orWhere('nik', 'like', $search . '%');
            });
        }

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)
            ->get(['id', 'applicant_name', 'address', 'status', 'created_at']);

        $items = $rows->map(fn ($t) => [
            'id'           => $t->id,
            'nomorInduk'   => 'INS-' . str_pad((string) $t->id, 5, '0', STR_PAD_LEFT),
            'customer'     => $t->applicant_name ?: '-',
            'alamat'       => $t->address ?: '-',
            'tanggalOrder' => $t->created_at ? $t->created_at->toDateString() : '-',
            'status'       => $t->status,
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $page,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    /**
     * Arsip Tunggakan: tagihan unpaid + penalty_amount > 0.
     * Eager load minimum + paginasi server-side (TIDAK loop halaman).
     */
    private function popupTunggakan(int $perPage, int $page, string $search)
    {
        $query = MonthlyBill::query()
            ->with([
                'customer:id,user_id,ticket_id,customer_code',
                'customer.user:id,name',
                'customer.ticket:id,applicant_name,address',
            ])
            ->where('status', 'unpaid')
            ->where('penalty_amount', '>', 0)
            ->orderBy('due_date', 'asc');

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->whereHas('customer.user', fn ($u) => $u->where('name', 'like', $search . '%'))
                    ->orWhereHas('customer', fn ($c) => $c->where('customer_code', 'like', $search . '%'))
                    ->orWhereHas('customer.ticket', fn ($t) => $t->where('applicant_name', 'like', $search . '%'));
            });
        }

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        $items = $rows->map(function ($b) {
            $totalAmt = (float) $b->total_amount;
            $denda = (float) $b->penalty_amount;
            return [
                'id'           => $b->id,
                'nomorInduk'   => $b->customer?->customer_code ?: '-',
                'customer'     => $b->customer?->ticket?->applicant_name
                    ?? $b->customer?->user?->name ?? '-',
                'alamat'       => $b->customer?->ticket?->address ?: '-',
                'periodeLabel' => $b->billing_period_month
                    ? sprintf('%02d/%d', $b->billing_period_month, $b->billing_period_year)
                    : '-',
                'tagihan'      => max(0, $totalAmt - $denda),
                'denda'        => $denda,
                'total'        => $totalAmt,
                'status'       => 'Belum Lunas',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $page,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    /**
     * Arsip Tagihan: SEMUA tagihan unpaid (tanpa filter denda).
     */
    private function popupTagihan(int $perPage, int $page, string $search)
    {
        $query = MonthlyBill::query()
            ->with([
                'customer:id,user_id,ticket_id,customer_code',
                'customer.user:id,name',
                'customer.ticket:id,applicant_name,address',
            ])
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc');

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->whereHas('customer.user', fn ($u) => $u->where('name', 'like', $search . '%'))
                    ->orWhereHas('customer', fn ($c) => $c->where('customer_code', 'like', $search . '%'))
                    ->orWhereHas('customer.ticket', fn ($t) => $t->where('applicant_name', 'like', $search . '%'));
            });
        }

        $total = (clone $query)->count();
        $rows = $query->forPage($page, $perPage)->get();

        $items = $rows->map(fn ($b) => [
            'id'           => $b->id,
            'nomorInduk'   => $b->customer?->customer_code ?: '-',
            'customer'     => $b->customer?->ticket?->applicant_name
                ?? $b->customer?->user?->name ?? '-',
            'alamat'       => $b->customer?->ticket?->address ?: '-',
            'periode'      => $b->billing_period_month,
            'tahun'        => $b->billing_period_year,
            'periodeLabel' => $b->billing_period_month
                ? sprintf('%02d/%d', $b->billing_period_month, $b->billing_period_year)
                : '-',
            'total'        => (float) $b->total_amount,
            'denda'        => (float) $b->penalty_amount,
            'jatuhTempo'   => $b->due_date?->toDateString(),
            'status'       => $b->status,
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $page,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    /**
     * Arsip Pemakaian: pemakaian air bulan ini.
     * Anti-N+1: ambil semua customer + tagihan sekaligus.
     */
    private function popupPemakaian(Request $request, int $perPage, int $page, string $search)
    {
        $now   = now();
        $month = (int) $request->query('month', $now->month);
        $year  = (int) $request->query('year',  $now->year);

        $customers = Customer::with(['user:id,name', 'ticket:id,applicant_name,address'])
            ->whereHas('ticket', fn ($q) => $q->whereIn('status', [
                'surveyed', 'unpaid', 'processing', 'completed', 'suspended',
            ]))
            ->get(['id', 'user_id', 'ticket_id', 'customer_code']);

        $bills = MonthlyBill::whereIn('customer_id', $customers->pluck('id'))
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->get()
            ->keyBy('customer_id');

        $items = $customers->map(function ($c) use ($bills, $month, $year) {
            $bill = $bills->get($c->id);
            return [
                'id'               => $c->id,
                'nomorInduk'       => $c->customer_code ?: '-',
                'customer'         => $c->user?->name ?? $c->ticket?->applicant_name ?? '-',
                'alamat'           => $c->ticket?->address ?: '-',
                'periodeLabel'     => $bill
                    ? sprintf('%02d/%d', $bill->billing_period_month, $bill->billing_period_year)
                    : sprintf('%02d/%d', $month, $year),
                'meter_awal'       => $bill?->meter_reading_start,
                'meter_akhir'      => $bill?->meter_reading_end,
                'pemakaian'        => $bill?->usage_m3,
                'pemakaian_charge' => $bill?->usage_charge,
                'abodemen'         => $bill?->abodemen,
                'denda'            => $bill?->penalty_amount ?? 0,
                'total'            => $bill?->total_amount ?? 0,
                'status'           => $bill?->status ?? 'pending',
            ];
        })->values();

        if ($search !== '') {
            $q = mb_strtolower($search);
            $items = $items->filter(fn ($i) => str_contains(mb_strtolower($i['customer']), $q)
                || str_contains(mb_strtolower($i['nomorInduk']), $q))->values();
        }

        $total = $items->count();
        $rows = $items->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'meta'    => [
                'current_page' => $page,
                'last_page'    => (int) max(1, ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    /**
     * Auto-generate piutang/abodemen/denda untuk tagihan menunggak.
     *
     * Dipanggil dari frontend SETELAH login berhasil, lalu hasilnya
     * ditampilkan lewat popup loading → popup hasil di
     * `useOverdueGenNotification.js`.
     *
     * - Hanya jalan bila hari ini == `toleransi_tunggakan` (TANGGAL di
     *   kolom SOP, range 1-28).
     * - SELALU menjalankan ulang command pada tanggal tersebut — tidak ada
     *   lagi gate "sudah pernah jalan hari ini". Command-nya sendiri yang
     *   menentukan mana yang perlu dikerjakan: setiap tagihan yang sudah
     *   punya jurnal `overdue_bill` dilewati (`skipped`), sisanya dibuat.
     *   Jadi menjalankan berkali-kali dalam sehari aman dan tidak
     *   menghasilkan data ganda.
     * - `--force` TIDAK dipakai: memaksa akan menghapus jurnal lama lalu
     *   membuat ulang, itu justru sumber data ganda kalau proses gagal di
     *   tengah jalan.
     * - Concurrency dijaga `Cache::lock`, bukan cache "sudah jalan": dua
     *   admin yang login bersamaan tidak boleh menjalankan command
     *   bersamaan, karena keduanya akan melihat tagihan yang sama belum
     *   punya jurnal lalu sama-sama membuat.
     *
     * Catatan: notifikasi banner lama (`overdue_gen_notification` +
     * endpoint `dashboard/notification`) sudah DIHAPUS. Deliverinya kini
     * murni lewat popup: user melihat proses generate, lalu hasilnya.
     */
    public function checkAutoGenerateOverdue(Request $request)
    {
        $today      = now();
        $todayYmd   = $today->toDateString();
        $todayDay   = (int) $today->format('d');
        $scheduledDay = (int) (Setting::first()?->toleransi_tunggakan ?? 0);

        $configured = $scheduledDay >= 1 && $scheduledDay <= 28;
        $isScheduledDay = $todayDay === $scheduledDay;

        // `will_run` = hari ini adalah tanggal generate. Sengaja TIDAK
        // bergantung pada "sudah pernah jalan" — frontend memakai field ini
        // hanya untuk memutuskan apakah membuka popup loading, dan itu harus
        // tetap true di setiap login pada tanggal tersebut supaya proses
        // dijalankan ulang (dedup ada di level command, bukan di sini).
        return response()->json([
            'success'        => true,
            'configured'     => $configured,
            'is_scheduled'   => $isScheduledDay,
            // Dipertahankan di response supaya frontend lama yang masih
            // membaca field ini tidak ikut rusak, tapi nilainya sudah tidak
            // lagi dipakai sebagai penentu.
            'already_ran'    => false,
            'will_run'       => $configured && $isScheduledDay,
            'scheduled_day'  => $scheduledDay,
            'today_day'      => $todayDay,
            'date'           => $todayYmd,
        ]);
    }

    public function autoGenerateOverdue(Request $request)
    {
        // Generate piutang menulis jurnal keuangan untuk SELURUH tagihan
        // menunggak, jadi ini operasi yang mutlak milik admin. Endpoint-nya
        // boleh dipanggil teknisi (route-nya `role:admin,teknisi`) karena
        // teknisi memakai statistik yang sama, tapi eksekusinya tidak.
        // Tanpa guard ini teknisi bisa menjalankannya, dan karena cache-nya
        // global per tanggal, teknisi yang login pertama kali pada hari itu
        // akan "memiliki" eksekusi tersebut.
        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'success' => true,
                'ran'     => false,
                'reason'  => 'Generate piutang otomatis hanya dapat dijalankan oleh admin.',
            ], 403);
        }

        $userId  = auth()->id();
        $today   = now();
        $todayYmd = $today->toDateString();
        $todayYm = $today->format('Y-m');

        $setting  = Setting::first();
        $scheduledDay = (int) ($setting?->toleransi_tunggakan ?? 0);

        // 1) Kalau SOP belum di-set / 0 → tidak ada generate otomatis.
        if ($scheduledDay < 1 || $scheduledDay > 28) {
            return response()->json([
                'success' => true,
                'ran'     => false,
                'reason'  => 'Toleransi menunggak belum dikonfigurasi.',
                'scheduled_day' => $scheduledDay,
                'today_day'     => (int) $today->format('d'),
                'date'          => $todayYmd,
            ]);
        }

        // 2) Hanya jalan di tanggal yang sesuai SOP.
        $todayDay = (int) $today->format('d');
        if ($todayDay !== $scheduledDay) {
            return response()->json([
                'success' => true,
                'ran'     => false,
                'reason'  => 'Hari ini bukan tanggal generate yang dijadwalkan.',
                'scheduled_day' => $scheduledDay,
                'today_day'     => $todayDay,
                'date'          => $todayYmd,
            ]);
        }

        // 3) Idempotent GLOBAL per tanggal, bukan per user.
        //
        // 3) Lock eksklusif per tanggal.
        //
        //    Dulu ada cache "sudah pernah jalan hari ini" yang membuat
        //    endpoint hanya bisa jalan SEKALI per tanggal. Permintaan baru:
        //    setiap login di tanggal generate harus menghitung ulang, karena
        //    tagihan baru bisa saja masuk setelah login pertama.
        //
        //    Yang diganti adalah PENGAKAL, bukan idempotensi. Command-nya
        //    sendiri yang melakukan dedup: tagihan yang sudah punya jurnal dilewati
        //    (`skipped`), jadi dijalankan berapa kali pun tidak menghasilkan
        //    jurnal ganda.
        //
        //    Yang tetap dijaga adalah LOCK. Tanpa lock, dua admin yang login
        //    pada detik yang sama bisa sama-sama melihat tagihan yang belum
        //    punya jurnal lalu sama-sama membuat — itu baru benar-benar
        //    menghasilkan data ganda. `Cache::lock` bersifat atomik, jadi
        //    hanya satu yang menang dan yang lain membaca ringkasan hasil.
        $lock = Cache::lock('auto_gen_overdue_lock_' . $todayYmd, 300);

        if (! $lock->get()) {
            // Proses sedang berjalan di request lain (double-click, atau dua
            // tab terbuka bersamaan). Balas ringkasan terakhir yang tersedia
            // supaya popup admin kedua tetap informatif, bukan error kosong.
            return response()->json([
                'success'     => true,
                'ran'         => false,
                'busy'        => true,
                'reason'      => 'Generate piutang sedang berjalan di tab lain. Mohon tunggu sebentar.',
                'scheduled_day' => $scheduledDay,
                'today_day'  => $todayDay,
                'date'       => $todayYmd,
                'summary'    => $this->buildSummaryFromRun(Cache::get('overdue_gen_run_summary')),
            ]);
        }

        // 4) Eksekusi command (sama seperti endpoint /tunggakan/generate).
        $start   = microtime(true);
        $opts    = ['--tanggal' => $todayYmd];

        try {
            $exit   = Artisan::call('billing:generate-overdue-transactions', $opts);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            Log::error('autoGenerateOverdue gagal: ' . $e->getMessage());
            $lock->release();
            return response()->json([
                'success' => false,
                'ran'     => false,
                'message' => 'Gagal menjalankan generate: ' . $e->getMessage(),
                'scheduled_day' => $scheduledDay,
                'today_day'     => $todayDay,
                'date'          => $todayYmd,
            ], 500);
        }

        $duration = (int) ((microtime(true) - $start) * 1000);

        // Ambil ringkasan yang ditulis command (angka akurat dari loop
        // yang benar-benar dijalankan). Fallback ke hitung ulang dari DB
        // bila command tidak sempat menulis (mis. gagal sebelum summary).
        $runSummary = Cache::get('overdue_gen_run_summary');

        $lock->release();

        $payload = [
            'success'       => true,
            'ran'           => true,
            'date'          => $todayYmd,
            'scheduled_day' => $scheduledDay,
            'today_day'     => $todayDay,
            'duration_ms'   => $duration,
            'exit_code'     => $exit,
            'output'        => trim($output),
            'summary'       => $this->buildSummaryFromRun($runSummary),
        ];

        return response()->json($payload);
    }

    /**
     * Bentuk payload `summary` untuk pop up frontend.
     *
     * Sumber angka: cache `overdue_gen_run_summary` yang ditulis command
     * `billing:generate-overdue-transactions` (lihat method
     * `storeRunSummary`). Angka di sana berasal langsung dari loop
     * pemrosesan, jadi tidak bisa nol palsu.
     *
     * Fallback dipakai hanya bila cache hilang (mis. cache store dimatikan).
     */
    private function buildSummaryFromRun(?array $run): array
    {
        // Abaikan ringkasan dari hari lain — kalau tanggalnya beda, angka
        // `processed`/`skipped` tidak lagi menggambarkan proses hari ini.
        if ($run && ($run['date'] ?? null) !== now()->toDateString()) {
            $run = null;
        }

        if ($run) {
            return [
                'tagihan_dengan_abodemen_tungakan'  => (int) ($run['jurnal_abodemen'] ?? 0),
                'tagihan_dengan_pemakaian_tungakan' => (int) ($run['jurnal_pemakaian'] ?? 0),
                'tagihan_diproses'                   => (int) ($run['processed'] ?? 0),
                'tagihan_dilewati'                   => (int) ($run['skipped'] ?? 0),
                'total_unpaid'                       => (int) ($run['total_unpaid'] ?? 0),
                'total_overdue'                      => (int) ($run['total_overdue'] ?? 0),
            ];
        }

        // Fallback: hitung dari DB tanpa filter tanggal, karena jurnal
        // tunggakan bisa dibuat di tanggal run sebelumnya.
        $processed = (int) Transaction::where('reverence_type', 'overdue_bill')
            ->where('account_debet', '1.1.03.01')
            ->where('account_kredit', '4.1.01.02')
            ->distinct()
            ->count('reverence_id');

        $processedUsage = (int) Transaction::where('reverence_type', 'overdue_bill')
            ->where('account_debet', '1.1.03.01')
            ->where('account_kredit', '4.1.01.03')
            ->distinct()
            ->count('reverence_id');

        return [
            'tagihan_dengan_abodemen_tungakan'  => $processed,
            'tagihan_dengan_pemakaian_tungakan' => $processedUsage,
            'tagihan_diproses'                   => $processed + $processedUsage,
            'tagihan_dilewati'                   => 0,
            'total_unpaid'                       => (int) MonthlyBill::where('status', 'unpaid')->count(),
            'total_overdue'                      => (int) MonthlyBill::where('status', 'unpaid')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];
    }
}
