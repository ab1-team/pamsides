<?php

namespace App\Console\Commands;

use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SuspendTransactionsTrigger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateOverdueTransactions extends Command
{
    protected $signature = 'billing:generate-overdue-transactions
                            {--tanggal= : Tanggal acuan (Y-m-d); default: hari ini}
                            {--force : Hapus jurnal tunggakan lama untuk bill yang sama sebelum insert}
                            {--chunk=500 : Jumlah baris jurnal per statement bulk insert}';

    protected $description = 'Generate jurnal piutang untuk tagihan menunggak (otomatis jam 00:01)';

    public function handle()
    {
        $today = $this->option('tanggal') ?: now()->toDateString();
        $today = date('Y-m-d', strtotime($today));
        $todayYmd = $today;
        $force = (bool) $this->option('force');

        // Ambang tunggakan: berapa HARI (dihitung dari hari generate) sebuah
        // tagihan dianggap menunggak.
        //
        // Nilai ini dipakai sebagai JUMLAH HARI di sini dengan sengaja.
        // `toleransi_tunggakan` punya dua consumers yang maknanya berbeda,
        // dan ini yang menggeser ambangnya:
        //   - `MonthlyBillController::pay` & `BillingForm.vue` memakainya
        //     sebagai TANGGAL jatuh tempo dalam bulan pemakaian tagihan
        //     (untuk deteksi ADVANCE payment).
        //   - Command ini memakainya sebagai MAAF toleransi: tagihan baru
        //     masuk piutang tunggakan setelah lewat N HARI.
        //
        // Jangan diubah jadi "hari toleransi di bulan pemakaian" — itu
        // akan membuat ambang jatuh ke bulan SEBELUM jatuh tempo tagihan,
        // sehingga praktis tidak ada tagihan yang lolos. Data aktual
        // perusahaan: tagihan periode Agustus/2026 jatuh tempo 26-27
        // September. Dengan ambang hari-toleransi (tanggal 6), ambangnya
        // jadi 6 AGUSTUS — sehingga 26 September dianggap belum menunggak
        // dan 3286 tagihan gagal diproses. Versi ini (6 hari sebelum hari
        // generate) memberi ambang 30 September, yang sesuai.
        $toleransi = (int) (Setting::first()?->toleransi_tunggakan ?? 0);
        $thresholdDate = date('Y-m-d', strtotime("$today -$toleransi days"));

        $overdueBills = MonthlyBill::where('status', 'unpaid')
            ->where('due_date', '<', $thresholdDate)
            ->with(['customer.user'])
            ->get();

        if ($overdueBills->isEmpty()) {
            $this->info('Tidak ada tagihan menunggak.');
            // Tetap tulis ringkasan supaya frontend tidak salah menampilkan
            // "diproses 0" padahal prosesnya memang tidak menemukan apa pun.
            $this->storeRunSummary($today, $thresholdDate, 0, 0, 0, 0);
            return self::SUCCESS;
        }

        $systemUser = User::where('role', 'admin')->first();
        if (!$systemUser) {
            $this->error('User admin tidak ditemukan.');
            $this->storeRunSummary($today, $thresholdDate, 0, $overdueBills->count(), 0, 0);
            return self::FAILURE;
        }

        $processed = 0;
        $skipped = 0;
        // Penghitung jurnal yang benar-benar DIBUAT pada run ini. Dipakai
        // untuk ringkasan popup — jangan dikira dari query jurnal bertanggal
        // hari ini, karena pada run ulang tidak ada jurnal baru sama sekali.
        $jurnalAbodemen = 0;
        $jurnalPemakaian = 0;

        $chunkSize = max(1, (int) $this->option('chunk'));

        // Akun yang ikut berubah karena generate ini. Tabel `amount` dihitung
        // ulang satu kali di akhir, menggantikan perhitungan berulang yang
        // tadinya dilakukan trigger MySQL untuk setiap baris.
        $affectedAccounts = [];

        // Trigger `amount` dimatikan sementara selama insert dan SELALU
        // dikembalikan lagi sebelum selesai — termasuk saat gagal.
        $suspend = new SuspendTransactionsTrigger();
        $suspend->disable();

        DB::beginTransaction();

        try {
            // Id ditulis eksplisit agar kolom `urutan` terisi dalam satu INSERT.
            //
            // Sebelumnya tiap jurnal melakukan `$trx->update(['urutan' => $trx->id])`
            // sendiri. Update itu memicu trigger `update_amount_debit` yang
            // menjalankan 8x SUM() penuh PER BARIS, padahal `urutan` sama
            // sekali tidak memengaruhi saldo.
            $nextId = ((int) Transaction::max('id')) + 1;
            $now = now();

            // ── Tagihan yang benar-benar belum punya jurnal ──
            //
            // Dulu dicek per-tagihan dengan `->exists()` di dalam loop: itu N+1
            // query untuk puluhan ribu tagihan. Sekarang satu query untuk
            // semuanya, lalu dicek dari memori.
            //
            // Kuncinya PASANGAN (tagihan, akun kredit), bukan tagihan saja.
            // Kalau hanya per tagihan, tagihan yang jurnal abodemennya sudah
            // ada tapi jurnal pemakaiannya belum akan SELALU dilewati —
            // padahal jurnal pemakaiannya memang belum pernah dibuat.
            // Command ini dijalankan ulang di setiap login, jadi seperti ini
            // prosesnya tidak pernah bisa tuntas.
            $alreadyHave = $force
                ? collect()
                : Transaction::where('reverence_type', 'overdue_bill')
                    ->select('reverence_id', 'account_kredit')
                    ->distinct()
                    ->get()
                    ->map(fn ($t) => $t->reverence_id . '|' . $t->account_kredit)
                    ->flip();

            $hasJournal = fn ($bill, string $kredit) =>
                $force ? false : $alreadyHave->has($bill->id . '|' . $kredit);

            // Karena deduplikasi kini per pasangan akun, "sudah punya
            // jurnal" tidak bisa lagi dihitung dari jumlah tagihan. Hitung
            // per tipe: tagihan yang DILEWATI kalau sudah punya jurnal untuk
            // tipe itu.
            //
            // WAJIB `keyBy('id')`: `Collection::has()` memakai KEY, bukan
            // nilai. Hasil `filter()` punya key integer 0,1,2,... sehingga
            // `has($bill->id)` hampir selalu salah dan TIDAK ADA jurnal yang
            // pernah dibuat — command tetap melaporkan "berhasil memproses"
            // padahal tabel kosong.
            $needsAbodemen = $overdueBills
                ->filter(fn ($bill) => (float) ($bill->abodemen ?? 0) > 0
                    && ! $hasJournal($bill, '4.1.01.02'))
                ->keyBy('id');

            $needsPemakaian = $overdueBills
                ->filter(fn ($bill) => (float) ($bill->usage_charge ?? 0) > 0
                    && ! $hasJournal($bill, '4.1.01.03'))
                ->keyBy('id');

            // Tagihan yang benar-benar akan menghasilkan SATU BARIS JURNAL
            // atau lebih.
            //
            // PENTING: syaratnya harus PERSIS sama dengan kondisi di loop
            // bawah. Kalau $targets lebih longgar dari syarat di loop itu,
            // command akan melaporkan "berhasil memproses N tagihan" padahal
            // tidak satu baris pun ter-insert — popup menampilkan angka yang
            // tidak pernah terjadi.
            //
            // Catatan soft-delete: jurnal yang pernah soft-deleted TETAP
            // terhitung sudah ada di sini, karena model Transaction memakai
            // global scope SoftDeletes. Jadi tagihan itu tidak dijurnal ulang.
            $targets = $overdueBills->filter(
                fn ($bill) => ((float) ($bill->abodemen ?? 0) > 0
                        && ! $hasJournal($bill, '4.1.01.02'))
                    || ((float) ($bill->usage_charge ?? 0) > 0
                        && ! $hasJournal($bill, '4.1.01.03'))
            );

            $skipped = $overdueBills->count() - $targets->count();

            // Mode --force: hapus jurnal lama lebih dulu supaya tidak dobel.
            if ($force) {
                foreach (array_chunk($overdueBills->pluck('id')->all(), 1000) as $idChunk) {
                    Transaction::where('reverence_type', 'overdue_bill')
                        ->whereIn('reverence_id', $idChunk)
                        ->delete();
                }
            }

            $rows = [];

            foreach ($targets as $bill) {
                $relasi       = $bill->customer?->customer_code ?? ('Bill #' . $bill->id);
                $customerName = $bill->customer?->user?->name
                    ?? $bill->customer?->ticket?->applicant_name
                    ?? 'Tanpa Nama';
                $abodemen     = (float) ($bill->abodemen ?? 0);
                $usageCharge  = (float) ($bill->usage_charge ?? 0);

                $keteranganSuffix = 'bulan ' . $this->periodLabelLong($bill) .
                    ' an. ' . $customerName . ' (' . $relasi . ')';

                // Cek PER TIPE, bukan hanya per tagihan: jurnal abodemen yang sudah
                // ada tidak boleh mencegah jurnal pemakaiannya dibuat, dan
                // sebaliknya. Command ini dijalankan ulang di setiap login.
                $adaJurnalBaru = false;

                if ($abodemen > 0 && $needsAbodemen->has($bill->id)) {
                    $rows[] = $this->journalRow(
                        $nextId++,
                        $today,
                        $now,
                        $systemUser->id,
                        $bill,
                        '4.1.01.02',
                        'Piutang Abodemen ' . $keteranganSuffix,
                        $relasi,
                        $abodemen
                    );
                    $jurnalAbodemen++;
                    $adaJurnalBaru = true;
                    $affectedAccounts['1.1.03.01'] = true;
                    $affectedAccounts['4.1.01.02'] = true;
                }

                if ($usageCharge > 0 && $needsPemakaian->has($bill->id)) {
                    $rows[] = $this->journalRow(
                        $nextId++,
                        $today,
                        $now,
                        $systemUser->id,
                        $bill,
                        '4.1.01.03',
                        'Piutang Denda ' . $keteranganSuffix,
                        $relasi,
                        $usageCharge
                    );
                    $jurnalPemakaian++;
                    $adaJurnalBaru = true;
                    $affectedAccounts['1.1.03.01'] = true;
                    $affectedAccounts['4.1.01.03'] = true;
                }

                // Hanya tagihan yang benar-benar menghasilkan jurnal yang
                // dihitung "diproses". Menghitung semua anggota `$targets`
                // membuat angka popup lebih besar dari kenyataan — dan
                // kalau semua barisnya nol, popup melaporkan ribuan tagihan
                // diproses padahal tabel tidak bertambah sama sekali.
                if ($adaJurnalBaru) {
                    $processed++;
                }
            }

            // Insert per chunk supaya satu statement tidak melebihi
            // max_allowed_packet untuk puluhan ribu baris.
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                DB::table('transactions')->insert($chunk);
            }

            DB::commit();

            // Trigger harus hidup kembali SEBELUM `amount` dihitung ulang, supaya
            // tabel `amount` tidak pernah tertinggal kalau proses ini mati tepat
            // di tengah jalan.
            $suspend->restore();

            // Gantikan pekerjaan trigger: hitung `amount` satu kali per akun.
            $this->recomputeAmount(array_keys($affectedAccounts), $today);

            $this->info("Berhasil memproses {$processed} tagihan menunggak (skip {$skipped}).");
            $this->storeRunSummary(
                $today,
                $thresholdDate,
                $processed,
                $skipped,
                $jurnalAbodemen,
                $jurnalPemakaian
            );
            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Wajib: kalau trigger tertinggal mati, tabel `amount` berhenti
            // ter-update untuk seluruh aplikasi.
            $suspend->restore();

            $this->error('Gagal: ' . $e->getMessage());
            $this->storeRunSummary($today, $thresholdDate, 0, $overdueBills->count(), 0, 0);
            return self::FAILURE;
        }
    }

    /**
     * Satu baris jurnal piutang dengan `id` dan `urutan` terisi sejak awal.
     */
    private function journalRow(
        int $id,
        string $today,
        $now,
        int $userId,
        $bill,
        string $kredit,
        string $keterangan,
        string $relasi,
        float $saldo
    ): array {
        return [
            'id'                   => $id,
            'tgl_transaksi'        => $today,
            'account_debet'        => '1.1.03.01',
            'account_kredit'       => $kredit,
            'transaction_group'    => null,
            'reverence_type'       => 'overdue_bill',
            'reverence_id'         => $bill->id,
            'keterangan_transaksi' => $keterangan,
            'relasi'               => $relasi,
            'saldo'                => $saldo,
            'id_user'              => $userId,
            'urutan'               => $id,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
    }

    /**
     * Hitung ulang tabel `amount` untuk akun-akun terdampak.
     *
     * Menggantikan trigger `create_amount_debit` yang sebelumnya melakukan hal
     * yang sama untuk setiap baris. Dipanggil SETELAH semua insert selesai,
     * jadi cukup satu kali per akun.
     */
    private function recomputeAmount(array $kodeAkuns, string $tanggal): void
    {
        if (empty($kodeAkuns)) {
            return;
        }

        $tahun = substr($tanggal, 0, 4);
        $bulan = substr($tanggal, 5, 2);
        $startOfYear = $tahun . '-01-01';
        $endOfYear  = $tahun . '-12-31';

        foreach (array_unique($kodeAkuns) as $kode) {
            $account = DB::table('accounts')->where('kode_akun', $kode)->first();

            if (! $account) {
                continue;
            }

            $row = DB::table('transactions')
                ->selectRaw('COALESCE(SUM(CASE WHEN account_debet = ? THEN saldo ELSE 0 END), 0) AS debit', [$kode])
                ->selectRaw('COALESCE(SUM(CASE WHEN account_kredit = ? THEN saldo ELSE 0 END), 0) AS kredit', [$kode])
                ->whereNull('deleted_at')
                ->whereBetween('tgl_transaksi', [$startOfYear, $endOfYear])
                ->where(function ($q) use ($kode) {
                    $q->where('account_debet', $kode)
                        ->orWhere('account_kredit', $kode);
                })
                ->first();

            DB::table('amount')->updateOrInsert(
                ['id' => (string) $account->id . $tahun . $bulan],
                [
                    'account_id' => $account->id,
                    'tahun'      => $tahun,
                    'bulan'      => $bulan,
                    'debit'      => $row->debit ?? 0,
                    'kredit'     => $row->kredit ?? 0,
                ]
            );
        }
    }

    /**
     * Label singkat: "Sep 2026"
     */
    private function periodLabel($bill)
    {
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        return ($months[$bill->billing_period_month] ?? '') . ' ' . $bill->billing_period_year;
    }

    /**
     * Label panjang: "September 2026" (dipakai di keterangan_transaksi).
     */
    private function periodLabelLong($bill)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return ($months[(int) $bill->billing_period_month] ?? '') . ' ' . $bill->billing_period_year;
    }

    /**
     * Simpan angka hasil run terakhir agar frontend bisa menampilkan
     * ringkasan yang AKURAT.
     *
     * Kenapa perlu: `DashboardController` sebelumnya menghitung ringkasan
     * dengan query `transactions WHERE tgl_transaksi = today`. Itu salah,
     * karena saat command dijalankan ulang dengan `--force=false`, semua
     * tagihan sudah punya jurnal sehingga TIDAK ada jurnal baru bertanggal
     * hari ini — hasilnya selalu 0 padahal command melaporkan ribuan tagihan
     * diproses. Angka di bawah berasal langsung dari loop yang sama, jadi
     * selalu sesuai dengan apa yang benar-benar terjadi.
     *
     * TTL 26 jam: cukup untuk menutup seluruh jendela "hari generate".
     */
    private function storeRunSummary(
        string $today,
        string $thresholdDate,
        int $processed,
        int $skipped,
        int $jurnalAbodemen,
        int $jurnalPemakaian
    ): void {
        $totalUnpaid = (int) MonthlyBill::where('status', 'unpaid')->count();
        $totalOverdue = (int) MonthlyBill::where('status', 'unpaid')
            ->where('due_date', '<', $today)
            ->count();

        Cache::put('overdue_gen_run_summary', [
            'date'                => $today,
            'threshold_date'      => $thresholdDate,
            'processed'           => $processed,
            'skipped'             => $skipped,
            'jurnal_abodemen'     => $jurnalAbodemen,
            'jurnal_pemakaian'    => $jurnalPemakaian,
            'total_unpaid'        => $totalUnpaid,
            'total_overdue'       => $totalOverdue,
            'timestamp'           => now()->toISOString(),
        ], now()->addHours(26));
    }
}
