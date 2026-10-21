<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use App\Support\LegacyIdentity;
use Carbon\Carbon;
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
 *   Sekarang iterasi per INSTALASI legazy SEMUA STATUS (satu instalasi = satu
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

    protected $description = 'Import pelanggan legacy (per instalasi, semua status) → users + customers row';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
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
        // Sumber kebenaran identitas pelanggan existing = baris `customers`
        // (kunci ticket_id), BUKAN email. Lihat komentar di resolveUserId().
        $existingCustomerRows = Customer::query()
            ->get(['id', 'ticket_id', 'user_id'])
            ->keyBy('ticket_id')
            ->all();
        $existingCustomersByTicket = Customer::pluck('id', 'ticket_id')->all();
        $existingCustomerCodes = array_flip(Customer::pluck('customer_code')->all());
        $this->info(sprintf(
            'Existing: %d users pelanggan | %d customers',
            count($existingUsersByEmail),
            count($existingCustomersByTicket)
        ));

        // Iterasi PER INSTALASI legacy, TANPA filter status ('A' dulu membuang
        // 32 instalasi non-A yang tetap punya tiket -- R/C/I/B -- sehingga
        // tiketnya tak pernah punya baris `customers` dan tagihannya hilang.
        // Cakupan ini disamakan dengan import:monthly-bills.
        $insts = DB::connection('legacy')
            ->table('installations AS i')
            ->join('customers AS c', 'c.id', '=', 'i.customer_id')
            ->when($bizFilter, fn ($q) => $q->where('i.business_id', $bizFilter))
            ->orderBy('i.id')
            ->select([
                'i.id AS inst_id', 'i.kode_instalasi', 'i.aktif',
                'i.customer_id', 'i.business_id',
                'c.nama', 'c.id AS legacy_cust_id',
            ])
            ->get();

        $totalRows = count($insts);
        $this->info("Memproses {$totalRows} instalasi legacy (semua status)...");

        $stats = [
            'users_created' => 0,
            'users_reused' => 0,
            'customers_created' => 0,
            'customers_updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'existing_without_user' => 0,
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
                    // Tetap hitung jumlah "akan dibuat/update" tanpa menulis,
                    // memakai ATURAN YANG SAMA dengan jalur --force (baris
                    // customers existing = sumber kebenaran user_id), supaya
                    // estimasi dry-run tidak melebih-lebihkan users baru.
                    $ticketId = $mapped['ticket_id'];
                    $existingRow = $existingCustomerRows[$ticketId] ?? null;
                    [$existingUserId, $isExistingIdentity] = $this->resolveCustomerUserId($existingRow);

                    if ($isExistingIdentity) {
                        $stats['users_reused']++;
                    } else {
                        if ($existingRow !== null) {
                            $stats['existing_without_user']++;
                        }
                        // Pelanggan baru: email pola LegacyIdentity (unik), simulasi
                        // tambahkan ke map supaya tiket/instalasi berikutnya reuse.
                        if (isset($existingUsersByEmail[$mapped['email']])) {
                            $stats['users_reused']++;
                        } else {
                            $existingUsersByEmail[$mapped['email']] = -1;
                            $stats['users_created']++;
                        }
                    }

                    if ($existingRow !== null) {
                        $stats['customers_updated']++;
                    } else {
                        // Catat simulasi baris -> sisa instalasi pada tiket yang
                        // sama dihitung "update", bukan "create" ganda.
                        $existingCustomerRows[$ticketId] = (object) [
                            'id' => -1,
                            'ticket_id' => $ticketId,
                            'user_id' => $existingUserId ?? -1,
                        ];
                        $stats['customers_created']++;
                    }
                    $this->maybeLog($i + 1, $chunkSize, $totalRows, $stats);

                    continue;
                }

                // 1. User: TIDAK boleh mengganti identitas pelanggan existing.
                //
                //    Pelanggan yang sudah punya baris `customers` (kunci
                //    ticket_id) memakai user_id dari baris itu apa adanya.
                //    Dulu lookup SELALU lewat email pola baru
                //    (slug(nama)+id), sehingga 1.388 user lama berpola
                //    <nama>@gmail.com tidak ketemu -> dibuat user BARU dan
                //    user_id pelanggan dipindah ke akun baru (akun lama
                //    yatim, email yang dikenal pelanggan tak bisa login).
                //    User baru HANYA dibuat untuk pelanggan baru, atau untuk
                //    baris customers existing yang user_id-nya NULL (jarang).
                $ticketId = $mapped['ticket_id'];
                $existingRow = $existingCustomerRows[$ticketId] ?? null;
                [$existingCustomerUserId, $isExistingIdentity] = $this->resolveCustomerUserId($existingRow);

                if ($isExistingIdentity) {
                    // Existing + sudah punya user: pakai, jangan sentuh.
                    $userId = $existingCustomerUserId;
                    $stats['users_reused']++;

                    $userEmail = DB::table('users')->where('id', $userId)->value('email');
                    if (is_string($userEmail) && $userEmail !== '') {
                        $existingUsersByEmail[$userEmail] = $userId;
                    }
                } else {
                    if ($existingRow !== null) {
                        // Existing tapi user_id NULL: satu-satunya kasus existing
                        // yang boleh memicu pembuatan user.
                        $stats['existing_without_user']++;
                    }

                    // Email unik per pelanggan legacy (pola baru, jaminan unik).
                    $email = $mapped['email'];
                    if (isset($existingUsersByEmail[$email])) {
                        $userId = $existingUsersByEmail[$email];
                        $stats['users_reused']++;
                    } else {
                        $now = now();
                        $userId = DB::table('users')->insertGetId([
                            'name' => $mapped['applicant_name'],
                            'email' => $email,
                            'password' => $defaultPasswordHash,
                            'must_change_password' => true,
                            'role' => 'pelanggan',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $existingUsersByEmail[$email] = $userId;
                        $stats['users_created']++;
                    }
                }

                // 2. customers row (kunci: satu tiket = satu baris pelanggan).
                $code = $mapped['customer_code'];

                // Baris customers target (kalau ada) = baris yang SEDANG di-update.
                $existingCustomerId = $existingCustomersByTicket[$ticketId] ?? null;
                $codeOwnedByTarget = $existingCustomerId !== null
                    && $code !== ''
                    && (string) DB::table('customers')->where('id', $existingCustomerId)->value('customer_code') === $code;

                // Suffix HANYA kalau kode benar-benar dipakai baris customers LAIN
                // (atau baris BARU). Saat impor ulang, kode baris sendiri JANGAN disentuh
                // -- dulu dianggap bentrok dengan dirinya sendiri -> '1.01.0002_c8805'.
                if (! $codeOwnedByTarget && $code !== '' && $this->codeTaken($code, $existingCustomerCodes)) {
                    $code = $code.'_c'.$inst->legacy_cust_id;
                }

                $customerData = [
                    'ticket_id' => $ticketId,
                    'user_id' => $userId,
                    'customer_code' => $code,
                    'initial_meter_reading' => 0,
                    'meter_photo_url' => null,
                    'activated_at' => $mapped['activated_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (isset($existingCustomersByTicket[$ticketId])) {
                    $customerId = $existingCustomersByTicket[$ticketId];
                    // Jangan pernah memindahkan user_id pelanggan existing
                    // (kecuali memang NULL -- lihat resolve di atas).
                    if ($existingCustomerUserId !== null) {
                        unset($customerData['user_id']);
                    }
                    DB::table('customers')->where('id', $customerId)->update($customerData);
                    $existingCustomerRows[$ticketId] = (object) [
                        'id' => $customerId,
                        'ticket_id' => $ticketId,
                        'user_id' => $existingCustomerUserId ?? $userId,
                    ];
                    $stats['customers_updated']++;
                } else {
                    $id = DB::table('customers')->insertGetId($customerData);
                    $existingCustomersByTicket[$ticketId] = $id;
                    // Catat di map in-memory supaya pasangan tiket yang SAMA
                    // (mis. >1 instalasi legacy -> 1 tiket) tidak membuat user
                    // kedua -- dan estimasi dry-run tetap jujur.
                    $existingCustomerRows[$ticketId] = (object) [
                        'id' => $id,
                        'ticket_id' => $ticketId,
                        'user_id' => $userId,
                    ];
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
        $this->line(sprintf('  total instalasi        : %d', $totalRows));
        $this->line(sprintf('  customers akan dibuat  : %d', $stats['customers_created']));
        $this->line(sprintf('  customers akan update  : %d', $stats['customers_updated']));
        $this->line(sprintf('  users baru             : %d', $stats['users_created']));
        $this->line(sprintf('  users sudah ada (pakai): %d', $stats['users_reused']));
        $this->line(sprintf('  existing tanpa user_id (dibuatkan user): %d', $stats['existing_without_user']));
        $this->line(sprintf('  di-skip                : %d', $stats['skipped']));
        $this->line(sprintf('  gagal                  : %d', $stats['failed']));
        $this->line(sprintf('  pelanggan tanpa instalasi (orphan)   : %d', $orphanCustomers));
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
     * Semua status ikut: tiket non-A bisa saja belum punya legacy id dan hanya
     * bisa dikenali lewat kodenya.
     *
     * @return array<string,int> kode_instalasi (unik) => legacy installation.id
     */
    private function legacyInstIdByKode(): array
    {
        $out = [];
        $dupe = [];
        foreach (DB::connection('legacy')->table('installations')
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
     * Jumlah pelanggan legacy (business filter) yang tidak punya instalasi sama sekali.
     */
    private function countOrphanCustomers(?string $bizFilter): int
    {
        $q = DB::connection('legacy')->table('customers AS c')
            ->leftJoin('installations AS i', function ($join) {
                $join->on('i.customer_id', '=', 'c.id');
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
            'email' => LegacyIdentity::customerEmail($name, (int) $inst->legacy_cust_id),
            'ticket_id' => $ticketIdByLegacyInst[$legacyInstId],
            'customer_code' => trim((string) ($inst->kode_instalasi ?? '')),
            'activated_at' => $this->parseDateTime($inst->aktif ?? null),
        ];
    }

    /**
     * Tentukan user_id untuk satu baris customers.
     *
     * Aturan: baris `customers` existing (kunci ticket_id) yang SUDAH punya
     * user_id adalah sumber kebenaran -- user_id itu dipakai APA ADANYA
     * (tanpa cek email), sehingga email pola lama `<nama>@gmail.com` tidak
     * memicu user baru dan user_id pelanggan tak pernah dipindah.
     *
     * @return array{0:?int,1:bool} [user_id, true bila dari baris existing yang ber-user]
     */
    private function resolveCustomerUserId(?object $existingRow): array
    {
        if ($existingRow !== null && $existingRow->user_id !== null) {
            return [(int) $existingRow->user_id, true];
        }

        // Baris existing tanpa user (jarang) atau pelanggan baru -> pembuatan
        // user lewat email pola LegacyIdentity (dihitung pemanggil).
        return [null, false];
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
            return Carbon::parse($val)->toDateTimeString();
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
