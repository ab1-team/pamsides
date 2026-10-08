<?php

namespace App\Console\Commands;

use App\Services\LegacyTicketAligner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixLegacyCustomersCommand extends Command
{
    protected $signature = 'legacy:fix-customers
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--min-accuracy=99 : batalkan kalau akurasi alignment di bawah ini}';

    protected $description = 'Selaraskan customers DB baru dengan legacy: buat customers+user yang hilang, perbaiki customer_code & user_id yang tidak sesuai';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $minAcc = (float) $this->option('min-accuracy');

        $this->info('=== Selaraskan customers dengan legacy (business_id='.$biz.') ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: data akan diubah.');
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // 1. Mapping installations -> tickets
        $align = LegacyTicketAligner::build($biz);
        $map = $align['map'];
        $this->info(sprintf(
            'Alignment: %d pasang | akurasi %.2f%% (anchor %d/%d)',
            count($map),
            $align['acc'],
            $align['anchors'],
            $align['total']
        ));
        if ($align['acc'] < $minAcc) {
            $this->error(sprintf('Akurasi %.2f%% < batas %.2f%%. DIBATALKAN.', $align['acc'], $minAcc));

            return self::FAILURE;
        }

        $packageMap = LegacyTicketAligner::packageMap($L, $N);

        // 2. Kumpulkan baris legacy yang relevan
        $legacy = $L->table('installations')
            ->where('business_id', $biz)
            ->orderBy('id')
            ->get(['id', 'kode_instalasi', 'package_id', 'desa', 'status', 'customer_id', 'aktif']);
        $legacyCustomers = $L->table('customers')
            ->where('business_id', $biz)
            ->get(['id', 'nama', 'nik', 'jk', 'hp', 'tgl_lahir'])
            ->keyBy('id');

        $statusMap = ['A' => 'completed', 'R' => 'pending', 'B' => 'suspended', 'I' => 'terminated', 'C' => 'terminated'];

        // 3. Data DB baru
        $existingCustomers = $N->table('customers')->get(['id', 'ticket_id', 'user_id', 'customer_code'])->keyBy('ticket_id');
        $takenTickets = $existingCustomers->keys()->map(fn ($x) => (int) $x)->all();
        $usedTickets = array_fill_keys($takenTickets, true);
        $codeByCustomer = [];
        foreach ($N->table('customers')->get(['id', 'customer_code']) as $c) {
            $ck = trim((string) $c->customer_code);
            if ($ck !== '') {
                $codeByCustomer[$ck] = (int) $c->id;
            }
        }

        $usersByEmail = $N->table('users')->pluck('id', 'email')->all();
        $maxUserId = (int) ($N->table('users')->max('id') ?? 0);

        // Kode legacy unik per business, jadi sebuah kode pasti milik satu
        // instalasi. Bangun dulu "kode -> legacy_inst" supaya bentrok antara
        // customers yang sudah ada bisa diselesaikan (tukar possesses).
        $codeOwner = [];
        foreach ($legacy as $i) {
            $k = trim((string) $i->kode_instalasi);
            if ($k !== '') {
                $codeOwner[$k] = (int) $i->id;
            }
        }

        $plan = ['create' => [], 'fix_user' => [], 'fix_code' => [], 'fix_ticket' => []];
        $skip = [];

        foreach ($legacy as $i) {
            $ticketId = $map[(int) $i->id] ?? null;
            $cust = $legacyCustomers->get((int) $i->customer_id);
            $kode = trim((string) $i->kode_instalasi);

            if ($ticketId === null) {
                $skip[] = "inst={$i->id} kode=$kode tidak ada ticket pasangannya";

                continue;
            }
            if (! $cust || trim((string) $cust->nama) === '') {
                $skip[] = "inst={$i->id} kode=$kode customer_id={$i->customer_id} tidak ada / nama kosong";

                continue;
            }

            $name = trim((string) $cust->nama);
            $expStatus = $statusMap[(string) $i->status] ?? null;
            $expPkg = $packageMap[(int) $i->package_id] ?? null;

            // Perbaikan data tiket (package/status)
            $ticketRow = $N->table('installation_tickets')->where('id', $ticketId)->first();
            if ($ticketRow) {
                $tdiff = [];
                if ($expPkg !== null && (int) $ticketRow->package_id !== $expPkg) {
                    $tdiff['package_id'] = [(int) $ticketRow->package_id, $expPkg];
                }
                if ($expStatus !== null && (string) $ticketRow->status !== $expStatus) {
                    $tdiff['status'] = [(string) $ticketRow->status, $expStatus];
                }
                if ($tdiff) {
                    $plan['fix_ticket'][] = ['ticket_id' => $ticketId, 'kode' => $kode, 'nama' => $name, 'diff' => $tdiff];
                }
            }

            $existing = $existingCustomers->get($ticketId);
            if (! $existing) {
                // Pelanggan ini belum punya customers row.
                if ($kode !== '' && isset($codeByCustomer[$kode])) {
                    $skip[] = "inst={$i->id} kode=$kode sudah dipakai customer lain, tidak dibuat baru (nama '{$name}')";

                    continue;
                }
                $plan['create'][] = [
                    'legacy_inst_id' => (int) $i->id,
                    'ticket_id' => $ticketId,
                    'legacy_customer_id' => (int) $i->customer_id,
                    'name' => $name,
                    'kode' => $kode,
                    'status' => $expStatus,
                    'activated_at' => $i->aktif,
                ];
                $usedTickets[$ticketId] = true;
                if ($kode !== '') {
                    $codeByCustomer[$kode] = $ticketId;
                }
            } else {
                // customers sudah ada: cek user_id & customer_code
                if ($existing->user_id === null) {
                    $plan['fix_user'][] = ['customer_id' => (int) $existing->id, 'name' => $name, 'reason' => 'user_id null'];
                }
                if (trim((string) $existing->customer_code) !== $kode && $kode !== '') {
                    $holder = $codeByCustomer[$kode] ?? null;
                    if ($holder !== null && $holder !== (int) $existing->id) {
                        // Kode ini dipegang customers lain. Karena kode legacy
                        // unik per business, pemegangnya yang salah.
                        if ($holder === $ticketId) {
                            $skip[] = "customer={$existing->id} kode '$kode' bentrok dgn customer lain, tidak diubah (nama '{$name}')";

                            continue;
                        }
                        // Lepaskan kode lama pemilik, lalu ambil yang ini.
                        $plan['fix_code'][] = [
                            'customer_id' => $holder,
                            'ticket_id' => null,
                            'old' => (string) ($N->table('customers')->where('id', $holder)->value('customer_code') ?? $kode),
                            'new' => '',
                            'nama' => '(pelepasan kode)',
                        ];
                    }
                    $plan['fix_code'][] = [
                        'customer_id' => (int) $existing->id,
                        'ticket_id' => $ticketId,
                        'old' => (string) $existing->customer_code,
                        'new' => $kode,
                        'nama' => $name,
                    ];
                    $codeByCustomer[$kode] = (int) $existing->id;
                }
            }
        }

        // 4. Tampilkan rencana
        $this->line('');
        $this->info('--- Tiket yang perlu dikoreksi ('.count($plan['fix_ticket']).') ---');
        foreach ($plan['fix_ticket'] as $f) {
            $d = [];
            foreach ($f['diff'] as $col => $pair) {
                $d[] = "$col: '{$pair[0]}' -> '{$pair[1]}'";
            }
            $this->line(sprintf('   ticket=%-5d kode=%-18s %-26s %s', $f['ticket_id'], $f['kode'], $f['nama'], implode(' | ', $d)));
        }

        $this->line('');
        $this->info('--- Customers perlu dibuat ('.count($plan['create']).') ---');
        foreach (array_slice($plan['create'], 0, 10) as $c) {
            $this->line(sprintf('   ticket=%-5d kode=%-18s %-26s status=%s', $c['ticket_id'], $c['kode'], $c['name'], $c['status'] ?? '-'));
        }
        if (count($plan['create']) > 10) {
            $this->line('   ... +'.(count($plan['create']) - 10).' lainnya');
        }

        $this->line('');
        $this->info('--- customers.user_id null ('.count($plan['fix_user']).') ---');
        foreach (array_slice($plan['fix_user'], 0, 10) as $f) {
            $this->line(sprintf('   customer=%-5d %-26s %s', $f['customer_id'], $f['name'], $f['reason']));
        }

        $this->line('');
        $this->info('--- customer_code tidak sesuai legacy ('.count($plan['fix_code']).') ---');
        foreach (array_slice($plan['fix_code'], 0, 10) as $f) {
            $this->line(sprintf('   customer=%-5d ticket=%-5d "%s" -> "%s"  %s', $f['customer_id'], $f['ticket_id'], $f['old'], $f['new'], $f['nama']));
        }
        if (count($plan['fix_code']) > 10) {
            $this->line('   ... +'.(count($plan['fix_code']) - 10).' lainnya');
        }

        $this->line('');
        if ($skip) {
            $this->warn('--- Dilewati ('.count($skip).') ---');
            foreach ($skip as $s) {
                $this->line('   '.$s);
            }
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Tidak ada perubahan. Jalankan dengan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        // 5. Terapkan dalam transaksi
        $this->line('');
        $this->error('Menerapkan perubahan...');

        $applied = ['tickets' => 0, 'users' => 0, 'customers' => 0, 'codes' => 0, 'failed' => 0];

        $N->transaction(function () use ($N, $plan, $usersByEmail, &$maxUserId, &$applied) {
            // a. Perbaiki tiket
            foreach ($plan['fix_ticket'] as $f) {
                // $f['diff'][kolom] = [nilai_lama, nilai_baru]
                $set = [];
                foreach ($f['diff'] as $col => $pair) {
                    $set[$col] = $pair[1];
                }
                if (! $set) {
                    continue;
                }
                $set['updated_at'] = now();
                $N->table('installation_tickets')->where('id', $f['ticket_id'])->update($set);
                $applied['tickets']++;
            }

            // b. Perbaiki customer_code
            foreach ($plan['fix_code'] as $f) {
                $newCode = trim($f['new']);
                if ($newCode !== '') {
                    $clash = $N->table('customers')
                        ->where('customer_code', $newCode)
                        ->where('id', '!=', $f['customer_id'])
                        ->exists();
                    if ($clash) {
                        $applied['failed']++;
                        $this->warn('   Gagal update kode customer='.$f['customer_id'].': "'.$newCode.'" sudah dipakai');

                        continue;
                    }
                }
                $N->table('customers')->where('id', $f['customer_id'])
                    ->update(['customer_code' => $newCode ?: null, 'updated_at' => now()]);
                $applied['codes']++;
            }

            // c. Buat customers baru (perlu user dulu)
            foreach ($plan['create'] as $c) {
                try {
                    $email = self::uniqueEmail($N, $c['name'], (int) $c['legacy_customer_id'], $usersByEmail);
                    $usersByEmail[$email] = true;

                    $userId = $N->table('users')->insertGetId([
                        'name' => $c['name'],
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'role' => 'pelanggan',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $applied['users']++;

                    $N->table('customers')->insert([
                        'ticket_id' => $c['ticket_id'],
                        'user_id' => $userId,
                        'customer_code' => self::uniqueCode($c['kode'], (int) $c['legacy_customer_id'], $N),
                        'initial_meter_reading' => 0,
                        'meter_photo_url' => null,
                        'activated_at' => self::parseDateTime($c['activated_at']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $applied['customers']++;
                } catch (\Throwable $e) {
                    $applied['failed']++;
                    $this->warn('   Gagal ticket='.$c['ticket_id'].' '.$c['name'].': '.$e->getMessage());
                }
            }

            // d. user_id null
            foreach ($plan['fix_user'] as $f) {
                $cust = $plan['create'][$f['customer_id'] ?? null] ?? null;
                $email = self::uniqueEmail($N, $f['name'], (int) $f['customer_id'], $usersByEmail);
                $usersByEmail[$email] = true;
                $userId = $N->table('users')->insertGetId([
                    'name' => $f['name'],
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'pelanggan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $applied['users']++;
                $N->table('customers')->where('id', $f['customer_id'])
                    ->update(['user_id' => $userId, 'updated_at' => now()]);
                $applied['customers']++;
            }
        });

        $this->line('');
        $this->info('Selesai:');
        $this->line('   tiket diperbaiki     : '.$applied['tickets']);
        $this->line('   customer_code diperbaiki : '.$applied['codes']);
        $this->line('   user dibuat          : '.$applied['users']);
        $this->line('   customers dibuat     : '.$applied['customers']);
        if ($applied['failed']) {
            $this->error('   gagal                : '.$applied['failed']);
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
        $n = 1;
        while (isset($used[$email]) || $N->table('users')->where('email', $email)->exists()) {
            $email = $base.'_'.$legacyId.'@gmail.com';
            if (! isset($used[$email]) && ! $N->table('users')->where('email', $email)->exists()) {
                break;
            }
            $email = $base.'_'.$legacyId.'_'.$n.'@gmail.com';
            $n++;
        }

        return $email;
    }

    private static function uniqueCode(string $kode, int $legacyId, $N): string
    {
        $base = $kode !== '' ? $kode : 'LEGACY-'.$legacyId;
        if (! $N->table('customers')->where('customer_code', $base)->exists()) {
            return $base;
        }

        return $base.'_c'.$legacyId;
    }

    private static function parseDateTime($val): ?string
    {
        if ($val === null) {
            return null;
        }
        $v = trim((string) $val);
        if ($v === '' || $v === '-' || str_starts_with($v, '0000-00-00')) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($v)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}