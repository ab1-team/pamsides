<?php

namespace App\Console\Commands;

use App\Services\LegacyTicketAligner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeUnusedCustomerUsersCommand extends Command
{
    protected $signature = 'legacy:purge-unused-users
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan penghapusan}
                            {--business=5 : business_id legacy yang jadi acuan}';

    protected $description = 'Hapus user role=pelanggan yang tidak dipakai customers manapun (sisa import lama)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');

        $this->info('=== Bersihkan user pelanggan tidak terpakai ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: user akan dihapus.');
        $this->line('');

        $L = DB::connection('legacy');
        $N = DB::connection();

        // Proteksi 1: nama user yang masih dibutuhkan pelanggan legacy
        // yang belum punya customers di DB baru.
        $existingCodes = $N->table('customers')->pluck('customer_code')
            ->map(fn ($c) => trim((string) $c))->flip()->all();
        $neededNames = [];
        $pendingLegacy = $L->table('installations')->where('business_id', $biz)->orderBy('id')->get();
        foreach ($pendingLegacy as $i) {
            if (isset($existingCodes[trim((string) $i->kode_instalasi)])) {
                continue;
            }
            $c = $L->table('customers')->where('id', $i->customer_id)->first();
            if ($c && trim((string) $c->nama) !== '') {
                $neededNames[strtolower(trim((string) $c->nama))] = true;
            }
        }
        $this->info('Nama pelanggan legacy yang masih butuh customers: '.count($neededNames));
        $this->line('');

        // Kandidat: user role=pelanggan tanpa customers
        $usedByCustomer = $N->table('customers')->pluck('user_id')->unique()->filter()
            ->map(fn ($x) => (int) $x)->all();
        $allPelanggan = $N->table('users')->where('role', 'pelanggan')->orderBy('id')->get();

        $candidates = [];
        $protected = [];
        foreach ($allPelanggan as $u) {
            if (in_array((int) $u->id, $usedByCustomer, true)) {
                continue;
            }
            $key = strtolower(trim((string) $u->name));
            if (isset($neededNames[$key])) {
                $protected[] = $u;

                continue;
            }
            // Proteksi 2: user yang dipakai table lain (selain customers).
            // monthly_bills tidak punya kolom user, jadi tidak diperiksa.
            $usedElsewhere = $N->table('installation_tickets')->where('user_id', $u->id)->count()
                + $N->table('installation_tickets')->where('created_by', $u->id)->count()
                + $N->table('bill_payments')->where('confirmed_by', $u->id)->count()
                + $N->table('payments')->where('confirmed_by', $u->id)->count()
                + $N->table('meter_readings')->where('recorded_by', $u->id)->count()
                + $N->table('transactions')->where('id_user', $u->id)->count()
                + $N->table('transactions')->where('penerima_komisi_id', $u->id)->count()
                + $N->table('survey_results')->where('surveyor_id', $u->id)->count()
                + $N->table('trouble_reports')->where('user_id', $u->id)->count()
                + $N->table('trouble_reports')->where('handled_by', $u->id)->count()
                + $N->table('installation_ticket_histories')->where('changed_by', $u->id)->count();
            if ($usedElsewhere > 0) {
                $protected[] = $u;

                continue;
            }
            $candidates[] = $u;
        }

        $this->line(sprintf(
            'user role=pelanggan total      : %d',
            $allPelanggan->count()
        ));
        $this->line(sprintf(
            'dipakai customers              : %d',
            count($usedByCustomer)
        ));
        $this->line(sprintf('Akan dihapus                   : %d', count($candidates)));
        $this->line(sprintf(
            'Dipertahankan (dipakai / butuh): %d',
            count($protected)
        ));
        $this->line('');

        if (count($protected)) {
            $this->info('Contoh yang DIpertahankan:');
            foreach (array_slice($protected, 0, 10) as $u) {
                echo sprintf("   id=%-6d %-34s '%s'\n", $u->id, $u->email, $u->name);
            }
            $this->line('');
        }

        $this->info('Contoh yang akan dihapus:');
        foreach (array_slice($candidates, 0, 10) as $u) {
            echo sprintf("   id=%-6d %-34s '%s'\n", $u->id, $u->email, $u->name);
        }
        $this->line('');

        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menghapus.');

            return self::SUCCESS;
        }

        $this->error('Menghapus...');
        $deleted = 0;
        $N->transaction(function () use ($N, $candidates, &$deleted) {
            foreach (array_chunk($candidates, 500) as $chunk) {
                $ids = array_map(fn ($u) => (int) $u->id, $chunk);
                $N->table('personal_access_tokens')->whereIn('tokenable_id', $ids)
                    ->where('tokenable_type', 'App\\Models\\User')->delete();
                $deleted += $N->table('users')->whereIn('id', $ids)->delete();
            }
        });

        $this->info('Selesai. User dihapus: '.$deleted);

        return self::SUCCESS;
    }
}