<?php

namespace App\Console\Commands;

use App\Services\LegacyTicketAligner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixLegacyOrphanTicketsCommand extends Command
{
    protected $signature = 'legacy:fix-orphan-tickets
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--fallback-package=1 : installation_packages.id untuk legacy package_id NULL}';

    protected $description = 'Buat installation_tickets + customers untuk legacy installations yang belum ada di DB baru (termasuk yang NULL di legacy)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $fallbackPkg = (int) $this->option('fallback-package');

        $this->info('=== Lengkapi tiket/pelanggan yang belum ada di DB baru (business_id='.$biz.') ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: data akan ditambah.');
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        $packageMap = LegacyTicketAligner::packageMap($L, $N);
        $align = LegacyTicketAligner::build($biz);
        $this->info(sprintf('Alignment existing: %d pasang | akurasi %.2f%%', count($align['map']), $align['acc']));
        $this->line('');

        // Legacy installations yang belum punya customers row di DB baru
        $existingCodes = $N->table('customers')->pluck('customer_code')
            ->map(fn ($c) => trim((string) $c))->all();
        $existingSet = array_flip($existingCodes);

        $legacy = $L->table('installations')
            ->where('business_id', $biz)
            ->orderBy('id')
            ->get([
                'id', 'kode_instalasi', 'package_id', 'desa', 'status', 'customer_id',
                'cater_id', 'alamat', 'rt', 'rw', 'order', 'pasang', 'aktif', 'koordinate',
            ]);

        $statusMap = ['A' => 'completed', 'R' => 'pending', 'B' => 'suspended', 'I' => 'terminated', 'C' => 'terminated'];

        // Legacy staff → user baru (cater/teknisi)
        $staffMap = [];
        $newStaff = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->pluck('id', 'name');
        foreach ($L->table('users')->where('business_id', $biz)->orderBy('id')->get() as $lu) {
            $name = trim((string) $lu->nama);
            if ($name === '') {
                continue;
            }
            $hit = $newStaff->first(fn ($v, $k) => strcasecmp(trim((string) $k), $name) === 0);
            if ($hit) {
                $staffMap[(int) $lu->id] = (int) $hit;
            }
        }
        $defaultStaff = (int) ($N->table('users')->where('role', 'admin')->orderBy('id')->value('id') ?? 1);
        $this->info('Staff terpetakan: '.count($staffMap).' | default created_by: '.$defaultStaff);
        $this->line('');

        $plan = [];
        $skip = [];

        foreach ($legacy as $i) {
            $kode = trim((string) $i->kode_instalasi);
            if ($kode === '' || isset($existingSet[$kode])) {
                continue;
            }

            // Customer: boleh ambil dari business lain (legacy installations
            // tidak selalu konsisten business_id-nya dengan customers-nya).
            $cust = $L->table('customers')->where('id', $i->customer_id)->first();
            if (! $cust) {
                $skip[] = "inst={$i->id} kode=$kode customer_id={$i->customer_id} tidak ada di legacy ANY business";

                continue;
            }

            $pkgId = $packageMap[(int) $i->package_id] ?? $fallbackPkg;
            $status = $statusMap[(string) $i->status] ?? 'pending';
            $staffId = $staffMap[(int) $i->cater_id] ?? $defaultStaff;

            $plan[] = [
                'legacy_inst_id' => (int) $i->id,
                'kode' => $kode,
                'legacy_customer_id' => (int) $i->customer_id,
                'legacy_cust_business' => (int) $cust->business_id,
                'name' => trim((string) $cust->nama),
                'nik' => $cust->nik,
                'jk' => $cust->jk,
                'tgl_lahir' => $cust->tgl_lahir,
                'tempat_lahir' => $cust->tempat_lahir,
                'hp' => $cust->hp,
                'cust_alamat' => $cust->alamat,
                'inst_alamat' => $i->alamat,
                'rt' => $i->rt,
                'rw' => $i->rw,
                'village_id' => (int) $i->desa,
                'package_id' => $pkgId,
                'status' => $status,
                'order_date' => $i->order,
                'aktif' => $i->aktif,
                'staff_id' => $staffId,
                'cater_id' => (int) $i->cater_id,
                'legacy_pkg_raw' => $i->package_id === null ? 'NULL' : (string) $i->package_id,
            ];
        }

        $this->info('Rencana: '.count($plan)." tiket + customers baru\n");
        foreach ($plan as $p) {
            $flags = [];
            if ($p['legacy_pkg_raw'] === 'NULL') {
                $flags[] = 'package NULL->'.$p['package_id'];
            }
            if ($p['legacy_cust_business'] !== $biz) {
                $flags[] = 'customer business='.$p['legacy_cust_business'];
            }
            if ($p['nik'] === null || trim((string) $p['nik']) === '') {
                $flags[] = 'nik NULL';
            }
            if ($p['hp'] === null || trim((string) $p['hp']) === '' || trim((string) $p['hp']) === '-') {
                $flags[] = 'hp kosong';
            }
            $this->line(sprintf(
                '  kode=%-18s %-30s v=%-4d pkg=%-2d status=%-10s staff=%-4d %s',
                $p['kode'], $p['name'], $p['village_id'], $p['package_id'], $p['status'], $p['staff_id'],
                $flags ? '['.implode(' | ', $flags).']' : ''
            ));
        }

        if ($skip) {
            $this->line('');
            $this->warn('Dilewati ('.count($skip).'):');
            foreach ($skip as $s) {
                $this->line('   '.$s);
            }
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->error('Menerapkan...');
        $applied = ['tickets' => 0, 'users' => 0, 'users_reused' => 0, 'customers' => 0, 'failed' => 0];

        $N->transaction(function () use ($N, $plan, &$applied) {
            $usedEmails = $N->table('users')->pluck('id', 'email')->all();

            // Kandidat user yang bisa dipakai ulang: user role=pelanggan
            // dengan nama sama yang belum dipakai customers manapun.
            $usedUserIds = $N->table('customers')->pluck('user_id')->unique()->filter()
                ->map(fn ($x) => (int) $x)->all();
            $reusable = [];
            foreach ($N->table('users')->where('role', 'pelanggan')->orderBy('id')->get() as $u) {
                if (in_array((int) $u->id, $usedUserIds, true)) {
                    continue;
                }
                $reusable[strtolower(trim((string) $u->name))][] = (int) $u->id;
            }

            foreach ($plan as $p) {
                try {
                    // 1. User pelanggan — reuse kalau ada yang cocok & belum terpakai.
                    $nameKey = strtolower(trim($p['name']));
                    $userId = null;
                    if (! empty($reusable[$nameKey])) {
                        $userId = array_shift($reusable[$nameKey]);
                        $applied['users_reused']++;
                    } else {
                        $email = self::uniqueEmail($N, $p['name'], $p['legacy_customer_id'], $usedEmails);
                        $userId = $N->table('users')->insertGetId([
                            'name' => $p['name'],
                            'email' => $email,
                            'password' => Hash::make('password'),
                            'role' => 'pelanggan',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $usedEmails[$email] = true;
                        $applied['users']++;
                    }

                    // 2. Ticket
                    $ticketId = $N->table('installation_tickets')->insertGetId([
                        'package_id' => $p['package_id'],
                        'user_id' => $p['staff_id'],
                        'applicant_name' => $p['name'],
                        'order_date' => self::parseDate($p['order_date']),
                        'nik' => self::cleanNik($p['nik']),
                        'rt' => self::cleanStr($p['rt']),
                        'rw' => self::cleanStr($p['rw']),
                        'phone' => self::cleanPhone($p['hp']),
                        'gender' => match (strtoupper((string) $p['jk'])) {
                            'L' => 'male', 'P' => 'female', default => null,
                        },
                        'birth_place' => self::cleanStr($p['tempat_lahir']),
                        'birth_date' => self::parseDate($p['tgl_lahir']),
                        'address' => self::buildAddress($p['cust_alamat'], $p['inst_alamat']),
                        'village_id' => $p['village_id'],
                        'lat' => 0,
                        'lng' => 0,
                        'status' => $p['status'],
                        'created_by' => $p['staff_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $applied['tickets']++;

                    // 3. Customer
                    $N->table('customers')->insert([
                        'ticket_id' => $ticketId,
                        'user_id' => $userId,
                        'customer_code' => $p['kode'],
                        'initial_meter_reading' => 0,
                        'meter_photo_url' => null,
                        'activated_at' => self::parseDateTime($p['aktif']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $applied['customers']++;
                } catch (\Throwable $e) {
                    $applied['failed']++;
                    $this->warn('   Gagal '.$p['kode'].' '.$p['name'].': '.$e->getMessage());
                }
            }
        });

        $this->line('');
        $this->info('Selesai:');
        $this->line('   tiket dibuat     : '.$applied['tickets']);
        $this->line('   user dibuat      : '.$applied['users']);
        $this->line('   user dipakai ulang: '.$applied['users_reused']);
        $this->line('   customers dibuat : '.$applied['customers']);
        if ($applied['failed']) {
            $this->error('   gagal            : '.$applied['failed']);
        }

        return self::SUCCESS;
    }

    private static function uniqueEmail($N, string $name, int $legacyId, array &$used): string
    {
        $base = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        if ($base === '') {
            $base = 'pelanggan'.$legacyId;
        }
        $email = $base.'@gmail.com';
        if (! isset($used[$email]) && ! $N->table('users')->where('email', $email)->exists()) {
            return $email;
        }
        $n = 1;
        do {
            $email = $base.'_'.$legacyId.'_'.$n.'@gmail.com';
            $n++;
        } while (isset($used[$email]) || $N->table('users')->where('email', $email)->exists());

        return $email;
    }

    private static function cleanStr($v): ?string
    {
        $s = trim((string) $v);

        return ($s === '' || $s === '-') ? null : $s;
    }

    private static function cleanNik($v): ?string
    {
        $s = trim((string) $v);
        if ($s === '' || $s === '-' || $s === '0' || $s === '--' || str_starts_with($s, '0000')) {
            return null;
        }
        if (! preg_match('/^\d{10,20}$/', $s)) {
            return null;
        }

        return $s;
    }

    private static function cleanPhone($v): ?string
    {
        $s = trim((string) $v);
        if ($s === '' || $s === '-' || $s === '0') {
            return null;
        }
        $d = preg_replace('/\D+/', '', $s);
        if ($d === '' || $d === '0') {
            return null;
        }

        return strlen($d) > 20 ? substr($d, -20) : $d;
    }

    private static function buildAddress($custAlamat, $instAlamat): string
    {
        foreach ([$custAlamat, $instAlamat] as $src) {
            $s = trim((string) $src);
            if ($s !== '' && $s !== '-') {
                return $s;
            }
        }

        return '-';
    }

    private static function parseDate($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '' || $s === '-' || str_starts_with($s, '0000-00-00')) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($s)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseDateTime($v): ?string
    {
        $d = self::parseDate($v);

        return $d === null ? null : $d.' 00:00:00';
    }
}