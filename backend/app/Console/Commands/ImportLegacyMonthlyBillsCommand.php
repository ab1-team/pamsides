<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\InstallationPackage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyMonthlyBillsCommand extends Command
{
    protected $signature = 'import:monthly-bills
                            {--dry-run : Simulasi}
                            {--force   : Insert beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=2000 : Chunk progress log}';

    protected $description = 'Import legacy usages → monthly_bills (1 row per customer per bulan)';

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

        // 1. Build ticket/customer mapping
        //
        // ANCHOR IDENTITAS: `installation_tickets.legacy_installation_id`
        // (lihat docs/database.md §4 `installation_tickets`). Heuristik triple
        // nama|desa|paket sudah DIBUANG dari jalur ini: pada data produksi ia
        // menautkan 198/201 instalasi ke tiket yang SALAH (nama ganda).
        $this->info('Building mappings...');

        // legacy installations: TANPA filter status ('A' dulu membuang 31
        // instalasi yang tetap punya usages/tagihan). Penentu akhir tetap
        // keberadaan baris `customers` ($ticketToCustomer) di bawah. Karena
        // itu `$instRows` / `$villageMap` / `$byTriple` / `$newByTriple` /
        // `$packageMap` tidak lagi dibutuhkan oleh mapping ini dan DIHAPUS
        // (tidak ada bagian lain di file ini yang memakainya).
        $instToTicket = [];
        foreach (DB::table('installation_tickets')
            ->whereNotNull('legacy_installation_id')
            ->orderBy('id')
            ->get(['id', 'legacy_installation_id']) as $t) {
            $legacyInstId = (int) $t->legacy_installation_id;
            if (! isset($instToTicket[$legacyInstId])) {
                $instToTicket[$legacyInstId] = (int) $t->id;
            }
        }

        // ticket → customer row + package_id
        $ticketInfo = DB::table('installation_tickets')
            ->get(['id', 'package_id'])
            ->keyBy('id')
            ->all();
        $ticketToCustomer = DB::table('customers')
            ->whereIn('ticket_id', array_unique(array_values($instToTicket)))
            ->pluck('id', 'ticket_id')
            ->all();
        $this->info('ticket→customer: '.count($ticketToCustomer));

        // 2. Paket abodemen map (new_pkg_id → monthly_abodemen)
        $abodemenByPkg = [];
        foreach (DB::table('installation_packages')->get(['id', 'monthly_abodemen']) as $p) {
            $abodemenByPkg[(int) $p->id] = (float) $p->monthly_abodemen;
        }
        $this->info('Paket dgn abodemen: '.count($abodemenByPkg));

        // 3. Existing monthly_bills (customer_id|year|month → row)
        $existingMB = [];
        foreach (DB::table('monthly_bills')->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month']) as $mb) {
            $existingMB["{$mb->customer_id}|{$mb->billing_period_year}|{$mb->billing_period_month}"] = (int) $mb->id;
        }
        $this->info('Existing monthly_bills: '.count($existingMB));

        // 4. Pre-compute penalty lookup
        // Untuk tiap customer, cari bulan-bulan unpaid sebelum current bulan → cek 2 bulan sebelum
        // Kita pre-load semua monthly_bills (existing), sort by customer+periode
        $allBills = DB::table('monthly_bills')
            ->orderBy('customer_id')
            ->orderBy('billing_period_year')
            ->orderBy('billing_period_month')
            ->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month', 'status']);
        $billsByCustomer = [];
        foreach ($allBills as $b) {
            $billsByCustomer[(int) $b->customer_id][] = $b;
        }

        // 5. Loop usages
        $usages = DB::connection('legacy')->table('usages')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->get();
        $this->info('Total usages: '.count($usages));

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'no_ticket' => 0,
            'no_customer' => 0,
        ];
        $skipped = [];
        $failed = [];

        $batch = [];
        $batchSize = 500;
        $totalRows = count($usages);

        foreach ($usages as $i => $u) {
            try {
                $legacyInstId = (int) $u->id_instalasi;
                $ticketId = $instToTicket[$legacyInstId] ?? null;
                if (! $ticketId) {
                    $stats['skipped']++;
                    $stats['no_ticket']++;
                    if (count($skipped) < 50) {
                        $skipped[] = ['usage_id' => $u->id, 'reason' => 'no inst→ticket'];
                    }

                    continue;
                }
                $customerId = $ticketToCustomer[$ticketId] ?? null;
                if (! $customerId) {
                    $stats['skipped']++;
                    $stats['no_customer']++;
                    if (count($skipped) < 50) {
                        $skipped[] = ['usage_id' => $u->id, 'reason' => 'no customer'];
                    }

                    continue;
                }
                $newPkgId = (int) ($ticketInfo[$ticketId]->package_id ?? 0);
                $abodemen = $abodemenByPkg[$newPkgId] ?? 0;

                $tgl = trim((string) ($u->tgl_pemakaian ?? ''));
                if ($tgl === '' || strlen($tgl) < 8) {
                    $stats['skipped']++;

                    continue;
                }
                try {
                    $date = Carbon::parse($tgl);
                } catch (\Throwable) {
                    $stats['skipped']++;

                    continue;
                }
                $year = (int) $date->year;
                $month = (int) $date->month;

                // meter values
                $start = (int) filter_var((string) ($u->awal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                $end = (int) filter_var((string) ($u->akhir ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                if ($start < 0) {
                    $start = 0;
                }
                if ($end < 0) {
                    $end = 0;
                }
                $usageM3 = max(0, $end - $start);

                // usage_charge dari legacy `nominal` (varchar/numeric)
                $nominal = (int) filter_var((string) ($u->nominal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                if ($nominal < 0) {
                    $nominal = 0;
                }

                // status
                $statusMap = ['PAID' => 'paid', 'UNPAID' => 'unpaid', 'NON' => 'unpaid'];
                $status = $statusMap[strtoupper(trim((string) ($u->status ?? 'UNPAID')))] ?? 'unpaid';

                // due_date dari tgl_akhir
                $dueDate = null;
                $tglAkhir = trim((string) ($u->tgl_akhir ?? ''));
                if ($tglAkhir !== '' && strlen($tglAkhir) >= 8) {
                    try {
                        $dueDate = Carbon::parse($tglAkhir)->toDateString();
                    } catch (\Throwable) {
                        $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
                    }
                } else {
                    $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
                }

                // Penalty: cek tagihan 1 bulan sebelum (Bulan N-1) → kalau unpaid, pakai late_penalty dari paket
                $penalty = 0;
                $checkDate = $date->copy()->subMonths(1);
                $checkKey = "{$customerId}|{$checkDate->year}|{$checkDate->month}";
                if (isset($existingMB[$checkKey])) {
                    $checkBillId = $existingMB[$checkKey];
                    $checkBill = null;
                    foreach ($billsByCustomer[(int) $customerId] ?? [] as $b) {
                        if ((int) $b->id === (int) $checkBillId) {
                            $checkBill = $b;
                            break;
                        }
                    }
                    if ($checkBill && $checkBill->status === 'unpaid') {
                        $latePenalty = InstallationPackage::where('id', $newPkgId)->value('late_penalty');
                        $penalty = (float) ($latePenalty ?? 0);
                    }
                }

                $total = $nominal + $abodemen + $penalty;

                $key = "{$customerId}|{$year}|{$month}";
                $now = now();
                $data = [
                    'customer_id' => (int) $customerId,
                    'billing_period_year' => $year,
                    'billing_period_month' => $month,
                    'meter_reading_start' => $start,
                    'meter_reading_end' => $end,
                    'usage_m3' => $usageM3,
                    'usage_charge' => $nominal,
                    'abodemen' => $abodemen,
                    'penalty_amount' => $penalty,
                    'total_amount' => $total,
                    'status' => $status,
                    'due_date' => $dueDate,
                    'updated_at' => $now,
                ];

                if (isset($existingMB[$key])) {
                    if (! $isDryRun) {
                        DB::table('monthly_bills')->where('id', $existingMB[$key])->update($data);
                    }
                    $stats['updated']++;
                } else {
                    $data['created_at'] = $now;
                    $batch[] = $data;
                    $existingMB[$key] = 0; // placeholder
                    $stats['created']++;

                    if (count($batch) >= $batchSize) {
                        if (! $isDryRun) {
                            DB::table('monthly_bills')->insert($batch);
                            // Append to billsByCustomer for penalty chain
                            foreach ($batch as $b) {
                                $billsByCustomer[(int) $b['customer_id']][] = (object) [
                                    'id' => 0,
                                    'customer_id' => $b['customer_id'],
                                    'billing_period_year' => $b['billing_period_year'],
                                    'billing_period_month' => $b['billing_period_month'],
                                    'status' => $b['status'],
                                ];
                            }
                        }
                        $batch = [];
                    }
                }

                if (($i + 1) % $chunkSize === 0 || ($i + 1) === $totalRows) {
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                }
            } catch (\Throwable $e) {
                $stats['failed']++;
                if (count($failed) < 50) {
                    $failed[] = ['usage_id' => $u->id, 'error' => $e->getMessage()];
                }
            }
        }

        if (! empty($batch)) {
            if (! $isDryRun) {
                DB::table('monthly_bills')->insert($batch);
            }
            $batch = [];
        }

        $this->line('');
        $this->info('Mapping (anchor legacy_installation_id):');
        $this->line('  inst→ticket: '.count($instToTicket));
        $this->line('  usages tanpa tiket: '.$stats['no_ticket']);
        $this->line('  usages tanpa pelanggan: '.$stats['no_customer']);

        $this->line('');
        $this->info('Ringkasan:');
        foreach ($stats as $k => $v) {
            $this->line("  $k : $v");
        }
        $this->line('  pasangan dibuat: '.$stats['created']);
        $this->line('  pasangan diupdate: '.$stats['updated']);
        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Skipped (10):');
            foreach (array_slice($skipped, 0, 10) as $s) {
                $this->line('  '.json_encode($s, JSON_UNESCAPED_UNICODE));
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Failed (10):');
            foreach (array_slice($failed, 0, 10) as $f) {
                $this->line('  '.json_encode($f, JSON_UNESCAPED_UNICODE));
            }
        }

        return self::SUCCESS;
    }

    private function maybeLog(int $current, int $chunkSize, int $total, array $stats): void
    {
        if ($current % $chunkSize === 0 || $current === $total) {
            $pct = round($current / max(1, $total) * 100, 1);
            $this->line(sprintf(
                '  [%5d/%5d] %5.1f%%  created=%d updated=%d skip=%d fail=%d',
                $current, $total, $pct,
                $stats['created'], $stats['updated'], $stats['skipped'], $stats['failed']
            ));
        }
    }
}
