<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportLegacyUsersCommand extends Command
{
    protected $signature = 'import:users
                            {--dry-run : Cetak rencana import, tanpa ubah data}
                            {--force   : Lewati konfirmasi}';

    protected $description = 'Import users dari DB lama (admin/surveyor/teknisi only - exclude pelanggan)';

    /** Map jabatan (positions.id) â†’ role enum DB baru */
    private array $jabatanToRole = [
        1 => 'admin',     // Direktur
        2 => 'admin',     // Sekertaris
        3 => 'admin',     // Bendahara
        4 => 'admin',     // Pengawas
        5 => 'teknisi',   // Cater/teknisi lapangan (pembaca meter & penanggung jawab instalasi)
        6 => 'admin',     // Pos Bayar
        7 => 'teknisi',   // Teknisi khusus
        8 => 'admin',     // Ketua
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $bizFilter = $this->option('business');

        $this->info('=== Import Users: DB Lama â†’ DB Baru ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        if ($bizFilter) {
            $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");
        }
        $this->line('');

        // 1. Ambil user legacy. Kolom `business_id` ADA di tabel `users`
        //    legacy; tanpa opsi --business perilaku lama (semua business)
        //    tetap dipertahankan supaya pemakaian lain tidak pecah.
        $legacyUsers = DB::connection('legacy')
            ->table('users')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->get();
        $this->info('User di DB lama: '.$legacyUsers->count());

        // 2. Cek legacy_id terbesar untuk offset ID biar ga tabrakan
        $existingMaxId = User::max('id') ?? 0;
        $this->info('ID tertinggi di DB baru saat ini: '.$existingMaxId);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        // Akun staff yang memakai password bawaan (legacy non-bcrypt/kosong)
        // -> wajib reset; dicatat untuk laporan migrasi.
        $needsReset = [];
        $bar = $this->output->createProgressBar($legacyUsers->count());
        $bar->start();

        foreach ($legacyUsers as $row) {
            try {
                $name = trim((string) ($row->nama ?? ''));
                $username = trim((string) ($row->username ?? ''));

                // Skip baris tanpa nama (data rusak)
                if ($name === '') {
                    $skipped++;
                    if (! $isDryRun) {
                        $this->line("\n  [SKIP] legacy_id={$row->id} nama kosong");
                    }
                    $bar->advance();

                    continue;
                }

                $role = $this->jabatanToRole[(int) ($row->jabatan ?? 0)] ?? 'admin';
                $email = $this->buildEmail($username, $row->id);

                // Tentukan password: kalau hash legacy bcrypt, pakai langsung;
                // kalau plaintext (md5/non-bcrypt), generate placeholder + wajib reset nanti.
                $password = $this->resolvePassword($row->password);

                if ($isDryRun) {
                    $this->line(sprintf(
                        "\n  [#%dâ†’+%d] %s | username=%s | role=%s | email=%s | pass=%s",
                        $row->id, $existingMaxId + ($row->id - $legacyUsers->min('id')) + 1,
                        $name, $username ?: '(empty)', $role, $email,
                        $password['mode']
                    ));
                    $bar->advance();

                    continue;
                }

                // Pakai legacy_id + offset supaya ID konsisten & tidak tabrakan
                $newId = $existingMaxId + (int) $row->id;

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'id' => $newId,
                        'name' => $name,
                        'password' => $password['hash'],
                        'must_change_password' => $password['must_change'],
                        'role' => $role,
                        'jabatan_id' => (int) ($row->jabatan ?? 0) ?: null,
                    ]
                );

                if ($password['must_change']) {
                    $needsReset[$user->id] = ['id' => $user->id, 'email' => $user->email, 'role' => $user->role];
                }

                if ($user->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                $bar->advance();
            } catch (\Throwable $e) {
                $errors[] = ['legacy_id' => $row->id, 'nama' => $row->nama ?? '-', 'error' => $e->getMessage()];
                $bar->advance();
            }
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info('Ringkasan:');
        $this->line("  - Dibuat       : {$created}");
        $this->line("  - Diupdate     : {$updated}");
        $this->line("  - Dilewati     : {$skipped}");
        $this->line('  - Gagal        : '.count($errors));

        if (! empty($errors)) {
            $this->line('');
            $this->warn('Detail error:');
            foreach ($errors as $err) {
                $this->line(sprintf('  - legacy_id=%s nama=%s | %s', $err['legacy_id'], $err['nama'], $err['error']));
            }
        }

        // Daftar staff pemakai password bawaan -> wajib reset (B4).
        if (! $isDryRun) {
            $this->line('');
            $this->warn('Akun staff yang wajib reset password (password bawaan): '.count($needsReset));
            if (! empty($needsReset)) {
                $this->table(
                    ['ID', 'Email', 'Role'],
                    array_values($needsReset)
                );
            }
        }

        // Tabel rekap
        $this->line('');
        $this->info('Rekap user di DB baru (by role):');
        $byRole = User::selectRaw('role, COUNT(*) AS cnt')->groupBy('role')->orderBy('role')->get();
        foreach ($byRole as $r) {
            $this->line(sprintf('  %-12s : %d', $r->role, $r->cnt));
        }

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Bikin email unique dari username legacy.
     * Fallback ke legacy{id}@legacy.local kalau username kosong/duplikat.
     */
    private function buildEmail(string $username, int $legacyId): string
    {
        $local = $username !== '' ? $username : "user{$legacyId}";
        // Bersihkan karakter yang ga valid di email
        $local = preg_replace('/[^a-zA-Z0-9._-]/', '_', $local);

        return "{$local}@legacy.local";
    }

    /**
     * Resolve password dari hash legacy.
     * Return ['hash' => bcrypt, 'mode' => ..., 'must_change' => bool].
     * bcrypt-legacy: pakai hash langsung (format $2y$ atau $2b$) -> TIDAK wajib ganti
     * plain-legacy : hash MD5/non-bcrypt -> password bawaan "password", WAJIB ganti
     * placeholder  : hash kosong/null -> password bawaan "password", WAJIB ganti
     */
    private function resolvePassword(?string $legacyHash): array
    {
        if (! $legacyHash || trim($legacyHash) === '') {
            return ['hash' => Hash::make('password'), 'mode' => 'placeholder', 'must_change' => true];
        }
        if (preg_match('/^\$2[aby]\$/', $legacyHash)) {
            // bcrypt legacy â†’ pakai langsung (kompatibel dengan Laravel)
            return ['hash' => $legacyHash, 'mode' => 'bcrypt-legacy', 'must_change' => false];
        }

        // Plain / md5 / sha1 â†’ hash ulang sebagai placeholder, wajib reset nanti
        return [
            'hash' => Hash::make('password'),
            'mode' => 'plain-legacy(was:'.substr($legacyHash, 0, 8).'...)',
            'must_change' => true,
        ];
    }
}
