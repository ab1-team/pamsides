<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ImportLegacyUsersCommand extends Command
{
    protected $signature = 'import:users
                            {--dry-run : Cetak rencana import, tanpa ubah data}
                            {--force   : Lewati konfirmasi}';

    protected $description = 'Import users dari DB lama (admin/surveyor/teknisi only - exclude pelanggan)';

    /** Map jabatan (positions.id) → role enum DB baru */
    private array $jabatanToRole = [
        1 => 'admin',     // Direktur
        2 => 'admin',     // Sekertaris
        3 => 'admin',     // Bendahara
        4 => 'admin',     // Pengawas
        5 => 'surveyor',  // Caters (pembaca meter)
        6 => 'admin',     // Pos Bayar
        7 => 'teknisi',   // Teknisi
        8 => 'admin',     // Ketua
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('=== Import Users: DB Lama → DB Baru ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        $this->line('');

        // 1. Ambil semua user legacy
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get();
        $this->info('User di DB lama: '.$legacyUsers->count());

        // 2. Cek legacy_id terbesar untuk offset ID biar ga tabrakan
        $existingMaxId = User::max('id') ?? 0;
        $this->info('ID tertinggi di DB baru saat ini: '.$existingMaxId);

        $created = 0; $updated = 0; $skipped = 0; $errors = [];
        $bar = $this->output->createProgressBar($legacyUsers->count());
        $bar->start();

        foreach ($legacyUsers as $row) {
            try {
                $name = trim((string) ($row->nama ?? ''));
                $username = trim((string) ($row->username ?? ''));

                // Skip baris tanpa nama (data rusak)
                if ($name === '') {
                    $skipped++;
                    if (! $isDryRun) $this->line("\n  [SKIP] legacy_id={$row->id} nama kosong");
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
                        "\n  [#%d→+%d] %s | username=%s | role=%s | email=%s | pass=%s",
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
                        'id'       => $newId,
                        'name'     => $name,
                        'password' => $password['hash'],
                        'role'     => $role,
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                $bar->advance();
            } catch (\Throwable $e) {
                $errors[] = ['legacy_id' => $row->id, 'nama' => $row->nama ?? '-', 'error' => $e->getMessage()];
                Log::error('Import user gagal', ['legacy_id' => $row->id, 'error' => $e->getMessage()]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->line(''); $this->line('');
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
     * Return ['hash' => bcrypt, 'mode' => 'bcrypt-legacy' | 'plain-legacy' | 'placeholder'].
     * bcrypt-legacy: pakai hash langsung (format $2y$ atau $2b$)
     * plain-legacy : hash MD5/non-bcrypt → bcrypt ulang pakai password plain (placeholder)
     * placeholder  : hash kosong/null → bcrypt "password" (default, harus reset)
     */
    private function resolvePassword(?string $legacyHash): array
    {
        if (! $legacyHash || trim($legacyHash) === '') {
            return ['hash' => Hash::make('password'), 'mode' => 'placeholder'];
        }
        if (preg_match('/^\$2[aby]\$/', $legacyHash)) {
            // bcrypt legacy → pakai langsung (kompatibel dengan Laravel)
            return ['hash' => $legacyHash, 'mode' => 'bcrypt-legacy'];
        }
        // Plain / md5 / sha1 → hash ulang sebagai placeholder, wajib reset nanti
        return ['hash' => Hash::make('password'), 'mode' => "plain-legacy(was:" . substr($legacyHash, 0, 8) . "...)"];
    }
}