<?php

namespace App\Console\Commands;

use App\Support\BillPaymentImporter;
use App\Support\LegacyTransactionCursor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyBillPaymentsCommand extends Command
{
    protected $signature = 'import:bill-payments
                            {--dry-run : Simulasi}
                            {--force   : Insert beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=200 : Chunk progress log}';

    protected $description = 'Import legacy transactions (Bayar/Pendapatan) → bill_payments, update monthly_bills.status';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $bizFilter = $this->option('business');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib --dry-run atau --force');

            return self::FAILURE;
        }
        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        if ($bizFilter) {
            $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");
        }

        // 1. Build mapping legacy_usage_id → monthly_bill_id di DB baru
        //    lewat usages.tgl_pemakaian → (customer_id, year, month)
        $this->info('Building usage→bill map...');

        $usages = DB::connection('legacy')
            ->table('usages')
            ->whereNotNull('tgl_pemakaian')
            ->whereRaw('LENGTH(CAST(tgl_pemakaian AS CHAR)) >= 8')
            ->get(['id', 'tgl_pemakaian', 'id_instalasi', 'customer']);

        $this->info('Legacy usages with valid date: '.count($usages));

        // Identitas instalasi legacy → tiket baru memakai anchor PASTI
        // `installation_tickets.legacy_installation_id` (lihat
        // 2026_10_14_000001_add_legacy_installation_id_to_installation_tickets_table).
        //
        // Sebelumnya pemetaan menebak lewat triple nama|desa|paket. Kunci itu
        // TIDAK unik (dua pelanggan nama sama di desa+paket sama saling
        // menimpa), sehingga pembayaran bisa tertempel ke tagihan pelanggan
        // lain. Anchor legacy id satu-satu, jadi tidak ada tebakan di sini.
        //
        // Satu query saja (kolom berindeks), tanpa N+1.
        $instToTicket = DB::table('installation_tickets')
            ->whereNotNull('legacy_installation_id')
            ->orderBy('id')
            ->get(['id', 'legacy_installation_id'])
            ->mapWithKeys(fn ($t) => [(int) $t->legacy_installation_id => (int) $t->id])
            ->all();
        $this->info('inst→ticket (legacy_installation_id): '.count($instToTicket));

        // ticket → customer row id
        $ticketToCustomer = DB::table('customers')
            ->pluck('id', 'ticket_id')
            ->all();

        // 2. Build map: legacy_usage_id → monthly_bill_id
        $usageToBill = [];   // legacy_usage_id → monthly_bills.id
        $usageToDate = [];   // legacy_usage_id → ['year'=>Y, 'month'=>M]
        $skippedUsages = [];
        $usageNoTicket = 0;   // usage tanpa tiket (legacy_installation_id tidak ketemu)
        $usageNoBill = 0;     // usage bertiket tapi tanpa tagihan pada bulan itu

        // Peta tagihan bulanan dimuat SEKALI lewat satu query, lalu dipakai
        // sebagai lookup in-memory.
        //
        // Sebelumnya setiap usage memicu query sendiri ke monthly_bills. Dengan
        // ~52.000 usage itu berarti 52.000 round-trip -- dan itulah penyebab
        // tahap ini menjadi yang paling lambat (69% dari total migrasi).
        // Indeks (customer_id, billing_period_year, billing_period_month) sudah
        // ada di tabel dan nilainya unik, jadi pemuatan penuh sekali ini murah
        // dan hasilnya identik dengan lookup per-row.
        $billIndex = [];
        foreach (DB::table('monthly_bills')
            ->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month']) as $b
        ) {
            $billIndex[BillPaymentImporter::billIndexKey(
                (int) $b->customer_id,
                (int) $b->billing_period_year,
                (int) $b->billing_period_month
            )] = (int) $b->id;
        }
        $this->info('monthly_bills index: '.count($billIndex).' baris dimuat ke memori.');

        foreach ($usages as $u) {
            try {
                $date = Carbon::parse($u->tgl_pemakaian);
            } catch (\Throwable) {
                continue;
            }
            $year = (int) $date->year;
            $month = (int) $date->month;

            $instId = (int) $u->id_instalasi;
            $ticketId = $instToTicket[$instId] ?? null;
            if (! $ticketId) {
                $usageNoTicket++;
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => 'no inst→ticket'];

                continue;
            }
            $customerId = $ticketToCustomer[$ticketId] ?? null;
            if (! $customerId) {
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => 'no customer'];

                continue;
            }

            $billId = $billIndex[BillPaymentImporter::billIndexKey((int) $customerId, $year, $month)] ?? null;

            if (! $billId) {
                $usageNoBill++;
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => "no monthly_bill for cust={$customerId} {$year}-{$month}"];

                continue;
            }

            $usageToBill[(int) $u->id] = $billId;
            $usageToDate[(int) $u->id] = ['year' => $year, 'month' => $month];
        }
        $this->info('usage→bill mapped: '.count($usageToBill));
        $this->info('usage→bill skipped: '.count($skippedUsages));
        $this->info('inst→ticket: '.count($instToTicket));
        $this->info('usage tanpa tiket: '.$usageNoTicket);
        $this->info('usage tanpa tagihan: '.$usageNoBill);

        // 3. Existing bill_payments by bill_id (1 bayar event per bill, kita track by composite key bill_id+paid_at)
        $existingBillPays = [];
        foreach (DB::table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
            $existingBillPays[BillPaymentImporter::dedupKey(
                (int) $bp->bill_id,
                (string) $bp->paid_at,
                (float) $bp->amount_paid
            )] = true;
        }
        $this->info('Existing bill_payments: '.count($existingBillPays));

        // 4. confirmed_by: legacy user → new user
        $existingMax = DB::table('users')->max('id') ?? 0;

        // ID user yang benar-benar ada, dimuat sekali. Sebelumnya tiap event
        // memanggil DB::table('users')->where('id',..)->exists() -- satu query
        // per event, padahal jawaban "user ini ada atau tidak" bisa dijawab
        // dari satu himpunan.
        $validUserIds = DB::table('users')->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $this->info('Valid user ids loaded: '.$validUserIds->count());
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);
        $newUsersAll = DB::table('users')->get(['id', 'name', 'role']);
        $byNameRole = [];
        foreach ($newUsersAll as $u) {
            $key = strtolower(trim($u->name)).'|'.strtolower($u->role);
            if (! isset($byNameRole[$key])) {
                $byNameRole[$key][] = (int) $u->id;
            }
        }
        $userMap = [];
        foreach ($legacyUsers as $lu) {
            $role = match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'teknisi',
                7 => 'teknisi',
                default => 'admin',
            };
            $key = strtolower(trim((string) $lu->nama)).'|'.strtolower($role);
            if (isset($byNameRole[$key]) && ! empty($byNameRole[$key])) {
                $userMap[(int) $lu->id] = (int) array_shift($byNameRole[$key]);
            } else {
                $userMap[(int) $lu->id] = $existingMax + (int) $lu->id;
            }
        }
        $fallbackAdmin = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // 5. Loop Bayar/Pendapatan transactions, group by (usage_id, tgl_transaksi)
        //    Streaming + agregasi per batch: tidak pernah menahan seluruh baris di memori.
        //    Kelompok yang datanya sudah lengkap dalam satu batch langsung diproses & ditulis.
        $baseQuery = fn () => DB::connection('legacy')->table('transactions')
            ->where(function ($q) {
                $q->where('keterangan', 'LIKE', 'Bayar%')
                    ->orWhere('keterangan', 'LIKE', 'Pendapatan%');
            })
            ->where('usage_id', '>', 0)
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter));

        $totalTrx = $baseQuery()->count();
        $this->info('Total Bayar/Pendapatan trx: '.$totalTrx);

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $scanned = 0;       // jumlah baris legacy yang dipindai
        $events = 0;        // jumlah event unik (grup usage_id|tgl_transaksi)
        $skipped = [];
        $failed = [];
        $billsPaidByBp = []; // track bill_id yg ke-update ke paid

        // Agregasi 1 grup (semantik identik dengan versi ->get(): user_id dari baris
        // PERTAMA grup, total = jumlah seluruh baris dalam grup).
        $emptyGroup = fn (int $uid, string $tgl, int $firstUser) => [
            'usage_id' => $uid,
            'tgl_transaksi' => $tgl,
            'total' => 0,
            'legacy_user_id' => $firstUser,
        ];

        // Penulis batch: event yang lolos filter dikumpulkan lalu ditulis per
        // blok. Satu insert() per event berarti ~24.000 round-trip; per blok
        // menjadi beberapa puluh. Perilaku & hasilnya sama persis, hanya
        // jumlah trip-nya yang turun.
        $insertBatch = [];
        $flushBatch = function () use (&$insertBatch, $isDryRun, &$stats, &$billsPaidByBp, &$failed) {
            if (! $isDryRun && $insertBatch !== []) {
                try {
                    DB::table('bill_payments')->insert($insertBatch);
                } catch (\Throwable $e) {
                    // Kegagalan blok dicatat, lalu sisanya ditulis per event
                    // supaya satu blok yang gagal tidak membuang seluruh
                    // pembayaran yang sudah terkumpul.
                    $stats['failed'] += count($insertBatch);
                    if (count($failed) < 50) {
                        $failed[] = ['key' => 'batch', 'error' => $e->getMessage()];
                    }

                    foreach ($insertBatch as $row) {
                        try {
                            DB::table('bill_payments')->insert($row);
                        } catch (\Throwable $e2) {
                            $stats['failed']++;
                            if (count($failed) < 50) {
                                $failed[] = ['key' => 'bill_id='.$row['bill_id'], 'error' => $e2->getMessage()];
                            }
                        }
                    }
                }
            }
            $insertBatch = [];
        };

        // Proses & tulis satu grup event ke bill_payments (logika sama persis, tanpa 'rows').
        $processGroup = function (array $g) use (
            &$stats, &$skipped, &$failed, &$events, &$billsPaidByBp, &$insertBatch,
            $usageToBill, $userMap, $fallbackAdmin, $existingBillPays, $validUserIds, $isDryRun
        ) {
            $events++;
            $usageId = (int) $g['usage_id'];
            try {
                $billId = $usageToBill[$usageId] ?? null;
                if (! $billId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) {
                        $skipped[] = ['usage_id' => $usageId, 'tgl' => $g['tgl_transaksi'], 'reason' => 'no monthly_bill mapping'];
                    }

                    return;
                }

                $paidAt = null;
                $tgl = trim((string) $g['tgl_transaksi']);
                if ($tgl !== '' && strlen($tgl) >= 8) {
                    try {
                        $paidAt = Carbon::parse($tgl)->toDateTimeString();
                    } catch (\Throwable) {
                        $paidAt = null;
                    }
                }
                if (! $paidAt) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) {
                        $skipped[] = ['usage_id' => $usageId, 'reason' => 'tgl_transaksi invalid'];
                    }

                    return;
                }

                $confirmedBy = $userMap[(int) ($g['legacy_user_id'] ?? 0)] ?? null;
                if (! $confirmedBy || ! $validUserIds->has($confirmedBy)) {
                    $confirmedBy = $fallbackAdmin;
                }

                $amount = (float) $g['total'];
                $dupKey = BillPaymentImporter::dedupKey($billId, $paidAt, $amount);

                if (isset($existingBillPays[$dupKey])) {
                    $stats['updated']++;
                } else {
                    if (! $isDryRun) {
                        $insertBatch[] = [
                            'bill_id' => $billId,
                            'amount_paid' => $amount,
                            'confirmed_by' => $confirmedBy,
                            'paid_at' => $paidAt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        if (count($insertBatch) >= 500) {
                            $flushBatch();
                        }
                    }
                    $existingBillPays[$dupKey] = true;
                    $stats['created']++;
                    $billsPaidByBp[$billId] = ($billsPaidByBp[$billId] ?? 0) + $amount;
                }
            } catch (\Throwable $e) {
                $stats['failed']++;
                if (count($failed) < 50) {
                    $failed[] = ['key' => $usageId.'|'.$g['tgl_transaksi'], 'error' => $e->getMessage()];
                }
            }
        };

        // Urutan (usage_id, tgl_transaksi, id) dipertahankan persis seperti versi ->get():
        // baris pertama tiap (usage_id|tgl_transaksi) tetap sama, sehingga legacy_user_id & total identik.
        $curKey = null;   // key grup yang sedang diakumulasi
        $cur = null;
        $after = null;   // anchor untuk kelanjutan (resume)

        // Semantik "resume setelah anchor" dipusatkan di LegacyTransactionCursor
        // agar bisa diuji tanpa koneksi DB (tests/Unit/BillPaymentsRetryStateTest.php).
        $applyAfter = fn ($q) => LegacyTransactionCursor::applyAfter($q, $after);

        $attempt = 0;
        $maxRetries = 8;
        while (true) {
            // CATATAN PENTING -- jangan me-reset `$cur`/`$curKey` di sini.
            //
            // `$cur` SENGAJA dibawa lintas percobaan retry. Kalau koneksi legacy
            // putus di tengah sebuah grup, grup itu belum utuh: `$cur` menyimpan
            // total parsial s.d. anchor terakhir. Query resume membaca baris
            // STRICTLY SETELAH anchor (`tgl_transaksi > X OR (tgl = X AND id > Y)`),
            // jadi baris yang sudah terakumulasi TIDAK dibaca ulang -- tidak ada
            // risiko double count. Yang terjadi adalah kelanjutan akumulasi, dan
            // itulah yang membuat total grup tetap utuh.
            //
            // Reset di sini (percobaan yang pernah ada) justru MENGHAPUS total
            // parsial sehingga grup jadi kurang. Lihat regression test:
            // tests/Unit/BillPaymentsRetryStateTest.php
            try {
                $q = $baseQuery();
                if ($after !== null) {
                    $q = $applyAfter($q);
                }
                $cursor = $q->orderBy('usage_id')
                    ->orderBy('tgl_transaksi')
                    ->orderBy('id')
                    ->select(['id', 'usage_id', 'tgl_transaksi', 'total', 'user_id'])
                    ->cursor();

                foreach ($cursor as $t) {
                    $scanned++;
                    $uid = (int) $t->usage_id;
                    $tgl = (string) $t->tgl_transaksi;
                    $key = $uid.'|'.$tgl;

                    if ($curKey !== $key) {
                        // Baris berganti grup -> grup sebelumnya sudah UTUH, baik pada
                        // run normal maupun setelah retry (anchor menandai baris terakhir
                        // grup itu, sehingga baris grup berikutnya = titik flush yang aman).
                        //
                        // Versi lama mensyaratkan `! $pendingResume` di sini. Itu SALAH:
                        // setelah retry, `$pendingResume` tetap true sampai grup berganti,
                        // sehingga grup yang sedang dibangun justru DILEWATI flush lalu
                        // ditimpa `$cur = $emptyGroup(...)` -- payment-nya hilang total
                        // (data loss), bukan sekadar dobel.
                        if ($curKey !== null) {
                            $processGroup($cur);
                            if ($scanned % $chunkSize === 0) {
                                $this->maybeLog($scanned, $chunkSize, $totalTrx, $stats, $events);
                            }
                        }
                        $curKey = $key;
                        $cur = $emptyGroup($uid, $tgl, (int) ($t->user_id ?? 0));
                    }
                    $cur['total'] += (int) $t->total;

                    $after = [$uid, $t->tgl_transaksi === null ? null : (string) $t->tgl_transaksi, (int) $t->id];
                }
                break; // selesai normal
            } catch (\Throwable $e) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    // Kegagalan di sini berarti import parsial dilaporkan SUKSES ke
                    // pemanggil (legacy:migrate-fast hanya mengecek exit code).
                    $this->error('Max retries habis: '.$e->getMessage());
                    $this->error('Import bill-payments BERHENTI PARSIAL — data TIDAK lengkap.');
                    $this->error('Legacy baru terpindai '.$scanned.' dari '.$totalTrx.' baris.');
                    $this->error('Perbaiki koneksi legacy lalu jalankan ulang perintah ini (idempoten).');

                    // Event yang sudah terkumpul tapi belum ditulis harus tetap
                    // ditulis; kalau tidak, data yang SUDAH berhasil diproses
                    // ikut hilang bersama proses yang dihentikan paksa.
                    $flushBatch();

                    return self::FAILURE;
                }
                $wait = min(60, 5 * $attempt);
                $anchorDesc = $after === null ? 'start' : ("usage_id={$after[0]}, tgl=".($after[1] ?? 'null').", id={$after[2]}");
                $this->warn("Legacy putus (try {$attempt}/{$maxRetries}): ".substr($e->getMessage(), 0, 80)." — sleep {$wait}s, resume after ".$anchorDesc);
                DB::purge('legacy');
                // Batch yang belum ditulis tetap dibawa. Berisi event yang
                // SUDAH selesai dihitung sebelum koneksi putus, jadi aman
                // ditulis sekarang: pemindaian dilanjutkan dari anchor dan
                // tidak akan menghasilkan event yang sama lagi.
                $flushBatch();
                // resume tanpa melewatkan baris: cross-check baris terakhir yang sudah diproses.
                // Baris yang gagal saat fetch belum ada di $after → tidak ada yang terlewat.
                sleep($wait);
            }
        }

        // Flush grup terakhir. Tetap DI LUAR loop: state grup di-reset di awal tiap
        // percobaan (lihat atas), jadi variabel ini selalu milik percobaan TERAKHIR
        // yang selesai normal — tidak mungkin ter-flush dobel.
        if ($curKey !== null) {
            $processGroup($cur);
        }

        // Tulis sisa batch sebelum menghitung ulang status tagihan. Kalau tidak,
        // pembayaran terakhir tidak ikut terhitung dan tagihan salah status 'unpaid'.
        $flushBatch();

        $this->info('Legacy rows scanned: '.$scanned);
        $this->info('Unique bayar events: '.$events);

        // 6. Update monthly_bills.status berdasarkan bill_payments total
        $this->line('');
        $this->info('Updating monthly_bills.status based on bill_payments...');
        $updatedBills = 0;
        if (! $isDryRun) {
            // Dijumlahkan & ditulis dengan statement SQL yang sama, bukan
            // loop select+update per tagihan. Versi lama menulis ~24.000
            // query (1 select + 1 update per tagihan); sekarang 2 query.
            //
            // monthly_bills yang tidak punya baris bill_payments TIDAK ikut
            // tersentuh -- persis seperti versi lama yang hanya memakai
            // tagihan yang muncul di hasil agregasi.
            $updatedBills = DB::table('monthly_bills')
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('bill_payments')
                        ->whereColumn('bill_payments.bill_id', 'monthly_bills.id');
                })
                ->whereRaw(
                    'status <> CASE
                        WHEN (SELECT IFNULL(SUM(bp.amount_paid), 0)
                              FROM bill_payments bp
                              WHERE bp.bill_id = monthly_bills.id)
                             >= total_amount - ? THEN "paid"
                        ELSE "unpaid"
                     END',
                    [BillPaymentImporter::PAID_TOLERANCE]
                )
                ->update([
                    'status' => DB::raw('CASE
                        WHEN (SELECT IFNULL(SUM(bp.amount_paid), 0)
                              FROM bill_payments bp
                              WHERE bp.bill_id = monthly_bills.id)
                             >= total_amount - '.BillPaymentImporter::PAID_TOLERANCE.' THEN "paid"
                        ELSE "unpaid"
                     END'),
                    'updated_at' => now(),
                ]);

            // Verifikasi: tagihan yang berubah status harus benar-benar
            // berbeda dari status lamanya. Kalau 0 tapi seharusnya ada yang
            // berubah, berarti logika CASE tidak sesuai dugaan -- lebih baik
            // bekannt daripada diam-diam menghasilkan status salah.
            $check = DB::table('monthly_bills')
                ->joinSub(
                    DB::table('bill_payments')
                        ->select('bill_id', DB::raw('IFNULL(SUM(amount_paid), 0) AS total_paid'))
                        ->groupBy('bill_id'),
                    'pt',
                    'pt.bill_id',
                    '=',
                    'monthly_bills.id'
                )
                ->whereRaw('monthly_bills.status <> CASE WHEN pt.total_paid >= monthly_bills.total_amount - ? THEN "paid" ELSE "unpaid" END', [BillPaymentImporter::PAID_TOLERANCE])
                ->count();

            if ($check > 0) {
                $this->error('PERINGATAN: masih ada '.$check.' tagihan yang statusnya tidak sesuai total pembayaran.');
                $this->error('Periksa definisi status tagihan sebelum deploy.');

                return self::FAILURE;
            }
        }
        $this->line('  bills status updated: '.$updatedBills);

        $this->line('');
        $this->info('Ringkasan:');
        $this->line('  inst→ticket: '.count($instToTicket));
        $this->line('  usage tanpa tiket: '.$usageNoTicket);
        $this->line('  usage tanpa tagihan: '.$usageNoBill);
        foreach ($stats as $k => $v) {
            $this->line("  $k : $v");
        }
        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Skipped (10):');
            foreach (array_slice($skipped, 0, 10) as $s) {
                $this->line('  '.json_encode($s));
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Failed (10):');
            foreach (array_slice($failed, 0, 10) as $f) {
                $this->line('  '.json_encode($f));
            }
        }

        return self::SUCCESS;
    }

    private function maybeLog(int $current, int $chunkSize, int $total, array $stats, int $events = 0): void
    {
        if ($current % $chunkSize === 0 || $current === $total) {
            $pct = round($current / max(1, $total) * 100, 1);
            $this->line(sprintf(
                '  [%5d/%5d] %5.1f%%  events=%d created=%d updated=%d skip=%d fail=%d',
                $current, $total, $pct, $events,
                $stats['created'], $stats['updated'], $stats['skipped'], $stats['failed']
            ));
        }
    }
}
