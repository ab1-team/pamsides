<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxCreateCommand extends Command
{
    protected $signature = 'probe:tx-create';
    protected $description = 'Show CREATE TABLE for transactions';

    public function handle(): int
    {
        $rows = DB::select('SHOW CREATE TABLE transactions');
        $this->line($rows[0]->{'Create Table'});
        return self::SUCCESS;
    }
}
