<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTransactionsExtraCommand extends Command
{
    protected $signature = 'probe:transactions-extra';
    protected $description = 'Probe extra: legacy transaction_id sample, relasi distribution';

    public function handle(): int
    {
        $this->line('--- sample legacy.transaction_id values ---');
        foreach (DB::connection('legacy')->select(
            'SELECT DISTINCT transaction_id, COUNT(*) c FROM transactions GROUP BY transaction_id ORDER BY c DESC LIMIT 20'
        ) as $r) {
            $this->line(sprintf('%6d | %s', $r->c, $r->transaction_id));
        }
        $this->line('--- legacy.relasi distribution ---');
        foreach (DB::connection('legacy')->select(
            "SELECT COALESCE(NULLIF(relasi, ''), '(empty)') AS relasi, COUNT(*) c FROM transactions GROUP BY relasi ORDER BY c DESC LIMIT 20"
        ) as $r) {
            $this->line(sprintf('%6d | %s', $r->c, $r->relasi));
        }
        $this->line('--- new.accounts count, max id ---');
        $row = DB::selectOne('SELECT COUNT(*) c, MAX(id) m FROM accounts');
        $this->line('count=' . $row->c . ' max=' . $row->m);
        $this->line('--- legacy.rekening_debit/kredit outside accounts range ---');
        foreach (DB::connection('legacy')->select(
            'SELECT
              (SELECT COUNT(*) FROM transactions WHERE rekening_debit NOT IN (SELECT id FROM accounts WHERE id IS NOT NULL)) AS debit_orphan,
              (SELECT COUNT(*) FROM transactions WHERE rekening_kredit NOT IN (SELECT id FROM accounts WHERE id IS NOT NULL)) AS kredit_orphan'
        ) as $r) {
            $this->line('debit_orphan=' . $r->debit_orphan . ' kredit_orphan=' . $r->kredit_orphan);
        }
        $this->line('--- new.transactions: apakah ada row existing? ---');
        $this->line('count: ' . DB::table('transactions')->count());
        return self::SUCCESS;
    }
}
