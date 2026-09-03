<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyStatusDistCommand extends Command
{
    protected $signature = 'legacy:dist {table} {--column=status : Kolom yang mau di-GROUP BY}';
    protected $description = 'Lihat distribusi nilai kolom (mis. status) di tabel legacy';

    public function handle(): int
    {
        $table = $this->argument('table');
        $column = $this->option('column');
        $rows = DB::connection('legacy')->select("
            SELECT `{$column}` AS label, COUNT(*) AS cnt
            FROM {$table}
            GROUP BY `{$column}`
            ORDER BY cnt DESC
        ");
        $this->info("Distribusi '{$column}' di '{$table}':");
        $total = 0;
        foreach ($rows as $r) {
            $total += $r->cnt;
            $this->line(sprintf('  %-10s | %5d', $r->label ?? 'NULL', $r->cnt));
        }
        $this->line(str_repeat('-', 30));
        $this->line(sprintf('  TOTAL      | %5d', $total));
        return self::SUCCESS;
    }
}