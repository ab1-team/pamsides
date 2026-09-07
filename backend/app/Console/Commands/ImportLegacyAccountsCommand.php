<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyAccountsCommand extends Command
{
    protected $signature = 'import:accounts
                            {--dry-run : Simulasi}
                            {--force   : Truncate + insert beneran}
                            {--business= : Filter business_id di DB lama (kosong=semua)}';

    protected $description = 'Import legacy akun_level_1/2/3 + accounts + jenis_transactions ke DB baru.';

    public function handle(): int
    {
        $isDryRun = ! $this->option('force');
        $bizFilter = $this->option('business');

        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        if ($bizFilter) $this->info("Filter accounts: hanya business_id={$bizFilter} di DB lama.");

        if (! $isDryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transactions')->truncate();
            DB::table('accounts')->truncate();
            DB::table('akun_level_3')->truncate();
            DB::table('akun_level_2')->truncate();
            DB::table('akun_level_1')->truncate();
            DB::table('jenis_transactions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // 1. akun_level_1
        $this->info('Import akun_level_1...');
        $rows = DB::connection('legacy')->table('akun_level_1')->get();
        $buf = [];
        foreach ($rows as $r) {
            $buf[] = [
                'id'           => (int) $r->id,
                'lev1'         => (int) $r->lev1,
                'lev2'         => (int) $r->lev2,
                'lev3'         => (int) $r->lev3,
                'lev4'         => (int) $r->lev4,
                'kode_akun'    => (string) $r->kode_akun,
                'nama_akun'    => (string) $r->nama_akun,
                'jenis_mutasi' => (string) $r->jenis_mutasi,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        $this->line('  rows: '.count($buf));
        if (! $isDryRun && ! empty($buf)) DB::table('akun_level_1')->insert($buf);

        // 2. akun_level_2
        $this->info('Import akun_level_2...');
        $rows = DB::connection('legacy')->table('akun_level_2')->get();
        $buf = [];
        foreach ($rows as $r) {
            $buf[] = [
                'id'           => (int) $r->id,
                'parent_id'    => (int) $r->parent_id,
                'lev1'         => (int) $r->lev1,
                'lev2'         => (int) $r->lev2,
                'lev3'         => (int) $r->lev3,
                'lev4'         => (int) $r->lev4,
                'kode_akun'    => (string) $r->kode_akun,
                'nama_akun'    => (string) $r->nama_akun,
                'jenis_mutasi' => (string) $r->jenis_mutasi,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        $this->line('  rows: '.count($buf));
        if (! $isDryRun && ! empty($buf)) DB::table('akun_level_2')->insert($buf);

        // 3. akun_level_3
        $this->info('Import akun_level_3...');
        $rows = DB::connection('legacy')->table('akun_level_3')->get();
        $buf = [];
        foreach ($rows as $r) {
            $buf[] = [
                'id'           => (int) $r->id,
                'parent_id'    => (int) $r->parent_id,
                'lev1'         => (int) $r->lev1,
                'lev2'         => (int) $r->lev2,
                'lev3'         => (int) $r->lev3,
                'lev4'         => (int) $r->lev4,
                'kode_akun'    => (string) $r->kode_akun,
                'nama_akun'    => (string) $r->nama_akun,
                'posisi'       => (int) ($r->posisi ?? 1),
                'jenis_mutasi' => (string) $r->jenis_mutasi,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        $this->line('  rows: '.count($buf));
        if (! $isDryRun && ! empty($buf)) DB::table('akun_level_3')->insert($buf);

        // 4. accounts (filter by business_id)
        $this->info('Import accounts...');
        $q = DB::connection('legacy')->table('accounts');
        if ($bizFilter) $q->where('business_id', (int) $bizFilter);
        $rows = $q->get();
        $buf = [];
        foreach ($rows as $r) {
            $buf[] = [
                'id'           => (int) $r->id,
                'parent_id'    => (int) $r->parent_id,
                'lev1'         => (int) $r->lev1,
                'lev2'         => (int) $r->lev2,
                'lev3'         => (int) $r->lev3,
                'lev4'         => (int) $r->lev4,
                'kode_akun'    => (string) $r->kode_akun,
                'nama_akun'    => (string) ($r->nama_akun ?? ''),
                'jenis_mutasi' => (string) ($r->jenis_mutasi ?? ''),
                'tgl_nonaktif' => $r->tgl_nonaktif,
                'created_at'   => $r->created_at ?: now(),
                'updated_at'   => $r->updated_at ?: now(),
            ];
        }
        $this->line('  rows: '.count($buf));
        if (! $isDryRun && ! empty($buf)) DB::table('accounts')->insert($buf);

        // 5. jenis_transactions
        $this->info('Import jenis_transactions...');
        $rows = DB::connection('legacy')->table('jenis_transactions')->get();
        $buf = [];
        foreach ($rows as $r) {
            $buf[] = [
                'id'         => (int) $r->id,
                'nama_jt'    => (string) $r->nama_jt,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->line('  rows: '.count($buf));
        if (! $isDryRun && ! empty($buf)) DB::table('jenis_transactions')->insert($buf);

        $this->line('');
        $this->info('Verifikasi:');
        $this->line('  akun_level_1: '.DB::table('akun_level_1')->count());
        $this->line('  akun_level_2: '.DB::table('akun_level_2')->count());
        $this->line('  akun_level_3: '.DB::table('akun_level_3')->count());
        $this->line('  accounts: '.DB::table('accounts')->count());
        $this->line('  jenis_transactions: '.DB::table('jenis_transactions')->count());

        return self::SUCCESS;
    }
}
