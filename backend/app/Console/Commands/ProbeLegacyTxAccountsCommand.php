<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeLegacyTxAccountsCommand extends Command
{
    protected $signature = 'legacy:probe-tx-accounts';
    protected $description = 'Lihat pola (rekening_debit, rekening_kredit) di transactions legacy';

    public function handle(): int
    {
        $this->line('Mapping rekening_debit/kredit → count:');
        $rows = DB::connection('legacy')->select("
            SELECT rekening_debit d, rekening_kredit k, COUNT(*) c
            FROM transactions
            GROUP BY rekening_debit, rekening_kredit
            ORDER BY c DESC
            LIMIT 30
        ");
        foreach ($rows as $r) {
            $this->line(sprintf('  debit=%-5s  kredit=%-5s  count=%-6s', $r->d, $r->k, $r->c));
        }

        $this->line('');
        $this->line('Distribusi usage_id / installation_id:');
        $r = DB::connection('legacy')->selectOne("SELECT
            SUM(usage_id>0) usage_pos,
            SUM(installation_id>0) inst_pos,
            SUM(usage_id=0 AND installation_id=0) both_zero,
            SUM(usage_id>0 AND installation_id>0) both_pos,
            COUNT(*) total
            FROM transactions");
        $this->line('  usage_id>0      : '.$r->usage_pos);
        $this->line('  installation_id>0: '.$r->inst_pos);
        $this->line('  both >0         : '.$r->both_pos);
        $this->line('  both =0         : '.$r->both_zero);
        $this->line('  total           : '.$r->total);

        $this->line('');
        $this->line('Sample transaction_id (group) values:');
        foreach (DB::connection('legacy')->select("SELECT transaction_id, COUNT(*) c FROM transactions GROUP BY transaction_id ORDER BY c DESC LIMIT 10") as $r) {
            $this->line(sprintf('  transaction_id=%-15s count=%s', $r->transaction_id, $r->c));
        }

        return self::SUCCESS;
    }
}
