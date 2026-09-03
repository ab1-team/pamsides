<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\InstallationTicket;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\SurveyResult;
use App\Models\BillPayment;
use App\Models\Payment;
use App\Models\MonthlyBill;
use App\Models\TroubleReport;
use App\Models\Transaction;

class PurgeSeedUsersCommand extends Command
{
    protected $signature = 'purge:seed-users
                            {--dry-run : Cek apa yang akan dihapus, tanpa ubah data}
                            {--force   : Lewati konfirmasi}';

    protected $description = 'Hapus user hasil seeder (admin/surveyor/teknisi/pelanggan@test) dari DB baru.';

    /** Email user seeder yang boleh auto-hapus */
    private array $seedEmails = [
        'admin@pamsides.test',
        'surveyor@pamsides.test',
        'teknisi@pamsides.test',
        'pelanggan@pamsides.test',
    ];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('=== Pembersihan User Hasil Seeder ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        $this->line('');

        // 1. Cari user target
        $targets = User::all()->filter(function ($u) {
            return in_array(strtolower($u->email), array_map('strtolower', $this->seedEmails), true);
        });

        if ($targets->isEmpty()) {
            $this->warn('User seeder tidak ditemukan. Mungkin sudah dihapus sebelumnya.');
            return self::SUCCESS;
        }

        // 2. Cek referensi di tiap tabel yang ada FK ke users
        $this->info('User yang akan dihapus:');
        foreach ($targets as $u) {
            $refs = $this->countReferences($u->id);
            $this->line(sprintf('  [#%d] %-30s (%s) | %s',
                $u->id, $u->name, $u->email, $this->formatRefs($refs)
            ));
        }

        // 3. Cek apakah ada referensi yang berbahaya (selain nullOnDelete)
        $dangerous = [];
        foreach ($targets as $u) {
            $refs = $this->countReferences($u->id);
            // created_by, surveyor_id, recorded_by, confirmed_by pakai constrained tanpa nullOnDelete
            foreach (['created_by', 'surveyor_id', 'recorded_by', 'confirmed_by'] as $fk) {
                if (! empty($refs[$fk]) && $refs[$fk] > 0) {
                    $dangerous[] = ['user_id' => $u->id, 'fk' => $fk, 'count' => $refs[$fk]];
                }
            }
        }

        if (! empty($dangerous)) {
            $this->line('');
            $this->error('GAGAL: Ada user yang terelasi ke data penting.');
            foreach ($dangerous as $d) {
                $this->line(sprintf('  - User #%d dipakai sebagai %s di %d record',
                    $d['user_id'], $d['fk'], $d['count']));
            }
            return self::FAILURE;
        }

        // 4. Konfirmasi
        if ($isDryRun) {
            $this->line('');
            $this->info('[dry-run] Tidak ada data yang dihapus. Jalankan tanpa --dry-run untuk eksekusi.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Yakin hapus user seeder di atas?', false)) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        // 5. Hapus
        $deleted = 0;
        foreach ($targets as $u) {
            // Hapus tiket draft yang reference (jaga konsistensi)
            InstallationTicket::where('created_by', $u->id)
                ->where('status', 'draft')->delete();
            // Putuskan referensi nullOnDelete (user_id, dll)
            InstallationTicket::where('user_id', $u->id)->update(['user_id' => null]);
            Customer::where('user_id', $u->id)->update(['user_id' => null]);
            $u->delete();
            $deleted++;
        }

        $this->line('');
        $this->info("Selesai: {$deleted} user seeder dihapus.");

        // 6. Tampilkan sisa
        $remaining = User::orderBy('id')->get(['id', 'name', 'email', 'role']);
        $this->line('');
        $this->info('Sisa user di DB baru ('.$remaining->count().'):');
        $this->table(
            ['ID', 'Name', 'Email', 'Role'],
            $remaining->map(fn($u) => [$u->id, $u->name, $u->email, $u->role])->toArray()
        );

        return self::SUCCESS;
    }

    private function countReferences(int $userId): array
    {
        return [
            'created_by'    => InstallationTicket::where('created_by', $userId)->count(),
            'user_id'       => InstallationTicket::where('user_id', $userId)->count()
                            + Customer::where('user_id', $userId)->count()
                            + TroubleReport::where('user_id', $userId)->count(),
            'surveyor_id'   => SurveyResult::where('surveyor_id', $userId)->count(),
            'recorded_by'   => MeterReading::where('recorded_by', $userId)->count(),
            'confirmed_by'  => Payment::where('confirmed_by', $userId)->count()
                            + BillPayment::where('confirmed_by', $userId)->count()
                            + Transaction::where('confirmed_by', $userId)->count(),
        ];
    }

    private function formatRefs(array $refs): string
    {
        $parts = [];
        foreach ($refs as $k => $v) {
            if ($v > 0) $parts[] = "$k=$v";
        }
        return $parts ? implode(', ', $parts) : 'no refs';
    }
}