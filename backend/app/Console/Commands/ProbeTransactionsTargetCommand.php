<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTransactionsTargetCommand extends Command
{
    protected $signature = 'probe:transactions-target';
    protected $description = 'Probe mapping legacy transactions → new transactions';

    public function handle(): int
    {
        $this->line('--- new.transactions schema ---');
        foreach (DB::select('SHOW COLUMNS FROM transactions') as $r) {
            $this->line($r->Field . ' | ' . $r->Type . ' | ' . $r->Null);
        }
        $this->line('--- legacy.transactions count ---');
        $this->line('all: ' . DB::connection('legacy')->selectOne('SELECT COUNT(*) c FROM transactions')->c);
        $this->line('null tgl: ' . DB::connection('legacy')->selectOne(
            "SELECT COUNT(*) c FROM transactions WHERE tgl_transaksi IS NULL OR CHAR_LENGTH(CAST(tgl_transaksi AS CHAR)) < 8"
        )->c);
        $this->line('--- accounts sample ---');
        foreach (DB::select('SELECT * FROM accounts ORDER BY id LIMIT 5') as $r) {
            $this->line(json_encode($r));
        }
        $this->line('--- account kode length distribution ---');
        foreach (DB::select('SELECT CHAR_LENGTH(kode_akun) len, COUNT(*) c FROM accounts GROUP BY len ORDER BY len') as $r) {
            $this->line($r->len . ' | ' . $r->c);
        }
        $this->line('--- jenis_transactions ---');
        foreach (DB::select('SELECT * FROM jenis_transactions') as $r) {
            $this->line(json_encode($r));
        }
        $this->line('--- legacy.rekening_debit/kredit examples (verify type matches) ---');
        foreach (DB::connection('legacy')->select('SELECT DISTINCT rekening_debit FROM transactions WHERE rekening_debit != 0 LIMIT 10') as $r) {
            $newId = DB::selectOne('SELECT id, kode_akun FROM accounts WHERE id = ?', [$r->rekening_debit]);
            $this->line(sprintf('legacy %d → new %s', $r->rekening_debit, $newId ? $newId->id . '/' . $newId->kode_akun : 'NOT FOUND'));
        }
        return self::SUCCESS;
    }
}
