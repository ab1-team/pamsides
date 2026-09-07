<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\InstallationPackage;
use App\Models\WaterTariffBlock;
use App\Models\InstallationTicket;

class PurgeSeedPackagesCommand extends Command
{
    protected $signature = 'purge:seed-packages
                            {--dry-run : Cek apa yang akan dihapus, tanpa ubah data}
                            {--force   : Lewati konfirmasi}
                            {--keep-tickets : JANGAN hapus tiket draft, hapus paketnya skip}';

    private bool $keepTickets = false;

    protected $description = 'Hapus Paket Reguler & Paket Sosial (hasil seeder) + blok tarif terkait. Aman untuk relasi.';

    /** Nama paket yang akan dihapus (case-insensitive contains) */
    private array $seedNames = ['Paket Reguler', 'Paket Sosial'];

    /** Status tiket yang dianggap aman untuk auto-delete (data dummy) */
    private array $safeStatuses = ['draft'];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $this->keepTickets = (bool) $this->option('keep-tickets');

        $this->info('=== Pembersihan Paket Hasil Seeder ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        if ($this->keepTickets) {
            $this->warn('--keep-tickets: tiket draft TIDAK dihapus otomatis.');
        }
        $this->line('');

        // 1. Cari paket yang akan dihapus (case-insensitive)
        $targets = InstallationPackage::all()->filter(function ($p) {
            foreach ($this->seedNames as $seed) {
                if (strcasecmp($p->name, $seed) === 0) {
                    return true;
                }
            }
            return false;
        });

        if ($targets->isEmpty()) {
            $this->warn('Paket seeder (Reguler/Sosial) tidak ditemukan. Mungkin sudah dihapus sebelumnya.');
            return self::SUCCESS;
        }

        $this->info('Paket yang akan dihapus:');
        foreach ($targets as $p) {
            $ticketCount = InstallationTicket::where('package_id', $p->id)->count();
            $blockCount  = WaterTariffBlock::where('package_id', $p->id)->count();
            $this->line(sprintf(
                '  [#%d] %-25s | %d blok tarif | %d tiket terelasi',
                $p->id, $p->name, $blockCount, $ticketCount
            ));
        }

        // 2. Cek apakah ada tiket yang reference paket ini
        $blockingTickets = collect();
        $blockingTicketDetails = [];
        foreach ($targets as $p) {
            $tickets = InstallationTicket::where('package_id', $p->id)->get();
            foreach ($tickets as $t) {
                $blockingTickets->push($t);
                $blockingTicketDetails[] = [
                    'ticket_id'   => $t->id,
                    'package_id'  => $p->id,
                    'package_name'=> $p->name,
                    'status'      => $t->status,
                    'applicant'   => $t->applicant_name ?? '-',
                ];
            }
        }

        if ($blockingTickets->isNotEmpty()) {
            $safeOnes  = $blockingTickets->whereIn('status', $this->safeStatuses);
            $unsafeOnes = $blockingTickets->whereNotIn('status', $this->safeStatuses);

            $this->line('');
            $this->info('Tiket yang terelasi:');
            foreach ($blockingTicketDetails as $d) {
                $marker = in_array($d['status'], $this->safeStatuses) ? '[AUTO-HAPUS]' : '[TIDAK AMAN]';
                $this->line(sprintf(
                    '  %s Tiket #%d → paket #%d (%s) | status=%s | applicant=%s',
                    $marker, $d['ticket_id'], $d['package_id'], $d['package_name'], $d['status'], $d['applicant']
                ));
            }

            if ($unsafeOnes->isNotEmpty()) {
                $this->line('');
                $this->error('GAGAL: Ada tiket dengan status non-draft yang reference paket ini.');
                $this->line('Hapus/ubah tiket non-draft secara manual dulu sebelum hapus paket.');
                return self::FAILURE;
            }

            if ($this->keepTickets) {
                $this->line('');
                $this->error('GAGAL: Ada tiket draft yang terelasi, dan --keep-tickets aktif.');
                $this->line('Hapus tiket draft secara manual, atau jalankan tanpa --keep-tickets.');
                return self::FAILURE;
            }

            $this->line('');
            $this->warn('Tiket berstatus DRAFT akan ikut dihapus (data dummy).');
        }

        // 3. Konfirmasi (skip kalau dry-run / force)
        if ($isDryRun) {
            $this->line('');
            $this->info('[dry-run] Tidak ada data yang dihapus. Jalankan tanpa --dry-run untuk eksekusi.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Yakin hapus paket di atas beserta blok tarifnya?', false)) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        // 4. Hapus (urutan: tiket draft, lalu blok, lalu paket)
        $deletedTickets = 0;
        $deletedBlocks  = 0;
        $deletedPkgs    = 0;
        DB::transaction(function () use ($targets, &$deletedTickets, &$deletedBlocks, &$deletedPkgs) {
            foreach ($targets as $p) {
                $deletedTickets += InstallationTicket::where('package_id', $p->id)
                    ->whereIn('status', $this->safeStatuses)
                    ->delete();
                $deletedBlocks  += WaterTariffBlock::where('package_id', $p->id)->delete();
                $p->delete();
                $deletedPkgs++;
            }
        });

        $this->line('');
        $this->info("Selesai: {$deletedPkgs} paket, {$deletedBlocks} blok, {$deletedTickets} tiket draft dihapus.");

        // 5. Tampilkan sisa
        $this->line('');
        $remaining = InstallationPackage::orderBy('id')->get();
        $this->info('Sisa paket di DB baru ('.$remaining->count().'):');
        foreach ($remaining as $p) {
            $this->line('  #' . $p->id . ' ' . $p->name);
        }

        return self::SUCCESS;
    }
}