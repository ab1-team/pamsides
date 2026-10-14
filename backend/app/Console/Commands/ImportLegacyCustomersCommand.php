<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use App\Support\LegacyIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Import pelanggan legacy → users (role=pelanggan) + customers row.
 *
 * PERBAIKAN PENTING (identitas):
 *   Dulu iterasi per baris `customers` legacy dan memetakan ke SATU instalasi
 *   aktif (MIN(id)), lalu menautkan ke tiket baru dengan heuristik
 *   (nama|desa|paket) + fallback NAMA SAJA. Akibatnya:
 *     - pelanggan dengan >1 instalasi aktif kehilangan instalasi lain;
 *     - semua pelanggan bernama sama dipetakan ke tiket pertama yang namanya
 *       sama (3 "Sunarto" -> 1 baris);
 *     - email user = slug(nama) sehingga pelanggan berbeda berbagi 1 akun
 *       (~191 tabrakan user).
 *
 *   Sekarang iterasi per INSTALASI legazy status='A' (satu instalasi = satu
 *   baris customers), tautkan ke tiket baru lewat
 *   `installation_tickets.legacy_installation_id` (pasti), dan email user
 *   unik per pelanggan legacy (slug(nama) + customer_id legacy).
 */
class ImportLegacyCustomersCommand extends Command
{
    protected $signature = 'import:customers
                            {--dry-run : Cuma simulasi, tidak insert}
                            {--force   : Jalankan insert/update beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=200 : Ukuran chunk progress logging}';

    protected $description = 'Import pelanggan legacy (per instalasi aktif) → users + customers row';

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

        // Legenda idempotensi: legacy_installation_id -> ticket baru.
        $ticketIdByLegacyInst = $this->buildTicketMap();
        $this->info('Tiket baru dengan legacy_installation_id: '.count($ticketIdByLegacyInst));

        // Pre-load existing users & customers (sekali, tanpa query per-baris).
        $existingUsersByEmail = User::where('role', 'pelanggan')->pluck('id', 'email')->all();
        $existingCustomersByTicket = Customer::pluck('id', 'ticket_id')->all();
        $existingCustomerCodes = array_flip(Customer::pluck('customer_code')->all());
        $this->info(sprintf(
            'Existing: %d users pelanggan | %d customers',
            count($existingUsersByEmail),
            count($existingCustomersByTicket)
        ));

        // Iterasi PER INSTALASI legacy status='A' (bukan per baris customers).
        $insts = DB::connection('legacy')
            ->table('installations AS i')
            ->join('customers AS c', 'c.id', '=', 'i.customer_id')
            ->when($bizFilter, fn ($q) => $q->where('i.business_id', $bizFilter))
            ->where('i.status', 'A')
            ->orderBy('i.id')
            ->select([
                'i.id AS inst_id', 'i.kode_instalasi', 'i.aktif',
                'i.customer_id', 'i.business_id',
                'c.nama', 'c.id AS legacy_cust_id',
            ])
            ->get();

        $totalRows = count($insts);
        $this->info("Memproses {$totalRows} instalasi legacy status='A'...");

