<?php

namespace App\Console\Commands;

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
        $isForce  = (bool) $this->option('force');
        $bizFilter = $this->option('business');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib --dry-run atau --force');
            return self::FAILURE;
        }
        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        if ($bizFilter) $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");

        // 1. Build mapping legacy_usage_id → monthly_bill_id di DB baru
        //    lewat usages.tgl_pemakaian → (customer_id, year, month)
        $this->info('Building usage→bill map...');

        $usages = DB::connection('legacy')
            ->table('usages')
            ->whereNotNull('tgl_pemakaian')
            ->whereRaw('LENGTH(CAST(tgl_pemakaian AS CHAR)) >= 8')
            ->get(['id', 'tgl_pemakaian', 'id_instalasi', 'customer']);

        $this->info('Legacy usages with valid date: '.count($usages));

        // legacy_inst_id → new_ticket_id → customer_id (di DB baru)
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

        // ticket → customer row id
        $ticketToCustomer = DB::table('customers')
            ->pluck('id', 'ticket_id')
            ->all();

        // 2. Build map: legacy_usage_id → monthly_bill_id
        $usageToBill = [];   // legacy_usage_id → monthly_bills.id
        $usageToDate = [];   // legacy_usage_id → ['year'=>Y, 'month'=>M]
        $skippedUsages = [];

        foreach ($usages as $u) {
            try {
                $date = \Carbon\Carbon::parse($u->tgl_pemakaian);
            } catch (\Throwable) {
                continue;
            }
            $year = (int) $date->year;
            $month = (int) $date->month;

            $instId = (int) $u->id_instalasi;
            $ticketId = $instToTicket[$instId] ?? null;
            if (! $ticketId) {
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => 'no inst→ticket'];
                continue;
            }
            $customerId = $ticketToCustomer[$ticketId] ?? null;
            if (! $customerId) {
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => 'no customer'];
                continue;
            }

            $billId = DB::table('monthly_bills')
                ->where('customer_id', $customerId)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->value('id');

            if (! $billId) {
                $skippedUsages[] = ['usage_id' => $u->id, 'reason' => "no monthly_bill for cust={$customerId} {$year}-{$month}"];
                continue;
            }

            $usageToBill[(int) $u->id] = (int) $billId;
            $usageToDate[(int) $u->id] = ['year' => $year, 'month' => $month];
        }
        $this->info('usage→bill mapped: '.count($usageToBill));
        $this->info('usage→bill skipped: '.count($skippedUsages));

        // 3. Existing bill_payments by bill_id (1 bayar event per bill, kita track by composite key bill_id+paid_at)
        $existingBillPays = [];
        foreach (DB::table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
            $key = $bp->bill_id.'|'.date('Y-m-d', strtotime((string) $bp->paid_at)).'|'.number_format((float) $bp->amount_paid, 2, '.', '');
            $existingBillPays[$key] = true;
        }
        $this->info('Existing bill_payments: '.count($existingBillPays));

        // 4. confirmed_by: legacy user → new user
        $existingMax = DB::table('users')->max('id') ?? 0;
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);
        $newUsersAll = DB::table('users')->get(['id', 'name', 'role']);
        $byNameRole = [];
        foreach ($newUsersAll as $u) {
            $key = strtolower(trim($u->name)).'|'.strtolower($u->role);
            if (! isset($byNameRole[$key])) $byNameRole[$key][] = (int) $u->id;
        }
        $userMap = [];
        foreach ($legacyUsers as $lu) {
            $role = match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'surveyor',
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
        $trxs = DB::connection('legacy')->table('transactions')
            ->where(function ($q) {
                $q->where('keterangan', 'LIKE', 'Bayar%')
                  ->orWhere('keterangan', 'LIKE', 'Pendapatan%');
            })
            ->where('usage_id', '>', 0)
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('usage_id')
            ->orderBy('tgl_transaksi')
            ->orderBy('id')
            ->get();
        $this->info('Total Bayar/Pendapatan trx: '.count($trxs));

        // Group in memory
        $groups = [];
        foreach ($trxs as $t) {
            $uid = (int) $t->usage_id;
            $tgl = (string) $t->tgl_transaksi;
            $key = $uid.'|'.$tgl;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'usage_id' => $uid,
                    'tgl_transaksi' => $tgl,
                    'rows' => [],
                    'total' => 0,
                    'legacy_user_id' => (int) ($t->user_id ?? 0),
                    'min_id' => (int) $t->id,
                ];
            }
            $groups[$key]['rows'][] = $t;
            $groups[$key]['total'] += (int) $t->total;
        }
        $this->info('Unique bayar events: '.count($groups));

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $skipped = [];
        $failed = [];
        $billsPaidByBp = []; // track bill_id yg ke-update ke paid

        $i = 0;
        foreach ($groups as $key => $g) {
            $i++;
            try {
                $usageId = (int) $g['usage_id'];
                $billId = $usageToBill[$usageId] ?? null;
                if (! $billId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $usageId, 'tgl' => $g['tgl_transaksi'], 'reason' => 'no monthly_bill mapping'];
                    continue;
                }

                $paidAt = null;
                $tgl = trim((string) $g['tgl_transaksi']);
                if ($tgl !== '' && strlen($tgl) >= 8) {
                    try {
                        $paidAt = \Carbon\Carbon::parse($tgl)->toDateTimeString();
                    } catch (\Throwable) {
                        $paidAt = null;
                    }
                }
                if (! $paidAt) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $usageId, 'reason' => 'tgl_transaksi invalid'];
                    continue;
                }

                $confirmedBy = $userMap[(int) ($g['legacy_user_id'] ?? 0)] ?? null;
                if (! $confirmedBy || ! DB::table('users')->where('id', $confirmedBy)->exists()) {
                    $confirmedBy = $fallbackAdmin;
                }

                $amount = (float) $g['total'];
                $paidAtDate = date('Y-m-d', strtotime($paidAt));
                $dupKey = $billId.'|'.$paidAtDate.'|'.number_format($amount, 2, '.', '');

                if (isset($existingBillPays[$dupKey])) {
                    $stats['updated']++;
                } else {
                    if (! $isDryRun) {
                        DB::table('bill_payments')->insert([
                            'bill_id' => $billId,
                            'amount_paid' => $amount,
                            'confirmed_by' => $confirmedBy,
                            'paid_at' => $paidAt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $existingBillPays[$dupKey] = true;
                    $stats['created']++;
                    $billsPaidByBp[$billId] = ($billsPaidByBp[$billId] ?? 0) + $amount;
                }

                if ($i % $chunkSize === 0 || $i === count($groups)) {
                    $this->maybeLog($i, $chunkSize, count($groups), $stats);
                }
            } catch (\Throwable $e) {
                $stats['failed']++;
                if (count($failed) < 50) $failed[] = ['key' => $key, 'error' => $e->getMessage()];
            }
        }

        // 6. Update monthly_bills.status berdasarkan bill_payments total
        $this->line('');
        $this->info('Updating monthly_bills.status based on bill_payments...');
        $updatedBills = 0;
        if (! $isDryRun) {
            // Sum bill_payments per bill_id
            $billTotals = DB::table('bill_payments')
                ->select('bill_id', DB::raw('SUM(amount_paid) AS total_paid'))
                ->groupBy('bill_id')
                ->get();
            foreach ($billTotals as $bt) {
                $bill = DB::table('monthly_bills')->where('id', $bt->bill_id)->first();
                if (! $bill) continue;
                $totalAmount = (float) $bill->total_amount;
                $totalPaid = (float) $bt->total_paid;
                $newStatus = $totalPaid >= $totalAmount - 0.01 ? 'paid' : 'unpaid';
                if ($bill->status !== $newStatus) {
                    DB::table('monthly_bills')->where('id', $bt->bill_id)->update([
                        'status' => $newStatus,
                        'updated_at' => now(),
                    ]);
                    $updatedBills++;
                }
            }
        }
        $this->line('  bills status updated: '.$updatedBills);

        $this->line('');
        $this->info('Ringkasan:');
        foreach ($stats as $k => $v) $this->line("  $k : $v");
        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Skipped (10):');
            foreach (array_slice($skipped, 0, 10) as $s) $this->line('  '.json_encode($s));
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Failed (10):');
            foreach (array_slice($failed, 0, 10) as $f) $this->line('  '.json_encode($f));
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
