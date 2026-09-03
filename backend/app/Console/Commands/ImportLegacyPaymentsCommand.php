<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPaymentsCommand extends Command
{
    protected $signature = 'import:payments
                            {--dry-run : Simulasi}
                            {--force   : Insert beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=200 : Chunk progress}';

    protected $description = 'Import legacy transactions (Biaya instalasi) → payments';

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

        // 1. Build mapping legacy_inst_id → new_ticket_id (sama seperti bills)
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

        // 2. Existing payments: key by ticket_id (1 ticket dgn type=installation_fee biasanya cuma 1 row)
        $existingByTicket = [];
        foreach (DB::table('payments')->where('type', 'installation_fee')->get(['id', 'ticket_id', 'paid_at', 'amount']) as $p) {
            $existingByTicket[(int) $p->ticket_id] = $p;
        }
        $this->info('Existing installation_fee payments: '.count($existingByTicket));

        // 3. confirmed_by: legacy user_id → new user.id
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
        // Fallback admin
        $fallbackAdmin = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // 4. Loop Biaya instalasi transactions
        $trxs = DB::connection('legacy')->table('transactions')
            ->where('keterangan', 'LIKE', 'Biaya instalasi%')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->get();
        $this->info('Total Biaya instalasi transactions: '.count($trxs));

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $skipped = [];
        $failed  = [];

        foreach ($trxs as $i => $t) {
            try {
                $legacyInstId = (int) $t->installation_id;
                $ticketId = $instToTicket[$legacyInstId] ?? null;
                if (! $ticketId) {
                    $stats['skipped']++;
                    if (count($skipped) < 50) $skipped[] = ['trx_id' => $t->id, 'reason' => 'no inst→ticket mapping'];
                    continue;
                }

                $amount = (float) $t->total;
                $paidAt = null;
                if ($t->tgl_transaksi && trim((string) $t->tgl_transaksi) !== '' && strlen(trim((string) $t->tgl_transaksi)) >= 8) {
                    try {
                        $paidAt = \Carbon\Carbon::parse($t->tgl_transaksi)->toDateTimeString();
                    } catch (\Throwable) {
                        $paidAt = null;
                    }
                }

                $confirmedBy = $userMap[(int) ($t->user_id ?? 0)] ?? null;
                if (! $confirmedBy || ! DB::table('users')->where('id', $confirmedBy)->exists()) {
                    $confirmedBy = $fallbackAdmin;
                }

                if (isset($existingByTicket[$ticketId])) {
                    // Update path: kalau amount/paid_at beda
                    $existing = $existingByTicket[$ticketId];
                    if (! $isDryRun) {
                        DB::table('payments')->where('id', $existing->id)->update([
                            'amount' => $amount,
                            'status' => 'confirmed',
                            'confirmed_by' => $confirmedBy,
                            'paid_at' => $paidAt,
                            'updated_at' => now(),
                        ]);
                    }
                    $stats['updated']++;
                } else {
                    if (! $isDryRun) {
                        DB::table('payments')->insert([
                            'ticket_id' => $ticketId,
                            'amount' => $amount,
                            'type' => 'installation_fee',
                            'status' => 'confirmed',
                            'confirmed_by' => $confirmedBy,
                            'paid_at' => $paidAt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $stats['created']++;
                    $existingByTicket[$ticketId] = (object) ['id' => 0]; // track untuk skip duplicate legacy entry
                }

                if (($i + 1) % $chunkSize === 0 || ($i + 1) === count($trxs)) {
                    $this->maybeLog($i + 1, $chunkSize, count($trxs), $stats);
                }
            } catch (\Throwable $e) {
                $stats['failed']++;
                if (count($failed) < 50) $failed[] = ['trx_id' => $t->id, 'error' => $e->getMessage()];
            }
        }

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
