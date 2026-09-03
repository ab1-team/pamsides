<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckNewDbCommand extends Command
{
    protected $signature = 'new:check {--table=transactions}';
    protected $description = 'Cek isi database baru';

    public function handle(): int
    {
        $this->info('DB default: '.DB::connection()->getDatabaseName());

        $tables = DB::select('SHOW TABLES');
        $names = array_map(fn($r) => array_values((array)$r)[0], $tables);
        $this->info('Tabel di DB baru: '.implode(', ', $names));

        $t = $this->option('table');
        if (in_array($t, $names)) {
            $this->info("Isi {$t} (5 baris):");
            foreach (DB::table($t)->limit(5)->get() as $row) {
                $this->line(str_repeat('-', 60));
                foreach ((array)$row as $col => $val) {
                    if (is_string($val) && mb_strlen($val) > 150) {
                        $val = mb_substr($val, 0, 150).'...';
                    }
                    $this->line(sprintf('  %-25s : %s', $col, is_scalar($val) ? $val : json_encode($val)));
                }
            }
        }

        return self::SUCCESS;
    }
}
