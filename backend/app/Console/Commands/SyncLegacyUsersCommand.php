<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyUsersCommand extends Command
{
    protected $signature = 'legacy:sync-users
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--drop-unused=0 : Hapus staff dari business lain yang tidak terpakai}';

    protected $description = 'Selaraskan users (jabatan_id + role) staff dengan legacy: cater/technisi→teknisi, bendahara/direktur/ketua→admin';

    /**
     * jabatan legacy → role app baru.
     * Catatan: jabatan 5 "Caters" dipetakan ke TEKNISI (bukan admin/surveyor)
     * karena di app baru techisi yang mencatat meter & installing.
     */
    private const ROLE_BY_JABATAN = [
        1 => 'admin',     // Direktur
        2 => 'admin',     // Sekretaris
        3 => 'admin',     // Bendahara
        4 => 'admin',     // Pengawas
        5 => 'teknisi',   // Cater  → teknisi
        6 => 'admin',     // Pos Bayar
        7 => 'teknisi',   // Teknisi
        8 => 'admin',     // Ketua
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $dropUnused = (bool) $this->option('drop-unused');

        $this->info('=== Selaraskan users dengan legacy (jabatan + role) ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: users akan diubah.');
        $this->info("business_id acuan={$biz} | hapus staff tak terpakai=" . ($dropUnused ? 'ya' : 'tidak'));
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // Legacy users dikunci lewat nama (nama staff unik di legacy)
        $legacyByName = [];
        foreach ($L->table('users')->orderBy('id')->get() as $lu) {
            $key = strtolower(trim((string) $lu->nama));
            if ($key === '') {
                continue;
            }
            $legacyByName[$key] = $lu;
        }

        $newStaff = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->orderBy('id')->get();

        $jabatanFix = [];
        $roleFix = [];
        $unchanged = [];
        $noLegacy = [];

        foreach ($newStaff as $u) {
            $key = strtolower(trim((string) $u->name));
            $lu = $legacyByName[$key] ?? null;
            if ($lu === null) {
                $noLegacy[] = $u;

                continue;
            }

            $legacyJabatan = (int) $lu->jabatan;
            $wantRole = self::ROLE_BY_JABATAN[$legacyJabatan] ?? 'admin';
            $legacyBiz = (int) $lu->business_id;

            $changes = [];
            if ((int) ($u->jabatan_id ?? 0) !== $legacyJabatan) {
                $changes[] = sprintf('jabatan_id: %s -> %d', $u->jabatan_id === null ? 'null' : $u->jabatan_id, $legacyJabatan);
            }
            if ($u->role !== $wantRole) {
                $changes[] = sprintf('role: %s -> %s', $u->role, $wantRole);
            }

            if ($changes) {
                $row = [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'legacy_jabatan' => $legacyJabatan,
                    'legacy_biz' => $legacyBiz,
                    'role' => $u->role,
                    'want_role' => $wantRole,
                    'jabatan_id' => $u->jabatan_id,
                    'changes' => $changes,
                ];
                if ((int) ($u->jabatan_id ?? 0) !== $legacyJabatan) {
                    $jabatanFix[] = $row;
                }
                if ($u->role !== $wantRole) {
                    $roleFix[] = $row;
                }
            } else {
                $unchanged[] = ['id' => (int) $u->id, 'name' => (string) $u->name, 'legacy_biz' => $legacyBiz];
            }
        }

        $this->info('--- Ringkasan ---');
        $this->line('  staff di DB baru            : ' . $newStaff->count());
        $this->line('  jabatan_id perlu diisi      : ' . count($jabatanFix));
        $this->line('  role perlu dikoreksi       : ' . count($roleFix));
        $this->line('  sudah benar                 : ' . count($unchanged));
        $this->line('  tidak ada di legacy         : ' . count($noLegacy));
        $this->line('');

        $this->info('--- Semua staff & asal business-nya ---');
        foreach ($newStaff as $u) {
            $key = strtolower(trim((string) $u->name));
            $lu = $legacyByName[$key] ?? null;
            $bizInfo = $lu ? 'legacy biz=' . $lu->business_id . ' jabatan=' . $lu->jabatan : 'TIDAK ADA di legacy';
            $jabInfo = 'jabatan_id=' . ($u->jabatan_id === null ? 'null' : $u->jabatan_id);
            $marker = '';
            if ($lu) {
                $legacyBiz = (int) $lu->business_id;
                $wantRole = self::ROLE_BY_JABATAN[(int) $lu->jabatan] ?? 'admin';
                if ($legacyBiz !== $biz) {
                    $marker .= ' <<< dari business ' . $legacyBiz;
                }
                if ($u->role !== $wantRole) {
                    $marker .= ' [role salah: ' . $u->role . '→' . $wantRole . ']';
                }
                if ((int) ($u->jabatan_id ?? 0) !== (int) $lu->jabatan) {
                    $marker .= ' [jabatan_id kosong]';
                }
            }
            echo sprintf("  id=%-5s %-30s role=%-10s %-16s %s%s\n",
                $u->id, $u->name, $u->role, $jabInfo, $bizInfo, $marker);
        }
        $this->line('');

        if ($noLegacy) {
            $this->warn('--- Staff yang tidak ada di legacy users ---');
            foreach ($noLegacy as $u) {
                echo sprintf("   id=%-5s %-30s role=%-10s email=%s\n", $u->id, $u->name, $u->role, $u->email);
            }
            $this->line('');
        }

        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->error('Menerapkan...');
        $updated = 0;

        foreach ($newStaff as $u) {
            $key = strtolower(trim((string) $u->name));
            $lu = $legacyByName[$key] ?? null;
            if ($lu === null) {
                continue;
            }
            $legacyJabatan = (int) $lu->jabatan;
            $wantRole = self::ROLE_BY_JABATAN[$legacyJabatan] ?? 'admin';

            $set = [];
            if ((int) ($u->jabatan_id ?? 0) !== $legacyJabatan) {
                $set['jabatan_id'] = $legacyJabatan;
            }
            if ($u->role !== $wantRole) {
                $set['role'] = $wantRole;
            }
            if (! $set) {
                continue;
            }
            $set['updated_at'] = now();
            $N->table('users')->where('id', $u->id)->update($set);
            $updated++;
        }

        $this->line('');
        $this->info('Selesai. Users diperbarui: ' . $updated);
        $this->line('');

        // Ringkasan akhir
        $this->info('Distribusi role sekarang:');
        foreach ($N->table('users')->select('role', DB::raw('count(*) c'))->groupBy('role')->get() as $r) {
            echo sprintf("   %-12s %6d\n", $r->role, $r->c);
        }
        $this->line('');
        $this->info('Staff per jabatan:');
        $jab = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])
            ->join('jabatans', 'jabatans.id', '=', 'users.jabatan_id')
            ->select('jabatans.nama_jabatan', 'users.role', DB::raw('count(*) c'))
            ->groupBy('jabatans.nama_jabatan', 'users.role')->get();
        foreach ($jab as $j) {
            echo sprintf("   %-14s %-10s %4d\n", $j->nama_jabatan, $j->role, $j->c);
        }
        $staffWithoutJab = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->whereNull('jabatan_id')->count();
        $this->line('  staff tanpa jabatan_id : ' . $staffWithoutJab);

        return self::SUCCESS;
    }
}