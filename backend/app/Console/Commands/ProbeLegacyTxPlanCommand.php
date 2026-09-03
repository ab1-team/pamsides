<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeLegacyTxPlanCommand extends Command
{
    protected $signature = 'legacy:probe-tx-plan';
    protected $description = 'Hitung rencana mapping (rekening) ke reverence_type untuk transaksi legacy';

    public function handle(): int
    {
        // klasifikasi pola: (debet, kredit) → reverence_type
        // dari kode_akun, ambil id dari legacy
        $accByCode = [];
        foreach (DB::connection('legacy')->table('accounts')->get(['id', 'kode_akun']) as $a) {
            $accByCode[(string) $a->kode_akun] = (int) $a->id;
        }

        $kas = $accByCode['1.1.01.01'] ?? null;
        $piutang = $accByCode['1.1.03.01'] ?? null;
        $pendapatanList = array_filter(array_map(fn($c) => $accByCode[$c] ?? null, ['4.1.01.01','4.1.01.02','4.1.01.03','4.1.01.04']));

        $this->line("kas id={$kas}, piutang id={$piutang}");
        $this->line('pendapatan ids: '.implode(',', array_values($pendapatanList)));

        $rows = DB::connection('legacy')->select("
            SELECT rekening_debit d, rekening_kredit k, COUNT(*) c
            FROM transactions
            GROUP BY rekening_debit, rekening_kredit
            ORDER BY c DESC
        ");

        $plan = ['monthly_bill' => 0, 'overdue_bill' => 0, 'bill_payment' => 0, 'payment' => 0, 'other' => 0];
        foreach ($rows as $r) {
            $d = (int) $r->d; $k = (int) $r->k;
            $tag = null;
            if ($d === $kas && in_array($k, $pendapatanList, true)) {
                // kas → pendapatan: ini jurnal pendapatan bulanan (saat bill dibuat)
                $tag = 'monthly_bill';
            } elseif ($d === $piutang && in_array($k, $pendapatanList, true)) {
                // piutang → pendapatan: jurnal piutang tunggakan
                $tag = 'overdue_bill';
            } elseif ($d === $kas && $k === $piutang) {
                // kas → piutang: pelunasan
                $tag = 'bill_payment';
            } elseif ($d === $kas && $k === $accByCode['4.1.01.01'] && $r->c > 0) {
                // kas → pasang baru (install payment) — tapi ini sama dengan monthly_bill kalau pendapatan
                $tag = 'payment';
            } else {
                $tag = 'other';
            }
            $plan[$tag] += $r->c;
            $this->line(sprintf('  d=%-5s k=%-5s c=%-6s  → %s', $d, $k, $r->c, $tag));
        }

        $this->line('');
        $this->line('RENCANA DISTRIBUSI:');
        foreach ($plan as $k => $v) $this->line("  $k : $v");
        $this->line('  TOTAL : '.array_sum($plan));

        // cek payment-specific (d=1 → 48 pasang baru)
        $this->line('');
        $this->line('Sample d=1 (kas lama) transactions:');
        foreach (DB::connection('legacy')->table('transactions')->where('rekening_debit', 1)->limit(5)->get() as $t) {
            $this->line('  id='.$t->id.' d='.$t->rekening_debit.' k='.$t->rekening_kredit.' usage='.$t->usage_id.' inst='.$t->installation_id.' ket='.mb_substr((string)$t->keterangan, 0, 60));
        }

        return self::SUCCESS;
    }
}
