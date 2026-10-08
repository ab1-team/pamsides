<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOrphanTransactionRefsCommand extends Command
{
    protected $signature = 'legacy:fix-orphan-tx-refs
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perbaikan}';

    protected $description = 'Kosongkan reverence_id pada transactions yang menunjuk baris monthly_bills / bill_payments yang sudah dihapus';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }

        $this->info('=== Perbaiki reverence_id yang menunjuk baris hilang ===');
        $this->warn($dry ? 'MODE DRY-RUN.' : 'MODE FORCE.');
        $this->line('');

        $N = DB::connection();

        $billIds = $N->table('monthly_bills')->pluck('id')->flip()->all();
        $payIds = $N->table('bill_payments')->pluck('id')->flip()->all();
        $monthlyBills = $N->table('monthly_bills')->count();
        $billPayments = $N->table('bill_payments')->count();
        $this->line("  monthly_bills : {$monthlyBills}");
        $this->line("  bill_payments : {$billPayments}");

        $fix = [];
        foreach ($N->table('transactions')->whereNotNull('reverence_id')->get(['id', 'reverence_type', 'reverence_id', 'keterangan_transaksi']) as $t) {
            $bad = false;
            if (in_array($t->reverence_type, ['monthly_bill', 'overdue_bill'], true)) {
                $bad = ! isset($billIds[(int) $t->reverence_id]);
            } elseif ($t->reverence_type === 'bill_payment') {
                $bad = ! isset($payIds[(int) $t->reverence_id]);
            } elseif ($t->reverence_type === 'payment') {
                $bad = ! $N->table('payments')->where('id', $t->reverence_id)->exists();
            }
            if ($bad) {
                $fix[] = ['id' => (int) $t->id, 'type' => (string) $t->reverence_type, 'ref' => (int) $t->reverence_id,
                    'ket' => mb_substr((string) $t->keterangan_transaksi, 0, 46)];
            }
        }

        $this->line('  reverence_id rusak : ' . count($fix));
        foreach ($fix as $f) {
            $this->line(sprintf('    trx=%-6s type=%-14s ref=%-6s %s', $f['id'], $f['type'], $f['ref'], $f['ket']));
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN. Jalankan --force untuk mengosongkan.');

            return self::SUCCESS;
        }

        if ($fix) {
            $ids = array_column($fix, 'id');
            foreach (array_chunk($ids, 400) as $chunk) {
                $N->table('transactions')->whereIn('id', $chunk)->update(['reverence_id' => null, 'updated_at' => now()]);
            }
        }

        $this->line('');
        $this->info('Selesai. Diperbaiki: ' . count($fix));

        return self::SUCCESS;
    }
}