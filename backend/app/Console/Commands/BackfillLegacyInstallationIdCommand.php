<?php

namespace App\Console\Commands;

use App\Support\LegacyIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi `installation_tickets.legacy_installation_id` untuk tiket LAMA yang
 * belum punya (hasil migrasi sebelum kolom ini ada).
 *
 * Cara pencocokan (konservatif -- tidak menebak):
 *   1. Ambil `customers.customer_code` di DB baru (harus sama dengan
 *      `installations.kode_instalasi` legacy untuk baris hasil migrasi).
 *   2. Cocokkan ke `installations` legacy pada business yang sama lewat
 *      `kode_instalasi` yang UNIK (kalau satu kode dipakai >1 instalasi, tidak
 *      bisa dipastikan -> biarkan null).
 *   3. Set `legacy_installation_id` bila cocok eksak dan belum terisi.
 *
 * Kalau tidak ada bukti pasti, kolom dibiarkan NULL dan jumlahnya DILAPORKAN
 * di akhir. Command ini idempotent.
 */
class BackfillLegacyInstallationIdCommand extends Command
{
    protected $signature = 'import:backfill-legacy-installation-id
                            {--dry-run : Simulasi, tidak menulis}
                            {--force   : Terapkan perubahan}
                            {--business=5 : Filter business_id di DB legacy}';

    protected $description = 'Backfill installation_tickets.legacy_installation_id dari customers.customer_code ↔ legacy installations.kode_instalasi';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! $dry && ! $force) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('installation_tickets', 'legacy_installation_id')) {
            $this->error('Kolom legacy_installation_id belum ada. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $biz = (int) $this->option('business');
        $this->warn($dry ? '>>> MODE DRY-RUN: tidak ada perubahan <<<' : '>>> MODE FORCE: menulis legacy_installation_id <<<');
        $this->info("Filter business legacy: {$biz}");

        // 1. Kode instalasi legacy yang UNIK di business ini (kode duplikat = ambigu).
        $legacyByKode = [];
        $duplicateKode = [];
        foreach (DB::connection('legacy')->table('installations')
            ->where('business_id', $biz)
            ->orderBy('id')
            ->get(['id', 'kode_instalasi']) as $inst) {
            $kode = trim((string) $inst->kode_instalasi);
            if ($kode === '' || $kode === '0') {
                continue;
            }
            if (isset($legacyByKode[$kode])) {
                $duplicateKode[$kode] = true;
                unset($legacyByKode[$kode]);
                continue;
            }
            if (! isset($duplicateKode[$kode])) {
                $legacyByKode[$kode] = (int) $inst->id;
            }
        }
        $this->info(sprintf('Kode instalasi legacy unik: %d (duplikat/ambigu: %d)', count($legacyByKode), count($duplicateKode)));

        // 2. Tiket baru tanpa legacy_installation_id, ambil customer_code-nya.
        $rows = DB::table('installation_tickets AS t')
            ->leftJoin('customers AS c', 'c.ticket_id', '=', 't.id')
            ->whereNull('t.legacy_installation_id')
            ->orderBy('t.id')
            ->get(['t.id', 'c.customer_code']);

        $stats = ['matched' => 0, 'no_customer' => 0, 'no_match' => 0, 'ambiguous' => 0];
        $updated = 0;

        foreach ($rows as $row) {
            $code = trim((string) ($row->customer_code ?? ''));
            if ($code === '') {
                $stats['no_customer']++;
                continue;
            }
            if (isset($duplicateKode[$code])) {
                $stats['ambiguous']++;
                continue;
            }
            if (! isset($legacyByKode[$code])) {
                $stats['no_match']++;
                continue;
            }

            $stats['matched']++;
            if (! $dry) {
                DB::table('installation_tickets')
                    ->where('id', $row->id)
                    ->whereNull('legacy_installation_id')
                    ->update(['legacy_installation_id' => $legacyByKode[$code]]);
                $updated++;
            }
        }

        $this->newLine();
        $this->info('Ringkasan backfill:');
        $this->line(sprintf('  tiket tanpa legacy_installation_id diperiksa : %d', count($rows)));
        $this->line(sprintf('  cocok pasti (dapat legacy id)                : %d', $stats['matched']));
        $this->line(sprintf('  tidak punya baris pelanggan                  : %d', $stats['no_customer']));
        $this->line(sprintf('  kode tidak ketemu di legacy                  : %d', $stats['no_match']));
        $this->line(sprintf('  kode ambigu (dipakai >1 instalasi legacy)    : %d', $stats['ambiguous']));
        if (! $dry) {
            $this->line(sprintf('  baris ter-update                             : %d', $updated));
        }

        $unresolved = $stats['no_customer'] + $stats['no_match'] + $stats['ambiguous'];
        $this->newLine();
        if ($unresolved > 0) {
            $this->warn(sprintf('DIBIARKAN NULL (tidak ditebak): %d tiket. Perlu penanganan manual/importer pelanggan.', $unresolved));
        } else {
            $this->info('Semua tiket berhasil dicocokkan dengan pasti.');
        }

        return self::SUCCESS;
    }
}
