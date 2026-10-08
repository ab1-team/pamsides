<?php

namespace App\Console\Commands;

use App\Services\LegacyUsageSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeNonLegacyBusinessDataCommand extends Command
{
    protected $signature = 'legacy:purge-non-biz-data
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan penghapusan}
                            {--business=5 : business_id legacy yang DIPERTAHANKAN}';

    protected $description = 'Hapus meter_readings + monthly_bills yang bersumber dari legacy business_id lain (HANYA di DB baru, legacy tidak disentuh)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $keep = (int) $this->option('business');

        $this->info('=== Hapus data bersumber business_id selain '.$keep.' (KHUSUS DB BARU) ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: baris di DB BARU akan dihapus.');

        $newName = DB::connection()->getDatabaseName();
        $newHost = DB::connection()->getConfig('host');
        $legacyHost = DB::connection('legacy')->getConfig('host');
        $this->info('  DB baru   : '.$newHost.' / '.$newName.'   <-- yang diubah');
        $this->info('  DB legacy : '.$legacyHost.' / '.DB::connection('legacy')->getDatabaseName().'   <-- hanya BACA');
        if ($newHost === $legacyHost) {
            $this->error('ABORT: host DB baru dan legacy sama. Berisiko!');

            return self::FAILURE;
        }
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // Tagihan yang valid = punya pasangan legacy usages business yang dipertahankan.
        $valid = [];
        foreach (LegacyUsageSource::collect($keep)['readings'] as $key => $r) {
            $valid[$key] = true;
        }

        $bills = $N->table('monthly_bills')->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month', 'total_amount', 'status']);
        $orphanBills = [];
        $keepCount = 0;
        foreach ($bills as $b) {
            $key = $b->customer_id . '|' . $b->billing_period_year . '|' . $b->billing_period_month;
            if (isset($valid[$key])) {
                $keepCount++;
            } else {
                $orphanBills[] = $b;
            }
        }

        $readings = $N->table('meter_readings')->get(['id', 'customer_id', 'reading_year', 'reading_month', 'meter_value']);
        $orphanReadings = [];
        $keepR = 0;
        foreach ($readings as $r) {
            $key = $r->customer_id . '|' . $r->reading_year . '|' . $r->reading_month;
            if (isset($valid[$key])) {
                $keepR++;
            } else {
                $orphanReadings[] = $r;
            }
        }

        // Klasifikasi asal:_usage legacy business mana?
        $codeByInst = $L->table('installations')->pluck('kode_instalasi', 'id')->all();
        $codeByInstBiz = [];
        foreach ($L->table('installations')->get(['id', 'business_id', 'kode_instalasi']) as $i) {
            $codeByInstBiz[(int) $i->id] = (int) $i->business_id;
        }
        $codeOfInst = $codeByInst;
        $codeByCust = $N->table('customers')->pluck('customer_code', 'id')->all();

        $origin = [];
        foreach ($orphanBills as $b) {
            $code = trim((string) ($codeByCust[$b->customer_id] ?? ''));
            // cari installations biz apa saja dengan kode ini
            $bizs = [];
            foreach ($codeOfInst as $instId => $kode) {
                if (trim((string) $kode) === $code && $code !== '') {
                    $bizs[$codeByInstBiz[(int) $instId] ?? 0] = true;
                }
            }
            $label = empty($bizs) ? 'tidak ada di legacy' : 'legacy biz=' . implode(',', array_keys($bizs));
            $origin[$label] = ($origin[$label] ?? 0) + 1;
        }

        $this->info('--- Analisis tagihan ---');
        $this->line('  monthly_bills total      : ' . count($bills));
        $this->line('  sesuai legacy biz'.$keep.'      : ' . $keepCount);
        $this->line('  tidak sesuai (kandidat hapus) : ' . count($orphanBills));
        foreach ($origin as $k => $v) {
            $this->line("     asal: {$k} -> {$v} tagihan");
        }

        $this->line('');
        $this->info('--- Rincian 15 tagihan yang akan dihapus ---');
        foreach (array_slice($orphanBills, 0, 15) as $b) {
            $code = trim((string) ($codeByCust[$b->customer_id] ?? ''));
            echo sprintf(
                "   bill=%-6d cust=%-5d kode=%-18s %d-%02d Rp %-12s %s\n",
                $b->id, $b->customer_id, $code === '' ? '(kosong)' : $code,
                $b->billing_period_year, $b->billing_period_month, $b->total_amount, $b->status
            );
        }

        // Data turunan
        $billIds = array_map(fn ($b) => (int) $b->id, $orphanBills);
        $custIds = array_values(array_unique(array_map(fn ($b) => (int) $b->customer_id, $orphanBills)));
        $bpCount = $billIds ? $N->table('bill_payments')->whereIn('bill_id', $billIds)->count() : 0;

        $this->line('');
        $this->line('  Yang akan ikut terhapus:');
        $this->line('    meter_readings : ' . count($orphanReadings));
        $this->line('    bill_payments  : ' . $bpCount);
        $this->line('    customers      : 0 (TIDAK dihapus)');
        $this->line('    transactions   : 0 (TIDAK dihapus)');

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Jalankan --force untuk menghapus di DB BARU saja.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->error('Menghapus dari DB BARU ('.$newName.')...');
        $del = ['bill_payments' => 0, 'monthly_bills' => 0, 'meter_readings' => 0];
        $N->transaction(function () use ($N, $billIds, $orphanReadings, &$del) {
            if ($billIds) {
                $del['bill_payments'] = $N->table('bill_payments')->whereIn('bill_id', $billIds)->delete();
                $del['monthly_bills'] = $N->table('monthly_bills')->whereIn('id', $billIds)->delete();
            }
            $rIds = array_map(fn ($r) => (int) $r->id, $orphanReadings);
            foreach (array_chunk($rIds, 400) as $chunk) {
                $del['meter_readings'] += $N->table('meter_readings')->whereIn('id', $chunk)->delete();
            }
        });

        $this->info('Selesai. Dihapus dari DB baru:');
        foreach ($del as $t => $n) {
            $this->line(sprintf('   %-18s %d', $t, $n));
        }
        $this->line('');
        $this->info('DB legacy TIDAK disentuh (koneksi hanya dipakai untuk SELECT).');

        return self::SUCCESS;
    }
}