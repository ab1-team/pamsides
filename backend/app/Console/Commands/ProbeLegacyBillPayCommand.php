<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeLegacyBillPayCommand extends Command
{
    protected $signature = 'legacy:probe-billpay';
    protected $description = 'Cek tabel bill_payments di legacy (nama mungkin beda)';

    public function handle(): int
    {
        $tables = DB::connection('legacy')->select('SHOW TABLES');
        $names = array_map(fn($r) => array_values((array)$r)[0], $tables);
        $this->info('Tabel legacy: '.implode(', ', $names));
        $this->line('');

        $candidates = array_values(array_filter($names, fn($n) =>
            str_contains($n, 'bill') || str_contains($n, 'payment') || str_contains($n, 'bayar') || str_contains($n, 'lunas')
        ));

        $this->info('Tabel terkait bill/payment: '.implode(', ', $candidates));
        foreach ($candidates as $t) {
            $cols = DB::connection('legacy')->select("SHOW COLUMNS FROM `$t`");
            $this->line("--- $t ---");
            foreach ($cols as $c) {
                $this->line('  '.$c->Field.' '.$c->Type);
            }
            $this->line('  count: '.DB::connection('legacy')->table($t)->count());
            $this->line('');
        }

        return self::SUCCESS;
    }
}
