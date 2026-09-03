<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTransactionsCategoryCommand extends Command
{
    protected $signature = 'probe:tx-category';
    protected $description = 'Probe distribution legacy transactions by keterangan pattern';

    public function handle(): int
    {
        $this->info('--- Rekening yg dipakai legacy (top 30) ---');
        $rows = DB::connection('legacy')->select("
            SELECT rekening_debit AS r, COUNT(*) AS c
            FROM transactions
            GROUP BY rekening_debit
            ORDER BY c DESC LIMIT 30
        ");
        foreach ($rows as $r) printf("d=%-5d  %d\n", $r->r, $r->c);

        $this->info('');
        $this->info('--- Rekening KREDIT (top 30) ---');
        $rows = DB::connection('legacy')->select("
            SELECT rekening_kredit AS r, COUNT(*) AS c
            FROM transactions
            GROUP BY rekening_kredit
            ORDER BY c DESC LIMIT 30
        ");
        foreach ($rows as $r) printf("k=%-5d  %d\n", $r->r, $r->c);

        $this->info('');
        $this->info('--- Map ke kode_akun di DB baru ---');
        $rows = DB::connection('legacy')->select("
            SELECT t.rekening_debit, a.kode_akun, t.keterangan_pattern, c
            FROM (
                SELECT rekening_debit, CASE
                    WHEN keterangan LIKE 'Bayar%' THEN 'Bayar'
                    WHEN keterangan LIKE 'Pendapatan%' THEN 'Pendapatan'
                    WHEN keterangan LIKE 'Hutang%' THEN 'Hutang'
                    WHEN keterangan LIKE 'Piutang%' THEN 'Piutang'
                    WHEN keterangan LIKE 'Biaya instalasi%' THEN 'Biaya_instalasi'
                    WHEN keterangan LIKE 'Penghapusan%' THEN 'Penghapusan'
                    WHEN keterangan LIKE 'Utang Komisi%' THEN 'Utang_komisi'
                    WHEN keterangan LIKE 'Dividen%' THEN 'Dividen'
                    WHEN keterangan LIKE 'Bonus%' THEN 'Bonus'
                    ELSE 'Other'
                END AS keterangan_pattern,
                COUNT(*) c
                FROM transactions
                GROUP BY rekening_debit, keterangan_pattern
            ) t
            LEFT JOIN accounts a ON a.id = t.rekening_debit
            ORDER BY c DESC LIMIT 60
        ");
        foreach ($rows as $r) {
            printf("d=%-5d akun=%-12s ket=%-22s %d\n",
                $r->rekening_debit, $r->kode_akun ?? '?', $r->keterangan_pattern, $r->c);
        }

        $this->info('');
        $this->info('--- Count per keterangan_pattern ---');
        $rows = DB::connection('legacy')->select("
            SELECT CASE
                WHEN keterangan LIKE 'Bayar%' THEN 'Bayar'
                WHEN keterangan LIKE 'Pendapatan%' THEN 'Pendapatan'
                WHEN keterangan LIKE 'Hutang%' THEN 'Hutang'
                WHEN keterangan LIKE 'Piutang%' THEN 'Piutang'
                WHEN keterangan LIKE 'Biaya instalasi%' THEN 'Biaya_instalasi'
                WHEN keterangan LIKE 'Penghapusan%' THEN 'Penghapusan'
                WHEN keterangan LIKE 'Utang Komisi%' THEN 'Utang_komisi'
                WHEN keterangan LIKE 'Dividen%' THEN 'Dividen'
                WHEN keterangan LIKE 'Bonus%' THEN 'Bonus'
                ELSE 'Other'
            END AS pattern,
            COUNT(*) c
            FROM transactions
            GROUP BY pattern
            ORDER BY c DESC
        ");
        foreach ($rows as $r) printf("%-22s %d\n", $r->pattern, $r->c);

        return self::SUCCESS;
    }
}
