<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportLegacyCustomersCommand extends Command
{
    protected $signature = 'import:customers
                            {--dry-run : Cuma simulasi, tidak insert}
                            {--force   : Jalankan insert/update beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=200 : Ukuran chunk progress logging}';

    protected $description = 'Import legacy customers → users role=pelanggan + customers row + update installation_tickets.user_id';

    /**
     * Map: legacy customer_id → installation_tickets.id (yang ber-status A, terlama)
     */
    private array $ticketMap = [];

    /**
     * Cache: legacy installation.id → new installation_ticket.id (via map dari ticketMap)
     * Dibangun dari DB legacy + DB baru join via applicant+village+package.
     */
    private array $newTicketByLegacyInstId = [];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce  = (bool) $this->option('force');
        $bizFilter = $this->option('business');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! $isDryRun && ! $isForce) {
            $this->error('Wajib pakai --dry-run atau --force');
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('>>> MODE DRY-RUN: tidak ada insert/update <<<');
        } else {
            $this->error('>>> MODE FORCE: data akan di-insert <<<');
        }
        if ($bizFilter) {
            $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");
        }

        $this->buildTicketMap($bizFilter);
        $this->info('Tiket mapping: '.count($this->ticketMap).' customer legacy → installation_tickets.id baru');

        // Existing users by (email) & customers by (ticket_id, customer_code) — pre-load sekali
        $existingUsersByEmail = User::pluck('id', 'email')->all();
        $existingCustomersByTicket = Customer::pluck('id', 'ticket_id')->all();
        // Track semua customer_code yang sudah dipakai (untuk handle duplicate kode_instalasi)
        $existingCustomerCodes = array_flip(Customer::pluck('customer_code')->all());
        // Pre-load existing customer_code per ticket_id (untuk update path)
        $existingCustomerCodeByTicket = Customer::pluck('customer_code', 'ticket_id')->all();
        $this->info(sprintf(
            'Existing: %d users (%d pelanggan) | %d customers',
            count($existingUsersByEmail),
            User::where('role', 'pelanggan')->count(),
            count($existingCustomersByTicket)
        ));

        // Loop customers legacy, urutkan by id biar konsisten
        // Filter via customer.business_id = bizFilter (kalau diset)
        $rows = DB::connection('legacy')->table('customers')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->get();
        $totalRows = count($rows);
        $this->info("Memproses {$totalRows} baris customer...");

        $stats = [
            'users_created' => 0,
            'users_updated' => 0,
            'customers_created' => 0,
            'customers_updated' => 0,
            'tickets_linked' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $skipped = [];
        $failed  = [];

        foreach ($rows as $i => $row) {
            try {
                $reason = null;
                $mapped = $this->mapRow($row, $reason);

                if ($mapped === null) {
                    $stats['skipped']++;
                    $skipped[] = ['legacy_cust_id' => $row->id, 'nama' => $row->nama ?? '-', 'reason' => $reason];
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                if ($isDryRun) {
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                // 1. Buat / ambil user
                $email = $mapped['email'];
                if (isset($existingUsersByEmail[$email])) {
                    $userId = $existingUsersByEmail[$email];
                    $stats['users_updated']++;
                } else {
                    // Pakai DB::table langsung (lebih cepat dari Eloquent)
                    $now = now();
                    $userId = DB::table('users')->insertGetId([
                        'name'       => $mapped['applicant_name'],
                        'email'      => $email,
                        'password'   => Hash::make('password'),
                        'role'       => 'pelanggan',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $existingUsersByEmail[$email] = $userId;
                    $stats['users_created']++;
                }

                // 2. Buat / update customers row
                $ticketId = $mapped['ticket_id'];
                $customerData = [
                    'ticket_id'   => $ticketId,
                    'user_id'     => $userId,
                    'customer_code' => $mapped['customer_code'],
                    'initial_meter_reading' => 0,
                    'meter_photo_url' => null,
                    'activated_at' => $mapped['activated_at'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                if (isset($existingCustomersByTicket[$ticketId])) {
                    // Update path: kalau customer_code bentrok dengan row lain, append suffix
                    $code = $mapped['customer_code'];
                    $existingCustId = $existingCustomersByTicket[$ticketId];
                    $currentCode = $existingCustomerCodeByTicket[$ticketId] ?? null;
                    // kalau kode baru sudah dipakai row LAIN (bukan row ini), kasih suffix
                    // cek dengan prefix-matching juga karena kode bisa punya suffix _cXXX dari run sebelumnya
                    if ($code !== $currentCode && $this->codeTaken($code, $existingCustomerCodes)) {
                        $code = $code.'_c'.$row->id;
                    }
                    $customerData['customer_code'] = $code;

                    DB::table('customers')
                        ->where('id', $existingCustId)
                        ->update($customerData);

                    if ($currentCode !== $code) {
                        unset($existingCustomerCodes[$currentCode]);
                    }
                    $existingCustomerCodes[$code] = true;
                    $existingCustomerCodeByTicket[$ticketId] = $code;

                    $stats['customers_updated']++;
                } else {
                    // Handle duplicate kode_instalasi (legacy punya customer_id berbeda untuk kode sama)
                    $code = $mapped['customer_code'];
                    if ($this->codeTaken($code, $existingCustomerCodes)) {
                        // Append legacy customer_id suffix supaya unique
                        $code = $code.'_c'.$row->id;
                    }
                    $customerData['customer_code'] = $code;
                    DB::table('customers')->insert($customerData);
                    $stats['customers_created']++;
                    $existingCustomerCodes[$code] = true;
                    $existingCustomerCodeByTicket[$ticketId] = $code;
                }
                $existingCustomersByTicket[$ticketId] = true;

                // 3. Update installation_tickets.user_id = userId (link tiket → user)
                // Hanya link kalau tiket belum punya user_id
                $updated = DB::table('installation_tickets')
                    ->where('id', $ticketId)
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId, 'updated_at' => now()]);
                if ($updated > 0) {
                    $stats['tickets_linked']++;
                }

                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $failed[] = ['legacy_cust_id' => $row->id, 'nama' => $row->nama ?? '-', 'error' => $e->getMessage()];
                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            }
        }

        $this->line('');
        $this->info('Ringkasan:');
        $this->line(sprintf('  users_created     : %d', $stats['users_created']));
        $this->line(sprintf('  users_updated     : %d', $stats['users_updated']));
        $this->line(sprintf('  customers_created : %d', $stats['customers_created']));
        $this->line(sprintf('  customers_updated : %d', $stats['customers_updated']));
        $this->line(sprintf('  tickets_linked    : %d', $stats['tickets_linked']));
        $this->line(sprintf('  dilewati          : %d', $stats['skipped']));
        $this->line(sprintf('  gagal             : %d', $stats['failed']));

        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Dilewati (semua):');
            foreach ($skipped as $s) {
                $this->line("  legacy_cust_id={$s['legacy_cust_id']} nama='{$s['nama']}' reason={$s['reason']}");
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Gagal (10 pertama):');
            foreach (array_slice($failed, 0, 10) as $f) {
                $this->line("  legacy_cust_id={$f['legacy_cust_id']} nama='{$f['nama']}' error={$f['error']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build map: legacy customer_id → installation_tickets.id (yang ber-status A, terlama)
     * Plus cache legacy_inst_id → new_ticket_id.
     */
    private function buildTicketMap(?string $bizFilter = null): void
    {
        // Step 1: untuk tiap customer legacy, ambil installation_id dengan status A yang MIN(id)
        $sql = "SELECT customer_id, MIN(id) AS inst_id
                FROM installations
                WHERE status = 'A'";
        $bindings = [];
        if ($bizFilter) {
            $sql .= " AND business_id = ?";
            $bindings[] = $bizFilter;
        }
        $sql .= " GROUP BY customer_id";
        $rows = DB::connection('legacy')->select($sql, $bindings);
        foreach ($rows as $r) {
            $this->ticketMap[(int) $r->customer_id] = (int) $r->inst_id;
        }

        // Step 2: untuk tiap legacy inst_id, lookup installations lengkap (untuk dapat package_id, desa, kode_instalasi, aktif)
        $legacyInstIds = array_values($this->ticketMap);
        if (empty($legacyInstIds)) {
            return;
        }

        $legacyInsts = DB::connection('legacy')
            ->table('installations')
            ->whereIn('id', $legacyInstIds)
            ->get(['id', 'kode_instalasi', 'package_id', 'desa', 'aktif']);

        // Step 3: pre-load semua new tickets index by (applicant_name|village_id|package_id)
        // untuk lookup cepat di loop. TicketMap akan berisi triple key → ticket baru.
        $newTickets = DB::table('installation_tickets')
            ->orderBy('id')
            ->get(['id', 'applicant_name', 'village_id', 'package_id']);

        // Group by triple, ambil yang paling lama per triple (untuk handle customer dgn multi-tiket)
        $byTriple = [];
        foreach ($newTickets as $nt) {
            $key = strtolower(trim($nt->applicant_name)).'|'.$nt->village_id.'|'.$nt->package_id;
            if (! isset($byTriple[$key])) {
                $byTriple[$key] = $nt->id;
            }
        }
        // fallback by name only
        $byName = [];
        foreach ($newTickets as $nt) {
            $key = strtolower(trim($nt->applicant_name));
            if (! isset($byName[$key])) {
                $byName[$key] = $nt->id;
            }
        }

        // Reverse: legacy inst_id → customer_id
        $instToCust = array_flip($this->ticketMap);

        // Bulk load customers (untuk dapat nama)
        $custIds = array_keys($this->ticketMap);
        $customers = DB::connection('legacy')->table('customers')->whereIn('id', $custIds)->get(['id', 'nama']);
        $custById = [];
        foreach ($customers as $c) {
            $custById[(int) $c->id] = $c;
        }

        foreach ($legacyInsts as $li) {
            $custId = $instToCust[$li->id] ?? null;
            if (! $custId) continue;
            $cust = $custById[$custId] ?? null;
            if (! $cust) continue;

            $name = strtolower(trim($cust->nama ?? ''));
            $key = $name.'|'.$li->desa.'|'.$li->package_id;

            if (isset($byTriple[$key])) {
                $this->newTicketByLegacyInstId[(int) $li->id] = $byTriple[$key];
            } elseif (isset($byName[$name])) {
                $this->newTicketByLegacyInstId[(int) $li->id] = $byName[$name];
            }
        }
    }

    /**
     * Cek apakah $code sudah dipakai (exact match atau prefix match untuk suffix _cXXX).
     */
    private function codeTaken(string $code, array $existingCodes): bool
    {
        if (isset($existingCodes[$code])) {
            return true;
        }
        // Prefix match: cari existing code yang dimulai dengan $code (handle suffix _cXXX)
        foreach (array_keys($existingCodes) as $existing) {
            if (str_starts_with($existing, $code.'_')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Map customer legacy → array siap insert.
     * Return null kalau skip.
     */
    private function mapRow(object $row, ?string &$reason): ?array
    {
        $name = trim((string) ($row->nama ?? ''));
        if ($name === '') {
            $reason = 'nama kosong';
            return null;
        }

        // 1. Cari installation_ticket yg sudah di-import
        if (! isset($this->ticketMap[(int) $row->id])) {
            $reason = 'customer tidak punya installations.status=A (orphan)';
            return null;
        }
        $legacyInstId = $this->ticketMap[(int) $row->id];

        if (! isset($this->newTicketByLegacyInstId[$legacyInstId])) {
            $reason = "tidak ada installation_ticket baru untuk legacy inst_id={$legacyInstId}";
            return null;
        }
        $newTicketId = $this->newTicketByLegacyInstId[$legacyInstId];

        $legacyInst = DB::connection('legacy')->table('installations')->where('id', $legacyInstId)->first();
        if (! $legacyInst) {
            $reason = "legacy installation_id={$legacyInstId} missing";
            return null;
        }

        // 2. Generate email unik dari nama + legacy_id
        $email = $this->makeUniqueEmail($name, (int) $row->id);

        // 3. Activated_at dari installations.aktif (legacy)
        $activatedAt = $this->parseDateTime($legacyInst->aktif ?? null);

        return [
            'applicant_name' => $name,
            'email'          => $email,
            'ticket_id'      => $newTicketId,
            'customer_code'  => (string) ($legacyInst->kode_instalasi ?? ''),
            'activated_at'   => $activatedAt,
        ];
    }

    private function makeUniqueEmail(string $name, int $legacyId): string
    {
        $local = strtolower($name);
        // Hanya alphanumeric
        $local = preg_replace('/[^a-z0-9]/', '', $local);
        if ($local === '') {
            $local = 'user'.$legacyId;
        }
        $candidate = $local.'@gmail.com';
        return $candidate;
        // Kalau duplicate dicek di caller (existingUsersByEmail skip + suffix manual)
    }

    private function parseDateTime(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $val = trim((string) $val);
        if ($val === '' || $val === '-' || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($val)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function maybeLog(int $current, int $chunkSize, int $total, array $stats): void
    {
        if ($current % $chunkSize === 0 || $current === $total) {
            $pct = round($current / max(1, $total) * 100, 1);
            $this->line(sprintf(
                '  [%5d/%5d] %5.1f%%  users_new=%d users_upd=%d cust_new=%d cust_upd=%d linked=%d skip=%d fail=%d',
                $current, $total, $pct,
                $stats['users_created'], $stats['users_updated'],
                $stats['customers_created'], $stats['customers_updated'],
                $stats['tickets_linked'], $stats['skipped'], $stats['failed']
            ));
        }
    }
}
