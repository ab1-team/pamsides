<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Village;
use App\Models\InstallationTicket;

class ImportLegacyVillagesCommand extends Command
{
    protected $signature = 'import:villages
                            {--dry-run : Cetak rencana import, tanpa ubah data}
                            {--force   : Lewati konfirmasi}';

    protected $description = 'Import data villages dari DB lama (tabel villages) → villages di DB baru';

    /** Status DB baru yang boleh auto-clean (ga dipake tiket) */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('=== Import Villages: DB Lama → DB Baru ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        $this->line('');

        // 1. Ambil semua village di DB lama
        $legacyRows = DB::connection('legacy')->table('villages')->orderBy('id')->get();
        $this->info('Village di DB lama: '.$legacyRows->count());

        // 2. Cek apakah ada village di DB baru yang reference dari tiket
        $existing = Village::all()->keyBy('id');
        $blockingTickets = 0;
        foreach ($existing as $v) {
            $blockingTickets += InstallationTicket::where('village_id', $v->id)->count();
        }
        if ($blockingTickets > 0) {
            $this->error("Ada {$blockingTickets} tiket yang reference village di DB baru. Import ditolak demi keamanan.");
            return self::FAILURE;
        }

        $created = 0; $updated = 0; $skipped = 0; $errors = [];

        $bar = $this->output->createProgressBar($legacyRows->count());
        $bar->start();

        foreach ($legacyRows as $row) {
            try {
                $newData = [
                    'village_name' => trim((string) ($row->nama ?? '')) ?: null,
                    'hamlet_name'  => $this->hamletFallback($row->dusun ?? null, $row->nama ?? null),
                    'address'      => $this->cleanText($row->alamat ?? null),
                    'phone'        => $this->cleanPhone($row->hp ?? null),
                ];

                // Skip baris kosong total (semua field null/kosong)
                if ($newData['village_name'] === null
                    && $newData['hamlet_name'] === null
                    && $newData['address'] === null
                    && $newData['phone'] === null) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if ($isDryRun) {
                    $exists = $existing->has($row->id);
                    $action = $exists ? 'UPDATE' : 'CREATE';
                    $this->line(sprintf(
                        "\n  [#%d] %s | village=%s | hamlet=%s | phone=%s",
                        $row->id,
                        $action,
                        $newData['village_name'] ?? '(null)',
                        $newData['hamlet_name'] ?? '(null)',
                        $newData['phone'] ?? '(null)'
                    ));
                    $bar->advance();
                    continue;
                }

                // updateOrCreate by ID (idempotent, ga bikin duplikat kalau di-run ulang)
                $village = Village::updateOrCreate(
                    ['id' => $row->id],
                    $newData
                );
                $village->wasRecentlyCreated ? $created++ : $updated++;
                $bar->advance();
            } catch (\Throwable $e) {
                $errors[] = [
                    'legacy_id' => $row->id,
                    'nama'      => $row->nama ?? '-',
                    'error'     => $e->getMessage(),
                ];
                Log::error('Import village gagal', [
                    'legacy_id' => $row->id,
                    'error'     => $e->getMessage(),
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->line(''); $this->line('');
        $this->info('Ringkasan:');
        $this->line("  - Dibuat   : {$created}");
        $this->line("  - Diupdate : {$updated}");
        $this->line("  - Dilewati (kosong) : {$skipped}");
        $this->line('  - Gagal    : '.count($errors));

        if (! empty($errors)) {
            $this->line('');
            $this->warn('Detail error:');
            foreach ($errors as $err) {
                $this->line(sprintf('  - legacy_id=%s nama=%s | %s', $err['legacy_id'], $err['nama'], $err['error']));
            }
        }

        // Tampilkan sample hasil
        $this->line('');
        $this->info('Sample villages DB baru (5 teratas):');
        $sample = Village::orderBy('id')->limit(5)->get();
        foreach ($sample as $v) {
            $this->line(sprintf('  [#%d] %s | hamlet=%s | phone=%s',
                $v->id,
                $v->village_name ?? '(null)',
                $v->hamlet_name ?? '(null)',
                $v->phone ?? '(null)'
            ));
        }

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    /** Bersihkan teks: null/kosong/dash jadi null */
    private function cleanText($val): ?string
    {
        if ($val === null) return null;
        $val = trim((string) $val);
        if ($val === '' || $val === '-') return null;
        return $val;
    }

    /** Bersihkan nomor telepon: '0' atau non-numeric jadi null */
    private function cleanPhone($val): ?string
    {
        if ($val === null) return null;
        $val = trim((string) $val);
        if ($val === '' || $val === '0') return null;
        // ambil hanya digit
        $digits = preg_replace('/[^0-9]/', '', $val);
        return $digits !== '' ? $digits : null;
    }

    /**
     * DB baru: hamlet_name NOT NULL.
     * Kalau legacy null/kosong, fallback ke nama desa (atau '-' kalau nama juga kosong).
     */
    private function hamletFallback($dusun, $nama): string
    {
        $dusun = $this->cleanText($dusun);
        if ($dusun !== null) {
            return $dusun;
        }
        $nama = trim((string) ($nama ?? ''));
        return $nama !== '' ? $nama : '-';
    }
}