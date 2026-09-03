<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\InstallationPackage;
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
        $isForce  = (bool) $this->option('force');
        $bizFilter = $this->option('business');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib --dry-run atau --force');
            return self::FAILURE;
        }
        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        if ($bizFilter) $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");

        // 1. Build ticket/customer mapping (sama seperti meter_readings)
        $this->info('Building mappings...');

        $packageMap = [];
        $legacyPkgs = DB::connection('legacy')->table('packages')->get(['id', 'business_id', 'kelas']);
        $newPkgs = DB::table('installation_packages')->get();
        foreach ($legacyPkgs as $lp) {
            $want = "{$lp->kelas} (B{$lp->business_id})";
            foreach ($newPkgs as $np) {
                if (strcasecmp($np->name, $want) === 0) {
                    $packageMap[(int) $lp->id] = (int) $np->id;
                    break;
                }
            }
        }

        $instRows = DB::connection('legacy')
            ->table('installations')
            ->where('status', 'A')
            ->get(['id', 'desa', 'package_id', 'customer_id']);

        $customersById = [];
        foreach (DB::connection('legacy')->table('customers')->get(['id', 'nama']) as $c) {
            $customersById[(int) $c->id] = $c;
        }

        $byTriple = [];
        foreach ($instRows as $li) {
            $cust = $customersById[(int) $li->customer_id] ?? null;
            if (! $cust) continue;
            $name = strtolower(trim((string) ($cust->nama ?? '')));
            if ($name === '') continue;
            $key = $name.'|'.((int) $li->desa).'|'.((int) $li->package_id);
            if (! isset($byTriple[$key])) $byTriple[$key] = [];
            $byTriple[$key][] = (int) $li->id;
        }

        $newTickets = DB::table('installation_tickets')->orderBy('id')->get(['id', 'applicant_name', 'village_id', 'package_id']);
        $newByTriple = [];
        foreach ($newTickets as $nt) {
            $name = strtolower(trim((string) $nt->applicant_name));
            $key = $name.'|'.((int) $nt->village_id).'|'.((int) $nt->package_id);
            if (! isset($newByTriple[$key])) {
                $newByTriple[$key] = (int) $nt->id;
            }
        }

        $instToTicket = [];
        foreach ($byTriple as $key => $instIds) {
            $parts = explode('|', $key);
            $newPkg = $packageMap[(int) $parts[2]] ?? null;
            if ($newPkg === null) continue;
            $newKey = $parts[0].'|'.$parts[1].'|'.$newPkg;
            if (isset($newByTriple[$newKey])) {
                foreach ($instIds as $iid) {
                    $instToTicket[(int) $iid] = $newByTriple[$newKey];
                }
            }
        }
        $this->info('inst→ticket: '.count($instToTicket));

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
        ];
        $skipped = [];
        $failed  = [];

        $batch = [];
        $batchSize = 500;
        $totalRows = count($usages);

        foreach ($usages as $i => $u) {
            try {
                $legacyInstId = (int) $u->id_instalasi;
                $ticketId = $instToTicket[$legacyInstId] ?? null;
                if (! $ticketId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'no inst→ticket'];
                    continue;
                }
                $customerId = $ticketToCustomer[$ticketId] ?? null;
                if (! $customerId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'no customer'];
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
                    $date = \Carbon\Carbon::parse($tgl);
                } catch (\Throwable) {
                    $stats['skipped']++;
                    continue;
                }
                $year  = (int) $date->year;
                $month = (int) $date->month;

                // meter values
                $start = (int) filter_var((string) ($u->awal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                $end   = (int) filter_var((string) ($u->akhir ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                if ($start < 0) $start = 0;
                if ($end < 0)   $end = 0;
                $usageM3 = max(0, $end - $start);

                // usage_charge dari legacy `nominal` (varchar/numeric)
                $nominal = (int) filter_var((string) ($u->nominal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                if ($nominal < 0) $nominal = 0;

                // status
                $statusMap = ['PAID' => 'paid', 'UNPAID' => 'unpaid', 'NON' => 'unpaid'];
                $status = $statusMap[strtoupper(trim((string) ($u->status ?? 'UNPAID')))] ?? 'unpaid';

                // due_date dari tgl_akhir
                $dueDate = null;
                $tglAkhir = trim((string) ($u->tgl_akhir ?? ''));
                if ($tglAkhir !== '' && strlen($tglAkhir) >= 8) {
                    try {
                        $dueDate = \Carbon\Carbon::parse($tglAkhir)->toDateString();
                    } catch (\Throwable) {
                        $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
                    }
                } else {
                    $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
                }

                // Penalty: cek tagihan 2 bulan sebelum (Bulan N-2) → kalau unpaid, pakai late_penalty dari paket
                $penalty = 0;
                $checkDate = $date->copy()->subMonths(2);
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
                    'customer_id'           => (int) $customerId,
                    'billing_period_year'   => $year,
                    'billing_period_month'  => $month,
                    'meter_reading_start'   => $start,
                    'meter_reading_end'     => $end,
                    'usage_m3'              => $usageM3,
                    'usage_charge'          => $nominal,
                    'abodemen'              => $abodemen,
                    'penalty_amount'        => $penalty,
                    'total_amount'          => $total,
                    'status'                => $status,
                    'due_date'              => $dueDate,
                    'updated_at'            => $now,
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
                if (count($failed) < 50) $failed[] = ['usage_id' => $u->id, 'error' => $e->getMessage()];
            }
        }

        if (! empty($batch)) {
            if (! $isDryRun) {
                DB::table('monthly_bills')->insert($batch);
            }
            $batch = [];
        }

        $this->line('');
        $this->info('Ringkasan:');
        foreach ($stats as $k => $v) $this->line("  $k : $v");
        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Skipped (10):');
            foreach (array_slice($skipped, 0, 10) as $s) $this->line('  '.json_encode($s, JSON_UNESCAPED_UNICODE));
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Failed (10):');
            foreach (array_slice($failed, 0, 10) as $f) $this->line('  '.json_encode($f, JSON_UNESCAPED_UNICODE));
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
