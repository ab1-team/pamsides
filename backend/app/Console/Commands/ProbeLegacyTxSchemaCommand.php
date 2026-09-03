<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProbeLegacyTxSchemaCommand extends Command
{
    protected $signature = 'legacy:probe-tx-schema';
    protected $description = 'Lihat kolom & tipe dari tabel transactions (legacy)';

    public function handle(): int
    {
        $cols = DB::connection('legacy')->select('SHOW COLUMNS FROM transactions');
        foreach ($cols as $c) {
            $this->line(sprintf('  %-25s %-20s null=%s key=%s', $c->Field, $c->Type, $c->Null, $c->Key));
        }

        $this->line('');
        $this->info('Contoh 3 baris:');
        foreach (DB::connection('legacy')->table('transactions')->limit(3)->get() as $row) {
            $this->line(str_repeat('-', 60));
            foreach ((array)$row as $col => $val) {
                if (is_string($val) && mb_strlen($val) > 120) {
                    $val = mb_substr($val, 0, 120).'...';
                }
                $this->line(sprintf('  %-25s : %s', $col, is_scalar($val) ? $val : json_encode($val)));
            }
        }

        $this->line('');
        $this->info('Tabel baru `transactions` columns:');
        foreach (Schema::getColumnListing('transactions') as $c) {
            $this->line('  '.$c);
        }

        $this->line('');
        $this->info('Counters:');
        $this->line('  legacy total       : '.DB::connection('legacy')->table('transactions')->count());
        $this->line('  new total          : '.DB::table('transactions')->count());
        $this->line('  legacy bill_payments : '.DB::connection('legacy')->table('bill_payments')->count());
        $this->line('  new bill_payments  : '.DB::table('bill_payments')->count());

        return self::SUCCESS;
    }
}
