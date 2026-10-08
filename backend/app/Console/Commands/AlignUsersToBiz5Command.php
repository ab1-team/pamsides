<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AlignUsersToBiz5Command extends Command
{
    protected $signature = 'legacy:align-users-biz5
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--drop-staff=0 : Hapus staff dari business lain yang tidak terpakai}
                            {--keep-email : Jangan ubah email staff yang dipertahankan}';

    protected $description = 'Selaraskan users dengan legacy biz5: role sesuai jabatan, jabatan_id dari tabel jabatans, staff biz lain dibersihkan';

    /** Jabatan yang dipakai app baru. Jabatan lain tidak ada perannya. */
    private const USED_JABATAN = [1 => 'Direktur', 3 => 'Bendahara', 5 => 'Caters', 8 => 'Ketua'];

    /** jabatan → role app baru */
    private const ROLE_BY_JABATAN = [
        1 => 'admin',   // Direktur
        2 => 'admin',   // Sekretaris
        3 => 'admin',   // Bendahara
        4 => 'admin',   // Pengawas
        5 => 'teknisi', // Cater → teknisi
        6 => 'admin',   // Pos Bayar
        7 => 'teknisi', // Teknisi
        8 => 'admin',   // Ketua
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $dropStaff = (bool) $this->option('drop-staff');

        $this->info('=== Selaraskan users dengan legacy business_id=' . $biz . ' ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: users akan diubah.');
        $this->info('Hapus staff dari business lain yang tak terpakai: ' . ($dropStaff ? 'ya' : 'tidak'));
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // Jabatan yang benar-benar ada di legacy biz5
        $legacyJabatanBiz5 = [];
        foreach ($L->table('users')->where('business_id', $biz)->get(['jabatan']) as $lu) {
            $legacyJabatanBiz5[(int) $lu->jabatan] = true;
        }
        $this->info('Jabatan yang dipakai legacy biz' . $biz . ': ' . implode(', ', array_keys($legacyJabatanBiz5)));
        $this->line('');

        // Jabatans di DB baru
        $jabatanById = $N->table('jabatans')->pluck('nama_jabatan', 'id')->all();

        // Legacy users (untuk acuan jabatan)
        $legacyByName = [];
        foreach ($L->table('users')->get() as $lu) {
            $k = strtolower(trim((string) $lu->nama));
            if ($k !== '') {
                $legacyByName[$k] = $lu;
            }
        }

        $staff = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->orderBy('id')->get();

        // Rencana: update role/jabatan_id
        $updates = [];
        $remove = [];
        $keep = [];

        foreach ($staff as $u) {
            $key = strtolower(trim((string) $u->name));
            $lu = $legacyByName[$key] ?? null;

            if ($lu === null) {
                $keep[] = ['user' => $u, 'reason' => 'tidak ada di legacy'];

                continue;
            }

            $legacyBiz = (int) $lu->business_id;
            $legacyJabatan = (int) $lu->jabatan;
            $jabatanValid = isset($jabatanById[$legacyJabatan]);
            $wantRole = self::ROLE_BY_JABATAN[$legacyJabatan] ?? 'admin';

            // Cek pemakaian
            $used = $this->usageCount((int) $u->id);

            // Staff dari business lain yang tidak terpakai → kandidat hapus
            if ($legacyBiz !== $biz && $used === 0) {
                $remove[] = [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'legacy_biz' => $legacyBiz,
                    'jabatan' => $legacyJabatan,
                    'role' => (string) $u->role,
                ];

                continue;
            }

            $set = [];
            if ((int) ($u->jabatan_id ?? 0) !== $legacyJabatan || ! $jabatanValid) {
                $set['jabatan_id'] = $jabatanValid ? $legacyJabatan : null;
            }
            if ($u->role !== $wantRole) {
                $set['role'] = $wantRole;
            }

            if ($set) {
                $updates[] = [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'legacy_biz' => $legacyBiz,
                    'legacy_jabatan' => $legacyJabatan,
                    'set' => $set,
                    'used' => $used,
                ];
            } else {
                $keep[] = ['user' => $u, 'reason' => 'sudah benar', 'legacy_biz' => $legacyBiz, 'used' => $used];
            }
        }

        $this->info('--- Staff yang akan di-update ---');
        $this->line('  jumlah : ' . count($updates));
        foreach ($updates as $u) {
            $parts = [];
            foreach ($u['set'] as $col => $v) {
                $parts[] = $col . '=' . ($v === null ? 'null' : $v);
            }
            echo sprintf("  id=%-5s %-30s legacy_biz=%-3s jabatan=%-3s dipakai=%-6d -> %s\n",
                $u['id'], $u['name'], $u['legacy_biz'], $u['legacy_jabatan'], $u['used'], implode(' ', $parts));
        }
        $this->line('');

        $this->info('--- Staff dari business lain yang TAK terpakai (kandidat hapus) ---');
        $this->line('  jumlah : ' . count($remove));
        foreach ($remove as $r) {
            echo sprintf("  id=%-5s %-30s legacy_biz=%-3s jabatan=%-3s role=%s\n",
                $r['id'], $r['name'], $r['legacy_biz'], $r['jabatan'], $r['role']);
        }
        $this->line('');

        $this->info('--- Staff yang dipertahankan ---');
        foreach ($keep as $k) {
            $u = $k['user'];
            printf("  id=%-5s %-30s role=%-10s (%s)\n", $u->id, $u->name, $u->role, $k['reason']);
        }
        $this->line('');

        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan --force untuk menerapkan.');

            return self::SUCCESS;
        }

        // Terapkan update role/jabatan_id
        $applied = 0;
        foreach ($updates as $u) {
            $N->table('users')->where('id', $u['id'])->update($u['set'] + ['updated_at' => now()]);
            $applied++;
        }
        $this->info('Users di-update: ' . $applied);

        // Hapus staff dari business lain bila diminta
        if ($dropStaff && $remove) {
            $ids = array_column($remove, 'id');
            $N->transaction(function () use ($N, $ids) {
                $N->table('personal_access_tokens')->whereIn('tokenable_id', $ids)
                    ->where('tokenable_type', 'App\\Models\\User')->delete();
            });
            $N->table('users')->whereIn('id', $ids)->delete();
            $this->info('Staff dihapus: ' . count($ids));
        } else {
            $this->line('');
            $this->warn('Staff dari business lain TIDAK dihapus (gunakan --drop-staff=1 untuk menghapus).');
        }

        $this->line('');
        $this->info('=== HASIL AKHIR ===');
        $this->line('Distribusi role:');
        foreach ($N->table('users')->select('role', DB::raw('count(*) c'))->groupBy('role')->get() as $r) {
            echo sprintf('   %-12s %6d' . PHP_EOL, $r->role, $r->c);
        }

        $this->line('');
        $this->line('Staff per jabatan:');
        $staffs = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->orderBy('id')->get();
        foreach ($staffs as $u) {
            $jabName = $u->jabatan_id === null ? '(null)' : ($jabatanById[$u->jabatan_id] ?? '?');
            printf("  id=%-5s %-30s %-10s jabatan_id=%-5s %s\n",
                $u->id, $u->name, $u->role, (string) $u->jabatan_id, $jabName);
        }

        $nullJab = $N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->whereNull('jabatan_id')->count();
        $this->line('');
        $this->line('Staff dengan jabatan_id null : ' . $nullJab);

        return self::SUCCESS;
    }

    private function usageCount(int $userId): int
    {
        $N = DB::connection();

        return $N->table('installation_tickets')->where('user_id', $userId)->count()
            + $N->table('installation_tickets')->where('created_by', $userId)->count()
            + $N->table('bill_payments')->where('confirmed_by', $userId)->count()
            + $N->table('payments')->where('confirmed_by', $userId)->count()
            + $N->table('meter_readings')->where('recorded_by', $userId)->count()
            + $N->table('transactions')->where('id_user', $userId)->count()
            + $N->table('transactions')->where('penerima_komisi_id', $userId)->count()
            + $N->table('survey_results')->where('surveyor_id', $userId)->count()
            + $N->table('trouble_reports')->where('user_id', $userId)->count()
            + $N->table('trouble_reports')->where('handled_by', $userId)->count();
    }
}