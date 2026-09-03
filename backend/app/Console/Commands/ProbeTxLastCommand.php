<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxLastCommand extends Command
{
    protected $signature = 'new:probe-tx-last';
    protected $description = 'Cek sample rows terakhir di DB baru';

    public function handle(): int
    {
        $rows = DB::table('transactions')->orderByDesc('id')->limit(8)->get();
        foreach ($rows as $r) {
            $this->line(sprintf('  id=%-6s tgl=%s d=%s k=%s saldo=%s revType=%-25s revId=%s urutan=%s', $r->id, $r->tgl_transaksi, $r->account_debet, $r->account_kredit, $r->saldo, $r->reverence_type ?? 'null', $r->reverence_id ?? '-', $r->urutan));
        }
        $this->line('');
        $this->info('Group by reverence_type:');
        foreach (DB::table('transactions')->selectRaw('reverence_type, COUNT(*) c')->groupBy('reverence_type')->get() as $r) {
            $this->line('  '.($r->reverence_type ?? 'null').' : '.$r->c);
        }
        return self::SUCCESS;
    }
}
