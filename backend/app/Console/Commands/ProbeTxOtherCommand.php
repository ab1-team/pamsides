<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxOtherCommand extends Command
{
    protected $signature = 'probe:tx-other';
    protected $description = 'Probe Other pattern';

    public function handle(): int
    {
        $sql = "SELECT LEFT(keterangan, 100) AS ket, COUNT(*) AS c
                FROM transactions
                WHERE keterangan NOT LIKE 'Bayar%'
                  AND keterangan NOT LIKE 'Pendapatan%'
                  AND keterangan NOT LIKE 'Hutang%'
                  AND keterangan NOT LIKE 'Piutang%'
                  AND keterangan NOT LIKE 'Biaya instalasi%'
                  AND keterangan NOT LIKE 'Penghapusan%'
                  AND keterangan NOT LIKE 'Utang Komisi%'
                  AND keterangan NOT LIKE 'Dividen%'
                  AND keterangan NOT LIKE 'Bonus%'
                GROUP BY LEFT(keterangan, 100)
                ORDER BY c DESC LIMIT 25";
        $rows = DB::connection('legacy')->select($sql);
        foreach ($rows as $r) {
            printf("%-105s %d\n", $r->ket, $r->c);
        }
        return self::SUCCESS;
    }
}
