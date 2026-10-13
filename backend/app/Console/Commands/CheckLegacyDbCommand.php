<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLegacyDbCommand extends Command
{
    protected $signature = 'legacy:check
                            {--table= : (opsional) Cek jumlah baris di tabel tertentu di DB lama}';

    protected $description = 'Cek koneksi ke database lama (legacy) dan tampilkan info dasar';

    public function handle(): int
    {
        $this->info('Mengecek koneksi ke database LAMA...');
        $this->line('');

        try {
            $pdo = DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Gagal konek ke DB lama!');
            $this->line('');
            $this->line('Pesan error: '.$e->getMessage());
            $this->line('');
            $this->warn('Pastikan di .env sudah diisi:');
            $this->line('  LEGACY_DB_HOST, LEGACY_DB_PORT, LEGACY_DB_DATABASE');
            $this->line('  LEGACY_DB_USERNAME, LEGACY_DB_PASSWORD');

            // Jebakan paling sering di lokasi ini: password DB legacy mengandung
            // karakter '#'. Tanpa tanda kutip, '#' dibaca sebagai awal KOMENTAR
            // oleh parser .env sehingga password terpotong di tengah -- dan
            // MySQL melaporkan "Access denied", bukan "password salah format".
            if ($this->looksLikeUnquotedHashPassword()) {
                $this->line('');
                $this->error(">> Kemungkinan besar: PASSWORD TERPOTONG karena karakter '#'.");
                $this->line('   Karakter \'#\' tanpa tanda kutip dibaca sebagai awal komentar,');
                $this->line('   jadi nilainya terpotong. Bungkus dengan tanda kutip tunggal:');
                $this->line("     LEGACY_DB_PASSWORD='<password aslinya>'");
                $this->line('   Lalu jalankan: php artisan config:clear');
            }

            return self::FAILURE;
        }

        $cfg = config('database.connections.legacy');
        $this->info('Koneksi BERHASIL');
        $this->table(
            ['Key', 'Value'],
            [
                ['Driver',   $cfg['driver']],
                ['Host',     $cfg['host']],
                ['Port',     $cfg['port']],
                ['Database', $cfg['database']],
                ['Username', $cfg['username']],
            ]
        );

        $version = DB::connection('legacy')->select('SELECT VERSION() AS v');
        $this->line('Versi DB: '.($version[0]->v ?? '-'));
        $this->line('');

        $tables = DB::connection('legacy')->select('SHOW TABLES');
        $key = 'Tables_in_'.($cfg['database'] ?? 'legacy');
        $tableNames = array_map(fn ($t) => $t->$key, $tables);

        $this->info('Tabel yang ditemukan ('.count($tableNames).'):');
        foreach ($tableNames as $name) {
            $count = DB::connection('legacy')->table($name)->count();
            $this->line(sprintf('  - %-40s %d baris', $name, $count));
        }

        if ($table = $this->option('table')) {
            $this->line('');
            if (! in_array($table, $tableNames, true)) {
                $this->error("Tabel '{$table}' tidak ada di DB lama.");

                return self::FAILURE;
            }
            $this->info("Sampel 5 baris pertama dari tabel '{$table}':");
            $rows = DB::connection('legacy')->table($table)->limit(5)->get();
            $this->table(
                array_keys((array) $rows->first() ?? []),
                $rows->map(fn ($r) => array_map(
                    fn ($v) => is_string($v) ? mb_substr($v, 0, 60) : $v,
                    (array) $r
                ))->toArray()
            );
        }

        return self::SUCCESS;
    }

    /**
     * Deteksi jebakan password '#' tanpa tanda kutip.
     *
     * Dua sumber kebenaran dibandingkan:
     *   1. nilai yang BENAR-BENAR dibaca aplikasi (sudah terpotong bila '#'),
     *   2. baris mentah di file .env.
     *
     * Kalau file .env punya '#' pada baris LEGACY_DB_PASSWORD tanpa tanda kutip
     * di sekitar nilai, maka password dipastikan terpotong.
     */
    private function looksLikeUnquotedHashPassword(): bool
    {
        $envFile = $this->laravel->environmentFilePath();
        if (! is_file($envFile)) {
            return false;
        }

        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            if (! preg_match('/^\s*LEGACY_DB_PASSWORD\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }

            $rawValue = trim($m[1]);
            if ($rawValue === '') {
                return false;
            }

            $quoted = (str_starts_with($rawValue, "'") && str_ends_with($rawValue, "'"))
                || (str_starts_with($rawValue, '"') && str_ends_with($rawValue, '"'));

            if ($quoted) {
                return false;   // aman
            }

            // Ada '#' tanpa kutip -> pasti terpotong.
            if (str_contains($rawValue, '#')) {
                return true;
            }
        }

        return false;
    }
}
