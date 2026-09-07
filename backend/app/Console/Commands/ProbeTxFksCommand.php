<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxFksCommand extends Command
{
    protected $signature = 'probe:tx-fks';
    protected $description = 'Probe FK on transactions table';

    public function handle(): int
    {
        $rows = DB::select(
            "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        foreach ($rows as $r) {
            $this->line($r->CONSTRAINT_NAME . ' | ' . $r->COLUMN_NAME . ' -> ' . $r->REFERENCED_TABLE_NAME . '.' . $r->REFERENCED_COLUMN_NAME);
        }
        return self::SUCCESS;
    }
}
