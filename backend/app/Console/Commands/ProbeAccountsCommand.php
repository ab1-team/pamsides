<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeAccountsCommand extends Command
{
    protected $signature = 'probe:accounts {--ids= : comma-separated ids}';
    protected $description = 'Probe legacy accounts by ids';

    public function handle(): int
    {
        $ids = $this->option('ids') ?: '1,48,49,50,51,113,186,192,594,605,612,641,642,643,644,685,686,689';
        $idsArr = array_map('intval', explode(',', $ids));
        $placeholders = implode(',', array_fill(0, count($idsArr), '?'));
        $rows = DB::connection('legacy')->select("SELECT id, kode_akun, nama_akun FROM accounts WHERE id IN ($placeholders) ORDER BY id", $idsArr);
        foreach ($rows as $r) {
            printf("id=%-4d  kode=%-12s nama=%s\n", $r->id, $r->kode_akun, $r->nama_akun);
        }

        $this->info('');
        $this->info('--- All rekening_debit distinct (mapped to kode_akun) ---');
        $rows = DB::connection('legacy')->select('SELECT DISTINCT rekening_debit FROM transactions ORDER BY rekening_debit');
        foreach ($rows as $r) {
            $acc = DB::connection('legacy')->selectOne('SELECT id, kode_akun, nama_akun FROM accounts WHERE id = ?', [$r->rekening_debit]);
            printf("d=%-5d  %s\n", $r->rekening_debit, $acc ? "{$acc->kode_akun} = {$acc->nama_akun}" : '?');
        }

        $this->info('');
        $this->info('--- All rekening_kredit distinct ---');
        $rows = DB::connection('legacy')->select('SELECT DISTINCT rekening_kredit FROM transactions ORDER BY rekening_kredit');
        foreach ($rows as $r) {
            $acc = DB::connection('legacy')->selectOne('SELECT id, kode_akun, nama_akun FROM accounts WHERE id = ?', [$r->rekening_kredit]);
            printf("k=%-5d  %s\n", $r->rekening_kredit, $acc ? "{$acc->kode_akun} = {$acc->nama_akun}" : '?');
        }
        return self::SUCCESS;
    }
}
