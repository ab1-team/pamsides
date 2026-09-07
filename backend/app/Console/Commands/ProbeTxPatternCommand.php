<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxPatternCommand extends Command
{
    protected $signature = 'probe:tx-pattern';
    protected $description = 'Probe ALL distinct prefixes';

    public function handle(): int
    {
        $sql = "SELECT
                  CASE
                    WHEN keterangan LIKE 'Bayar%' AND keterangan LIKE '%Abodemen%' THEN 'Bayar_Abodemen'
                    WHEN keterangan LIKE 'Bayar%' AND keterangan LIKE '%Pemakaian%' THEN 'Bayar_Pemakaian'
                    WHEN keterangan LIKE 'Bayar%' AND keterangan LIKE '%Denda%' THEN 'Bayar_Denda'
                    WHEN keterangan LIKE 'Bayar%' AND keterangan LIKE '%Air%' THEN 'Bayar_Air'
                    WHEN keterangan LIKE 'Bayar%' THEN 'Bayar_Other'
                    WHEN keterangan LIKE 'Pendapatan%' AND keterangan LIKE '%Abodemen%' THEN 'Pendapatan_Abodemen'
                    WHEN keterangan LIKE 'Pendapatan%' AND (keterangan LIKE '%Pemakaian%' OR keterangan LIKE '%Tagihan%') THEN 'Pendapatan_Pemakaian'
                    WHEN keterangan LIKE 'Pendapatan%' THEN 'Pendapatan_Other'
                    WHEN keterangan LIKE 'Hutang%' AND keterangan LIKE '%Abodemen%' THEN 'Hutang_Abodemen'
                    WHEN keterangan LIKE 'Hutang%' AND (keterangan LIKE '%Pemakaian%' OR keterangan LIKE '%Tagihan%') THEN 'Hutang_Pemakaian'
                    WHEN keterangan LIKE 'Hutang%' AND keterangan LIKE '%Denda%' THEN 'Hutang_Denda'
                    WHEN keterangan LIKE 'Hutang%' THEN 'Hutang_Other'
                    WHEN keterangan LIKE 'Piutang%' AND keterangan LIKE '%Abodemen%' THEN 'Piutang_Abodemen'
                    WHEN keterangan LIKE 'Piutang%' AND (keterangan LIKE '%Pemakaian%' OR keterangan LIKE '%Tagihan%') THEN 'Piutang_Pemakaian'
                    WHEN keterangan LIKE 'Piutang%' AND keterangan LIKE '%Denda%' THEN 'Piutang_Denda'
                    WHEN keterangan LIKE 'Piutang%' THEN 'Piutang_Other'
                    WHEN keterangan LIKE 'Biaya instalasi%' THEN 'BiayaInstalasi'
                    WHEN keterangan LIKE 'Penghapusan%' THEN 'Penghapusan'
                    WHEN keterangan LIKE 'Utang Komisi%' THEN 'UtangKomisi'
                    WHEN keterangan LIKE 'Dividen%' THEN 'Dividen'
                    WHEN keterangan LIKE 'Bonus%' THEN 'Bonus'
                    WHEN keterangan LIKE 'Utang%' AND keterangan LIKE '%Abodemen%' THEN 'Utang_Abodemen'
                    WHEN keterangan LIKE 'Utang%' AND (keterangan LIKE '%Pemakaian%' OR keterangan LIKE '%Air%') THEN 'Utang_Pemakaian'
                    WHEN keterangan LIKE 'Utang%' AND keterangan LIKE '%Denda%' THEN 'Utang_Denda'
                    WHEN keterangan LIKE 'Utang%' THEN 'Utang_Other'
                    ELSE 'Unknown'
                  END AS pattern,
                  COUNT(*) c
                FROM transactions
                GROUP BY pattern
                ORDER BY c DESC";
        $rows = DB::connection('legacy')->select($sql);
        $total = 0;
        foreach ($rows as $r) {
            printf("%-25s %d\n", $r->pattern, $r->c);
            $total += $r->c;
        }
        $this->line("---");
        $this->line("Total: $total");

        $this->info('');
        $this->info('--- Sample Bayar Air (rekening_debit=594) ---');
        $rows = DB::connection('legacy')->select("
            SELECT id, tgl_transaksi, rekening_debit, rekening_kredit, user_id, usage_id, installation_id, total, relasi, LEFT(keterangan, 80) AS ket
            FROM transactions
            WHERE rekening_debit = 594 AND keterangan LIKE '%Bayar%' AND (keterangan LIKE '%Pemakaian%' OR keterangan LIKE '%Tagihan%')
            LIMIT 5
        ");
        foreach ($rows as $r) print_r((array) $r);

        $this->info('');
        $this->info('--- Sample Bayar Abodemen (rekening_debit=594) ---');
        $rows = DB::connection('legacy')->select("
            SELECT id, tgl_transaksi, rekening_debit, rekening_kredit, user_id, usage_id, installation_id, total, relasi, LEFT(keterangan, 80) AS ket
            FROM transactions
            WHERE rekening_debit = 594 AND keterangan LIKE '%Bayar%' AND keterangan LIKE '%Abodemen%'
            LIMIT 5
        ");
        foreach ($rows as $r) print_r((array) $r);

        $this->info('');
        $this->info('--- Sample Bayar Denda (rekening_debit=594) ---');
        $rows = DB::connection('legacy')->select("
            SELECT id, tgl_transaksi, rekening_debit, rekening_kredit, user_id, usage_id, installation_id, total, relasi, LEFT(keterangan, 80) AS ket
            FROM transactions
            WHERE rekening_debit = 594 AND keterangan LIKE '%Bayar%' AND keterangan LIKE '%Denda%'
            LIMIT 5
        ");
        foreach ($rows as $r) print_r((array) $r);

        $this->info('');
        $this->info('--- Min/Max id legacy ---');
        $rows = DB::connection('legacy')->select("SELECT MIN(id) min_id, MAX(id) max_id, COUNT(*) c FROM transactions");
        foreach ($rows as $r) print_r((array) $r);

        return self::SUCCESS;
    }
}