        $stats = [
            'users_created' => 0,
            'users_reused' => 0,
            'customers_created' => 0,
            'customers_updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $skipped = [];
        $failed = [];

        // Password default seragam ('password') -> hash bcrypt SEKALI saja.
        $defaultPasswordHash = Hash::make('password');
        $startedAt = microtime(true);

        foreach ($insts as $i => $inst) {
            try {
                $reason = null;
                $mapped = $this->mapInstallation($inst, $ticketIdByLegacyInst, $reason);

                if ($mapped === null) {
                    $stats['skipped']++;
                    $skipped[] = [
                        'legacy_inst_id' => $inst->inst_id,
                        'kode' => $inst->kode_instalasi,
                        'nama' => $inst->nama ?? '-',
                        'reason' => $reason,
                    ];
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                if ($isDryRun) {
                    // Tetap hitung jumlah "akan dibuat/update" tanpa menulis.
                    if (isset($existingUsersByEmail[$mapped['email']])) {
                        $stats['users_reused']++;
                    } else {
                        $stats['users_created']++;
                    }
                    if (isset($existingCustomersByTicket[$mapped['ticket_id']])) {
                        $stats['customers_updated']++;
                    } else {
                        $stats['customers_created']++;
                    }
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
                    continue;
                }

                // 1. User: email unik per pelanggan legacy (wajib unik).
                $email = $mapped['email'];
                if (isset($existingUsersByEmail[$email])) {
                    $userId = $existingUsersByEmail[$email];
                    $stats['users_reused']++;
                } else {
                    $now = now();
                    $userId = DB::table('users')->insertGetId([
                        'name'       => $mapped['applicant_name'],
                        'email'      => $email,
                        'password'   => $defaultPasswordHash,
                        'role'       => 'pelanggan',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $existingUsersByEmail[$email] = $userId;
                    $stats['users_created']++;
                }

                // 2. customers row (kunci: satu tiket = satu baris pelanggan).
                $ticketId = $mapped['ticket_id'];
                $code = $mapped['customer_code'];
                if ($code !== '' && $this->codeTaken($code, $existingCustomerCodes)) {
                    $code = $code.'_c'.$inst->legacy_cust_id;
                }

                $customerData = [
                    'ticket_id'   => $ticketId,
                    'user_id'     => $userId,
                    'customer_code' => $code,
                    'initial_meter_reading' => 0,
                    'meter_photo_url' => null,
                    'activated_at' => $mapped['activated_at'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];

                if (isset($existingCustomersByTicket[$ticketId])) {
                    DB::table('customers')->where('id', $existingCustomersByTicket[$ticketId])->update($customerData);
                    $stats['customers_updated']++;
                } else {
                    $id = DB::table('customers')->insertGetId($customerData);
                    $existingCustomersByTicket[$ticketId] = $id;
                    $stats['customers_created']++;
                }
                if ($code !== '') {
                    $existingCustomerCodes[$code] = true;
                }

                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $failed[] = ['legacy_inst_id' => $inst->inst_id, 'kode' => $inst->kode_instalasi, 'error' => $e->getMessage()];
                $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);
            }
        }

        $elapsed = microtime(true) - $startedAt;

        // Pelanggan legacy yang TIDAK punya instalasi aktif (tidak diproses).
        $orphanCustomers = $this->countOrphanCustomers($bizFilter);

        $this->line('');
        $this->info($isDryRun ? 'Ringkasan (DRY-RUN, estimasi):' : 'Ringkasan:');
        $this->line(sprintf('  total instalasi A      : %d', $totalRows));
        $this->line(sprintf('  customers akan dibuat  : %d', $stats['customers_created']));
        $this->line(sprintf('  customers akan update  : %d', $stats['customers_updated']));
        $this->line(sprintf('  users baru             : %d', $stats['users_created']));
        $this->line(sprintf('  users sudah ada (pakai): %d', $stats['users_reused']));
        $this->line(sprintf('  di-skip                : %d', $stats['skipped']));
        $this->line(sprintf('  gagal                  : %d', $stats['failed']));
        $this->line(sprintf('  pelanggan tanpa instalasi A (orphan) : %d', $orphanCustomers));
        $this->line(sprintf('  durasi                 : %.1f s', $elapsed));

        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Dilewati (semua) + alasan:');
            foreach ($skipped as $s) {
                $this->line("  inst_id={$s['legacy_inst_id']} kode={$s['kode']} nama='{$s['nama']}' reason={$s['reason']}");
            }
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Gagal (10 pertama):');
            foreach (array_slice($failed, 0, 10) as $f) {
                $this->line("  inst_id={$f['legacy_inst_id']} kode={$f['kode']} error={$f['error']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Build map: legacy installation.id → installation_tickets.id baru.
     *
     * Sumber PASTI: `installation_tickets.legacy_installation_id`. Tidak ada lagi
     * heuristik nama/desa/paket. Fallback `customers.customer_code` ↔
     * `kode_instalasi` dipakai HANYA untuk tiket lama yang belum punya legacy id
     * (hasil migrasi sebelum kolom ini ada) -- lihat
     * `import:backfill-legacy-installation-id`.
     *
     * @return array<int,int> legacy_inst_id => ticket_id
     */
    private function buildTicketMap(): array
    {
        $map = [];
        foreach (DB::table('installation_tickets')
            ->whereNotNull('legacy_installation_id')
            ->orderBy('id')
            ->get(['id', 'legacy_installation_id']) as $t) {
            $legacy = (int) $t->legacy_installation_id;
            if (! isset($map[$legacy])) {
                $map[$legacy] = (int) $t->id;
            }
        }

        // Fallback: tiket belum punya legacy_installation_id -> cocokkan via
        // customers.customer_code yang sama dengan kode_instalasi legacy.
        $instIdByKode = $this->legacyInstIdByKode();
        if (! empty($instIdByKode)) {
            $rows = DB::table('installation_tickets AS t')
                ->join('customers AS c', 'c.ticket_id', '=', 't.id')
                ->whereNull('t.legacy_installation_id')
                ->get(['t.id', 'c.customer_code']);
            foreach ($rows as $r) {
                $code = trim((string) ($r->customer_code ?? ''));
                if (isset($instIdByKode[$code])) {
                    $legacyInstId = $instIdByKode[$code];
                    if (! isset($map[$legacyInstId])) {
                        $map[$legacyInstId] = (int) $r->id;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string,int> kode_instalasi (unik) => legacy installation.id
     */
    private function legacyInstIdByKode(): array
    {
        $out = [];
        $dupe = [];
        foreach (DB::connection('legacy')->table('installations')
            ->where('status', 'A')
            ->orderBy('id')
            ->get(['id', 'kode_instalasi']) as $inst) {
            $kode = trim((string) $inst->kode_instalasi);
            if ($kode === '' || $kode === '0') {
                continue;
            }
            if (isset($out[$kode])) {
                $dupe[$kode] = true;
                unset($out[$kode]);
                continue;
            }
            if (! isset($dupe[$kode])) {
                $out[$kode] = (int) $inst->id;
            }
        }

        return $out;
    }

    /**
     * Jumlah pelanggan legacy (business filter) yang tidak punya instalasi aktif.
     */
    private function countOrphanCustomers(?string $bizFilter): int
    {
        $q = DB::connection('legacy')->table('customers AS c')
            ->leftJoin('installations AS i', function ($join) {
                $join->on('i.customer_id', '=', 'c.id')->where('i.status', '=', 'A');
            })
            ->whereNull('i.id');
        if ($bizFilter) {
            $q->where('c.business_id', $bizFilter);
        }

        return (int) $q->count();
    }

    /**
     * Map satu instalasi legacy → array siap insert. Null kalau skip.
     *
     * @param  array<int,int>  $ticketIdByLegacyInst
     * @return array{applicant_name:string,email:string,ticket_id:int,customer_code:string,activated_at:?string}|null
     */
    private function mapInstallation(object $inst, array $ticketIdByLegacyInst, ?string &$reason): ?array
    {
        $name = trim((string) ($inst->nama ?? ''));
        if ($name === '') {
            $reason = 'nama kosong';

            return null;
        }

        $legacyInstId = (int) $inst->inst_id;
        if (! isset($ticketIdByLegacyInst[$legacyInstId])) {
            $reason = "tidak ada installation_ticket dengan legacy_installation_id={$legacyInstId}";

            return null;
        }

        return [
            'applicant_name' => $name,
            'email'          => LegacyIdentity::customerEmail($name, (int) $inst->legacy_cust_id),
            'ticket_id'      => $ticketIdByLegacyInst[$legacyInstId],
            'customer_code'  => trim((string) ($inst->kode_instalasi ?? '')),
            'activated_at'   => $this->parseDateTime($inst->aktif ?? null),
        ];
    }

    /**
     * Cek apakah $code sudah dipakai (exact atau prefix _cXXX dari run lama).
     */
    private function codeTaken(string $code, array $existingCodes): bool
    {
        if (isset($existingCodes[$code])) {
            return true;
        }
        foreach (array_keys($existingCodes) as $existing) {
            if (str_starts_with($existing, $code.'_')) {
                return true;
            }
        }

        return false;
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
                '  [%5d/%5d] %5.1f%%  users_new=%d users_reuse=%d cust_new=%d cust_upd=%d skip=%d fail=%d',
                $current, $total, $pct,
                $stats['users_created'], $stats['users_reused'],
                $stats['customers_created'], $stats['customers_updated'],
                $stats['skipped'], $stats['failed']
            ));
        }
    }
}
