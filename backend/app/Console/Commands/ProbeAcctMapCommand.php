<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeAcctMapCommand extends Command
{
    protected $signature = 'legacy:probe-acct {--id=*}';
    protected $description = 'Lihat mapping legacy account id → kode_akun & nama';

    public function handle(): int
    {
        $ids = $this->option('id');
        if (empty($ids)) $ids = [1, 33, 34, 35, 46, 48, 49, 50, 51, 55, 113, 186, 189, 192, 594, 612, 641, 642, 643, 644, 685, 686, 689];
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::connection('legacy')->select("SELECT id, kode_akun, nama_akun FROM accounts WHERE id IN ($place) ORDER BY id", $ids);
        foreach ($rows as $r) {
            $this->line(sprintf('  %-5s %-12s %s', $r->id, $r->kode_akun, $r->nama_akun));
        }

        $this->line('');
        $this->line('Kode_akun di DB baru (1.1.01.01, 1.1.03.01, 4.1.01.x):');
        foreach (DB::table('accounts')->whereIn('kode_akun', ['1.1.01.01','1.1.03.01','4.1.01.01','4.1.01.02','4.1.01.03','4.1.01.04'])->get() as $r) {
            $this->line(sprintf('  %-12s %s', $r->kode_akun, $r->nama_akun));
        }

        return self::SUCCESS;
    }
}
