<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyMeterReadingsCommand extends Command
{
    protected $signature = 'import:meter-readings
                            {--dry-run : Simulasi, tidak insert}
                            {--force   : Insert beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=500 : Chunk progress log}';

    protected $description = 'Import legacy usages → meter_readings (1 row per customer per bulan)';

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

        // 1. Build mapping: legacy_inst_id → new_ticket_id (via ticket.applicant_name|village|package)
        //    plus: legacy_inst_id → new_customer_id (via ticket_id)
        $this->info('Building ticket map...');

        // packageMap legacy → new
        $packageMap = [];
        $newPkgIdToLegacy = [];
        $legacyPkgs = DB::connection('legacy')->table('packages')->get(['id', 'business_id', 'kelas']);
        $newPkgs = DB::table('installation_packages')->get();
        foreach ($legacyPkgs as $lp) {
            $want = "{$lp->kelas} (B{$lp->business_id})";
            foreach ($newPkgs as $np) {
                if (strcasecmp($np->name, $want) === 0) {
                    $packageMap[(int) $lp->id] = (int) $np->id;
                    $newPkgIdToLegacy[(int) $np->id][] = (int) $lp->id;
                    break;
                }
            }
        }
        $this->info('packageMap size: '.count($packageMap));

        $rows = DB::connection('legacy')->select(
            "SELECT id, desa, package_id, customer_id
             FROM installations
             WHERE status = 'A'"
        );

        $customersById = [];
        $legacyCust = DB::connection('legacy')->table('customers')->get(['id', 'nama']);
        foreach ($legacyCust as $c) {
            $customersById[(int) $c->id] = $c;
        }

        // Group legacy inst by (nama|village|legacy_pkg) → list of inst_ids (urut ascending)
        $byTriple = [];
        foreach ($rows as $li) {
            $cust = $customersById[(int) $li->customer_id] ?? null;
            if (! $cust) continue;
            $name = strtolower(trim((string) ($cust->nama ?? '')));
            if ($name === '') continue;
            $key = $name.'|'.((int) $li->desa).'|'.((int) $li->package_id);
            if (! isset($byTriple[$key])) {
                $byTriple[$key] = [];
            }
            $byTriple[$key][] = (int) $li->id;
        }
        $this->info('byTriple size: '.count($byTriple));

        // Index new tickets by triple (lowered name|village_id|new_package_id)
        $newTickets = DB::table('installation_tickets')->orderBy('id')->get(['id', 'applicant_name', 'village_id', 'package_id']);
        $newByTriple = [];
        foreach ($newTickets as $nt) {
            $name = strtolower(trim((string) $nt->applicant_name));
            $key = $name.'|'.((int) $nt->village_id).'|'.((int) $nt->package_id);
            if (! isset($newByTriple[$key])) {
                $newByTriple[$key] = (int) $nt->id;
            }
        }
        $this->info('newByTriple size: '.count($newByTriple));

        // Map: legacy_inst_id → new_ticket_id
        // Translate each legacy triple key from legacy_pkg_id to new_pkg_id, then lookup
        $instToTicket = [];
        foreach ($byTriple as $key => $instIds) {
            // key format: name|village|legacy_pkg
            $parts = explode('|', $key);
            $name = $parts[0];
            $village = $parts[1];
            $legacyPkg = $parts[2];
            $newPkg = $packageMap[(int) $legacyPkg] ?? null;
            if ($newPkg === null) continue;
            $newKey = $name.'|'.$village.'|'.$newPkg;
            if (isset($newByTriple[$newKey])) {
                $ticketId = $newByTriple[$newKey];
                foreach ($instIds as $iid) {
                    $instToTicket[(int) $iid] = $ticketId;
                }
            }
        }
        $this->info('Mapped '.count($instToTicket).' legacy inst → ticket');

        // ticket_id → customer_id (dari customers.ticket_id)
        $ticketToCustomer = DB::table('customers')
            ->whereIn('ticket_id', array_unique(array_values($instToTicket)))
            ->pluck('id', 'ticket_id')
            ->all();
        $this->info('Mapped ticket → customer rows: '.count($ticketToCustomer));

        // 2. Existing meter_readings keys (customer_id + year + month)
        $existingMR = [];
        $mrRows = DB::table('meter_readings')->get(['customer_id', 'reading_year', 'reading_month']);
        foreach ($mrRows as $mr) {
            $existingMR["{$mr->customer_id}|{$mr->reading_year}|{$mr->reading_month}"] = true;
        }
        $this->info('Existing meter_readings: '.count($existingMR));

        // 3. recorded_by: legacy cater (user_id) → new user.id
        $userMap = [];
        $existingMax = DB::table('users')->max('id') ?? 0;
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);
        $newUsersByNameRole = [];
        $newUsersAll = DB::table('users')->get(['id', 'name', 'role']);
        foreach ($newUsersAll as $u) {
            $key = strtolower(trim($u->name)).'|'.strtolower($u->role);
            if (! isset($newUsersByNameRole[$key])) {
                $newUsersByNameRole[$key][] = (int) $u->id;
            }
        }
        foreach ($legacyUsers as $lu) {
            $role = match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'surveyor',
                7 => 'teknisi',
                default => 'admin',
            };
            $key = strtolower(trim((string) $lu->nama)).'|'.strtolower($role);
            if (isset($newUsersByNameRole[$key]) && ! empty($newUsersByNameRole[$key])) {
                $userMap[(int) $lu->id] = (int) array_shift($newUsersByNameRole[$key]);
            } else {
                $userMap[(int) $lu->id] = $existingMax + (int) $lu->id;
            }
        }

        // 4. Loop usages - bulk insert version
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

        $batchSize = 500;
        $batch = [];
        $totalRows = count($usages);

        // Pre-load fallback recorded_by (teknisi pertama)
        $fallbackUser = DB::table('users')->where('role', 'teknisi')->value('id')
            ?? DB::table('users')->where('role', 'admin')->value('id')
            ?? 1;

        foreach ($usages as $i => $u) {
            try {
                $legacyInstId = (int) $u->id_instalasi;
                $ticketId = $instToTicket[$legacyInstId] ?? null;
                if (! $ticketId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'no inst→ticket mapping'];
                    continue;
                }
                $customerId = $ticketToCustomer[$ticketId] ?? null;
                if (! $customerId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'no customer row for ticket_id='.$ticketId];
                    continue;
                }

                $tgl = trim((string) ($u->tgl_pemakaian ?? ''));
                if ($tgl === '' || strlen($tgl) < 8) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'tgl_pemakaian kosong'];
                    continue;
                }
                try {
                    $date = \Carbon\Carbon::parse($tgl);
                } catch (\Throwable) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $u->id, 'reason' => 'tgl_pemakaian invalid'];
                    continue;
                }
                $year  = (int) $date->year;
                $month = (int) $date->month;

                $meterValue = (int) filter_var((string) ($u->akhir ?? '0'), FILTER_SANITIZE_NUMBER_INT);
                if ($meterValue < 0) $meterValue = 0;

                $cater = (int) ($u->cater ?? 0);
                $recordedBy = $userMap[$cater] ?? null;
                if (! $recordedBy || ! DB::table('users')->where('id', $recordedBy)->exists()) {
                    $recordedBy = $fallbackUser;
                }

                $key = "{$customerId}|{$year}|{$month}";
                $now = now();

                if (isset($existingMR[$key])) {
                    // Update path — batch update lebih lambat, langsung query saja
                    if (! $isDryRun) {
                        DB::table('meter_readings')
                            ->where('customer_id', $customerId)
                            ->where('reading_year', $year)
                            ->where('reading_month', $month)
                            ->update([
                                'recorded_by' => (int) $recordedBy,
                                'meter_value' => $meterValue,
                                'recorded_at' => $date->toDateTimeString(),
                                'updated_at' => $now,
                            ]);
                    }
                    $stats['updated']++;
                } else {
                    $batch[] = [
                        'customer_id'   => (int) $customerId,
                        'recorded_by'   => (int) $recordedBy,
                        'reading_year'  => $year,
                        'reading_month' => $month,
                        'meter_value'   => $meterValue,
                        'photo_url'     => null,
                        'recorded_at'   => $date->toDateTimeString(),
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                    $existingMR[$key] = true;
                    $stats['created']++;

                    if (count($batch) >= $batchSize) {
                        if (! $isDryRun) {
                            DB::table('meter_readings')->insert($batch);
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

        // Flush sisa
        if (! empty($batch)) {
            if (! $isDryRun) {
                DB::table('meter_readings')->insert($batch);
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
