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
        $tableNames = array_map(fn($t) => $t->$key, $tables);

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
                $rows->map(fn($r) => array_map(
                    fn($v) => is_string($v) ? mb_substr($v, 0, 60) : $v,
                    (array) $r
                ))->toArray()
            );
        }

        return self::SUCCESS;
    }
}
