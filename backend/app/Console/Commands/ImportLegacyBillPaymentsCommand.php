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
            $wantSuffixed = "{$lp->kelas} (B{$lp->business_id})";
            $wantPlain    = $lp->kelas;
            foreach ($newPkgs as $np) {
                if (strcasecmp($np->name, $wantSuffixed) === 0 || strcasecmp($np->name, $wantPlain) === 0) {
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

        // Build legacy.villages.id → new.villages.id map by nama+dusun
        $villageMap = [];
        $legacyVillages = DB::connection('legacy')->table('villages')->get();
        $localVillages = DB::table('villages')->get();
        foreach ($legacyVillages as $lv) {
            $nama = strtolower(trim((string) $lv->nama));
            $dusun = strtolower(trim((string) ($lv->dusun ?? '')));
            foreach ($localVillages as $nv) {
                $matchNama = strtolower(trim((string) $nv->village_name)) === $nama;
                $matchDusun = $dusun === '' || $dusun === '-'
                    ? strtolower(trim((string) $nv->hamlet_name)) === ''
                    : strtolower(trim((string) $nv->hamlet_name)) === $dusun;
                if ($matchNama && $matchDusun) {
                    $villageMap[(int) $lv->id] = (int) $nv->id;
                    break;
                }
            }
        }

        $byTriple = [];
        foreach ($instRows as $li) {
            $cust = $customersById[(int) $li->customer_id] ?? null;
            if (! $cust) continue;
            $name = strtolower(trim((string) ($cust->nama ?? '')));
            if ($name === '') continue;
            $newVillage = $villageMap[(int) $li->desa] ?? null;
            if ($newVillage === null) continue;
            $key = $name.'|'.$newVillage.'|'.((int) $li->package_id);
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

        // Agregasi 1 grup (semantik identik dengan versi ->get(): user_id & id dari baris PERTAMA,
        // total = jumlah seluruh baris dalam grup).
        $emptyGroup = fn (int $uid, string $tgl, int $firstId, int $firstUser) => [
            'usage_id'       => $uid,
            'tgl_transaksi'  => $tgl,
            'total'          => 0,
            'legacy_user_id' => $firstUser,
            'min_id'         => $firstId,
        ];

        // Proses & tulis satu grup event ke bill_payments (logika sama persis, tanpa 'rows').
        $processGroup = function (array $g) use (
            &$stats, &$skipped, &$failed, &$events, &$billsPaidByBp,
            $usageToBill, $userMap, $fallbackAdmin, $existingBillPays, $isDryRun
        ) {
            $events++;
            $usageId = (int) $g['usage_id'];
            try {
                $billId = $usageToBill[$usageId] ?? null;
                if (! $billId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['usage_id' => $usageId, 'tgl' => $g['tgl_transaksi'], 'reason' => 'no monthly_bill mapping'];
                    return;
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
                    return;
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
            } catch (\Throwable $e) {
                $stats['failed']++;
                if (count($failed) < 50) $failed[] = ['key' => $usageId.'|'.$g['tgl_transaksi'], 'error' => $e->getMessage()];
            }
        };

        // Urutan (usage_id, tgl_transaksi, id) dipertahankan persis seperti versi ->get():
        // baris pertama tiap (usage_id|tgl_transaksi) tetap sama, sehingga legacy_user_id & total identik.
        // Anchor dibuat NULL-safe: tgl_transaksi bisa '' (bukan tanggal valid) di legacy, dan
        // membandingkan kolom DATE dengan '' memicu error 1525 di MySQL strict mode.
        $lastUid  = 0;
        $lastTgl  = null;   // null = belum ada baris sama sekali
        $curAnchor = null;  // anchor yang sudah DIPROSES (null = belum proses baris apa pun)
        $curKey   = null;   // key grup yang sedang diakumulasi
        $cur      = null;
        $after    = null;   // anchor untuk kelanjutan (resume)

        $applyAfter = function ($q) use (&$after, &$curAnchor) {
            if ($after === null) return $q;
            [$auid, $atgl, $aid] = $after;
            $q->where(function ($w) use ($auid, $atgl, $aid) {
                $w->where('usage_id', '>', $auid)
                  ->orWhere(function ($q2) use ($auid, $atgl, $aid) {
                      $q2->where('usage_id', '=', $auid);
                      if ($atgl === null) {
                          $q2->whereNotNull('tgl_transaksi');
                      } else {
                          $q2->where(function ($q3) use ($atgl, $aid) {
                              $q3->where('tgl_transaksi', '>', $atgl)
                                 ->orWhere(function ($q4) use ($atgl, $aid) {
                                     $q4->where('tgl_transaksi', '=', $atgl)->where('id', '>', $aid);
                                 });
                          });
                      }
                  });
            });
            return $q;
        };

        retry:
        $attempt = 0;
        $maxRetries = 8;
        while (true) {
            try {
                $q = $baseQuery();
                if ($after !== null) $q = $applyAfter($q);
                $cursor = $q->orderBy('usage_id')
                    ->orderBy('tgl_transaksi')
                    ->orderBy('id')
                    ->select(['id', 'usage_id', 'tgl_transaksi', 'total', 'user_id'])
                    ->cursor();

                $pendingResume = ($after !== null); // grup pertama loop ini pending bila lanjut dari anchor
                foreach ($cursor as $t) {
                    $scanned++;
                    $uid = (int) $t->usage_id;
                    $tgl = (string) $t->tgl_transaksi;
                    $key = $uid.'|'.$tgl;

                    if ($curKey !== $key) {
                        // Baris berganti grup. Flush grup sebelumnya, kecuali grup itu adalah
                        // kelanjutan resume yang belum utuh (pendResume=true) → dibiarkan utuh saat replay.
                        if ($curKey !== null && ! $pendingResume) {
                            $processGroup($cur);
                            if ($scanned % $chunkSize === 0) $this->maybeLog($scanned, $chunkSize, $totalTrx, $stats, $events);
                        }
                        $pendingResume = false;
                        $curKey = $key;
                        $cur = $emptyGroup($uid, $tgl, (int) $t->id, (int) ($t->user_id ?? 0));
                    }
                    $cur['total'] += (int) $t->total;

                    $lastUid = $uid;
                    $lastTgl = $t->tgl_transaksi === null ? null : (string) $t->tgl_transaksi;
                    $after   = [$uid, $lastTgl, (int) $t->id];
                }
                break; // selesai normal
            } catch (\Throwable $e) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    $this->error('Max retries: '.$e->getMessage());
                    break;
                }
                $wait = min(60, 5 * $attempt);
                $anchorDesc = $after === null ? 'start' : ("usage_id={$after[0]}, tgl=".($after[1] ?? 'null').", id={$after[2]}");
                $this->warn("Legacy putus (try {$attempt}/{$maxRetries}): ".substr($e->getMessage(), 0, 80)." — sleep {$wait}s, resume after ".$anchorDesc);
                DB::purge('legacy');
                // resume tanpa melewatkan baris: cross-check baris terakhir yang sudah diproses.
                // Baris yang gagal saat fetch belum ada di $after → tidak ada yang terlewat.
                sleep($wait);
            }
        }

        // flush grup terakhir
        if ($curKey !== null) {
            $processGroup($cur);
        }

        $this->info('Legacy rows scanned: '.$scanned);
        $this->info('Unique bayar events: '.$events);

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
