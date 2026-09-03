<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixCustomerGapsCommand extends Command
{
    protected $signature = 'customers:fix-gaps
                            {--dry-run : Cuma simulasi}
                            {--force   : Jalankan insert/update beneran}
                            {--chunk=200 : Ukuran chunk progress}';

    protected $description = 'Tambah customers row untuk ticket completed yang belum punya, dengan 1 row per legacy_customer_id';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce  = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib --dry-run atau --force');
            return self::FAILURE;
        }
        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');

        // 1. packageMap (legacy → new)
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
        $this->info('packageMap: '.count($packageMap).' legacy → new');

        // 2. Bulk load SEMUA legacy inst (status='A') + nama customers
        // Optimasi: 1 query aja, join di memory
        $this->info('Bulk-loading legacy inst+customer (status=A)...');
        $rows = DB::connection('legacy')
            ->table('installations AS i')
            ->join('customers AS c', 'c.id', '=', 'i.customer_id')
            ->where('i.status', 'A')
            ->select('i.id AS inst_id', 'i.package_id', 'i.customer_id', 'i.desa', 'i.kode_instalasi', 'i.aktif', 'c.nama', 'c.hp')
            ->get();
        $this->info('Loaded '.count($rows).' legacy inst rows');

        // Group by (nama|desa|legacy_pkg_id)
        $byTriple = [];
        foreach ($rows as $r) {
            $name = strtolower(trim((string) ($r->nama ?? '')));
            $key = $name.'|'.((int) $r->desa).'|'.((int) $r->package_id);
            if (! isset($byTriple[$key])) {
                $byTriple[$key] = [];
            }
            $byTriple[$key][] = $r;
        }
        $this->info('Unique triples: '.count($byTriple));

        // 3. Existing data
        $existingUsersByEmail = User::pluck('id', 'email')->all();
        $existingCustomerCodes = array_flip(Customer::pluck('customer_code')->all());
        $existingCustByTicket = Customer::pluck('id', 'ticket_id')->all();

        // 4. Ambil ticket gap
        $gaps = DB::table('installation_tickets AS t')
            ->leftJoin('customers AS c', 'c.ticket_id', '=', 't.id')
            ->whereNull('c.id')
            ->where('t.status', 'completed')
            ->orderBy('t.id')
            ->select('t.id', 't.applicant_name', 't.village_id', 't.package_id', 't.user_id')
            ->get();
        $this->info('Tickets completed tanpa customers row: '.count($gaps));

        $stats = [
            'new_user' => 0,
            'reused_user' => 0,
            'new_cust' => 0,
            'linked' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $skipped = [];
        $failed  = [];

        // Track inst yang sudah diambil supaya tiap inst hanya 1 customer row
        $instTaken = [];

        // Pre-load: dari existing customers rows, recover legacy inst_id via suffix _cNNN
        // Bulk: extract semua legacy_cust_id dari suffix, lalu 1 query ke legacy installations
        $legacyCustIdsFromExisting = [];
        $existingCustCodes = DB::table('customers')->pluck('customer_code')->all();
        foreach ($existingCustCodes as $cc) {
            if (preg_match('/_c(\d+)(_\d+)?$/', $cc, $m)) {
                $legacyCustIdsFromExisting[(int) $m[1]] = true;
            }
        }
        if (! empty($legacyCustIdsFromExisting)) {
            $instRows = DB::connection('legacy')->table('installations')
                ->where('status', 'A')
                ->whereIn('customer_id', array_keys($legacyCustIdsFromExisting))
                ->select('id', 'customer_id')
                ->get();
            foreach ($instRows as $ir) {
                // Hanya inst terlama per customer_id (kalau ada multi, ini akan di-overwrite,
                // tapi kita cukup untuk detect "sudah pernah di-pakai")
                $instTaken[(int) $ir->id] = true;
            }
        }
        $this->info('Pre-loaded instTaken: '.count($instTaken));

        $now = now();
        $insertBuffer = [];
        $ticketUpdates = [];

        foreach ($gaps as $i => $t) {
            try {
                $name = strtolower(trim($t->applicant_name));
                $legacyPkgIds = $newPkgIdToLegacy[(int) $t->package_id] ?? [];
                if (empty($legacyPkgIds)) {
                    $stats['skipped']++;
                    $skipped[] = ['ticket_id' => $t->id, 'reason' => 'no legacy pkg mapping'];
                    continue;
                }

                // Cari candidate by triple (nama|village|legacy_pkg)
                $candidates = [];
                foreach ($legacyPkgIds as $lp) {
                    $key = $name.'|'.((int) $t->village_id).'|'.((int) $lp);
                    if (isset($byTriple[$key])) {
                        $candidates = array_merge($candidates, $byTriple[$key]);
                    }
                }
                if (empty($candidates)) {
                    $stats['skipped']++;
                    $skipped[] = ['ticket_id' => $t->id, 'reason' => 'no legacy inst match', 'name' => $t->applicant_name];
                    continue;
                }

                // Pilih inst yang belum di-take
                $pick = null;
                foreach ($candidates as $cand) {
                    if (! isset($instTaken[(int) $cand->inst_id])) {
                        $pick = $cand;
                        break;
                    }
                }
                if (! $pick) {
                    // semua sdh dipakai → ambil terlama
                    usort($candidates, fn($a, $b) => $a->inst_id <=> $b->inst_id);
                    $pick = $candidates[0];
                }

                $legacyCustId = (int) $pick->customer_id;
                $realName = trim((string) ($pick->nama ?? ''));
                if ($realName === '') {
                    $stats['skipped']++;
                    $skipped[] = ['ticket_id' => $t->id, 'reason' => 'nama kosong'];
                    continue;
                }

                // Email: prefixed dengan legacy_cust_id agar unique
                $local = strtolower($realName);
                $local = preg_replace('/[^a-z0-9]/', '', $local);
                if ($local === '') {
                    $local = 'user'.$legacyCustId;
                }
                $email = $local.'.'.$legacyCustId.'@gmail.com';

                if ($isDryRun) {
                    $stats['new_cust']++;
                    $instTaken[(int) $pick->inst_id] = true;
                    continue;
                }

                // 1. User
                if (isset($existingUsersByEmail[$email])) {
                    $userId = $existingUsersByEmail[$email];
                    $stats['reused_user']++;
                } else {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $realName,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'role' => 'pelanggan',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $existingUsersByEmail[$email] = $userId;
                    $stats['new_user']++;
                }

                // 2. customer_code
                $code = (string) ($pick->kode_instalasi ?? '');
                if ($code === '' || $code === '0') {
                    $code = 'LEGACY_'.$legacyCustId;
                }
                // Selalu suffix dengan legacy_cust_id untuk uniqueness
                $code = $code.'_c'.$legacyCustId;
                // Cek duplicate, kalau bentrok tambah counter
                if (isset($existingCustomerCodes[$code])) {
                    $base = $code;
                    $n = 2;
                    while (isset($existingCustomerCodes[$base.'_'.$n])) {
                        $n++;
                    }
                    $code = $base.'_'.$n;
                }

                // 3. activated_at
                $activatedAt = null;
                $aktif = $pick->aktif ?? null;
                if ($aktif && trim((string) $aktif) !== '' && trim((string) $aktif) !== '0000-00-00') {
                    try {
                        $activatedAt = \Carbon\Carbon::parse($aktif)->toDateTimeString();
                    } catch (\Throwable) {
                        $activatedAt = null;
                    }
                }

                DB::table('customers')->insert([
                    'ticket_id' => (int) $t->id,
                    'user_id' => $userId,
                    'customer_code' => $code,
                    'initial_meter_reading' => 0,
                    'meter_photo_url' => null,
                    'activated_at' => $activatedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $existingCustByTicket[(int) $t->id] = true;
                $existingCustomerCodes[$code] = true;
                $instTaken[(int) $pick->inst_id] = true;
                $stats['new_cust']++;

                // 4. ticket.user_id kalau null
                if ($t->user_id === null) {
                    $ticketUpdates[(int) $t->id] = $userId;
                    $stats['linked']++;
                }

                $this->maybeLog($i + 1, $chunkSize, count($gaps), $stats);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $failed[] = ['ticket_id' => $t->id, 'name' => $t->applicant_name, 'error' => $e->getMessage()];
                $this->maybeLog($i + 1, $chunkSize, count($gaps), $stats);
            }
        }

        // Bulk update tickets
        if (! $isDryRun && ! empty($ticketUpdates)) {
            foreach ($ticketUpdates as $tid => $uid) {
                DB::table('installation_tickets')
                    ->where('id', $tid)
                    ->whereNull('user_id')
                    ->update(['user_id' => $uid, 'updated_at' => now()]);
            }
        }

        $this->line('');
        $this->info('Ringkasan:');
        foreach ($stats as $k => $v) {
            $this->line("  $k : $v");
        }

        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Dilewati (20):');
            foreach (array_slice($skipped, 0, 20) as $s) {
                $this->line('  '.json_encode($s, JSON_UNESCAPED_UNICODE));
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Gagal (50):');
            foreach (array_slice($failed, 0, 50) as $f) {
                $this->line('  '.json_encode($f, JSON_UNESCAPED_UNICODE));
            }
        }

        return self::SUCCESS;
    }

    private function codeTaken(string $code, array $existing): bool
    {
        if (isset($existing[$code])) {
            return true;
        }
        foreach (array_keys($existing) as $ex) {
            if (str_starts_with($ex, $code.'_')) {
                return true;
            }
        }
        return false;
    }

    private function maybeLog(int $current, int $chunkSize, int $total, array $stats): void
    {
        if ($current % $chunkSize === 0 || $current === $total) {
            $pct = round($current / max(1, $total) * 100, 1);
            $this->line(sprintf(
                '  [%5d/%5d] %5.1f%%  new_user=%d reused=%d cust=%d skip=%d fail=%d link=%d',
                $current, $total, $pct,
                $stats['new_user'], $stats['reused_user'],
                $stats['new_cust'], $stats['skipped'], $stats['failed'], $stats['linked']
            ));
        }
    }
}
