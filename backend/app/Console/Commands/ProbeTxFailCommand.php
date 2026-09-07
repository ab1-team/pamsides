<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxFailCommand extends Command
{
    protected $signature = 'probe:tx-fail';
    protected $description = 'Probe why transactions batch insert fails';

    public function handle(): int
    {
        $row = DB::connection('legacy')->table('transactions')->orderBy('id')->skip(999)->take(1)->first();
        $this->line('sample row id=' . $row->id);
        $this->line(json_encode($row, JSON_UNESCAPED_UNICODE));

        $accountCode = [];
        foreach (DB::table('accounts')->get(['id', 'kode_akun']) as $a) {
            $accountCode[(int) $a->id] = (string) $a->kode_akun;
        }
        $debet = $accountCode[(int) $row->rekening_debit] ?? 'NULL';
        $kredit = $accountCode[(int) $row->rekening_kredit] ?? 'NULL';
        $this->line('debet=' . $debet . ' kredit=' . $kredit);

        try {
            DB::table('transactions')->insert([
                'tgl_transaksi' => $row->tgl_transaksi,
                'account_debet' => $debet,
                'account_kredit' => $kredit,
                'reverence_type' => null,
                'reverence_id' => null,
                'keterangan_transaksi' => $row->keterangan,
                'relasi' => $row->relasi,
                'saldo' => (float) $row->total,
                'urutan' => (int) $row->urutan,
                'id_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->line('INSERT OK');
        } catch (\Throwable $e) {
            $this->error('INSERT FAIL: ' . $e->getMessage());
        }
        $this->line('count: ' . DB::table('transactions')->count());

        DB::table('transactions')->where('keterangan_transaksi', $row->keterangan)->where('tgl_transaksi', $row->tgl_transaksi)->delete();
        $this->line('cleaned up. count now: ' . DB::table('transactions')->count());
        return self::SUCCESS;
    }
}
