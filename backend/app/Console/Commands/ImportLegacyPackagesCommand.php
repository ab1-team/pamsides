<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InstallationPackage;
use App\Models\WaterTariffBlock;

class ImportLegacyPackagesCommand extends Command
{
    protected $signature = 'import:packages
                            {--dry-run   : Cetak rencana import, tanpa ubah data}
                            {--business= : Filter business_id tertentu (mis. 1 / 2 / 5)}
                            {--reset     : Hapus SEMUA paket & blok tarif di DB baru sebelum import}
                            {--force     : Lewati prompt konfirmasi (untuk script otomatis)}';

    protected $description = 'Import data paket dari DB lama (packages + settings.block) → installation_packages + water_tariff_blocks';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $bizFilter = $this->option('business');

        $this->info('=== Import Paket: DB Lama → DB Baru ===');
        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: tidak ada perubahan data.');
        }
        if ($bizFilter) {
            $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");
        }
        $this->line('');

        // 1. Ambil settings per business_id (sumber: blok range)
        $settingsRows = DB::connection('legacy')->table('settings')->get();
        $settingsByBiz = [];
        foreach ($settingsRows as $s) {
            $blockArr = is_string($s->block) ? (json_decode($s->block, true) ?: []) : (array) $s->block;
            $settingsByBiz[(int) $s->business_id] = [
                'pasang_baru'     => (float) ($s->pasang_baru ?? 0),
                'blocks'          => $blockArr,
                'tanggal_toleransi' => (int) ($s->tanggal_toleransi ?? 0),
                'batas_tagihan'   => (int) ($s->batas_tagihan ?? 0),
            ];
        }
        $this->info('Settings per business di DB lama: '.implode(', ', array_keys($settingsByBiz)));

        // 2. Ambil semua paket
        $packagesQuery = DB::connection('legacy')->table('packages')->orderBy('id');
        if ($bizFilter) {
            $packagesQuery->where('business_id', $bizFilter);
        }
        $packages = $packagesQuery->get();
        $this->info('Jumlah paket di DB lama: '.$packages->count());
        $this->line('');

        if ($packages->isEmpty()) {
            $this->warn('Tidak ada paket untuk di-import.');
            return self::SUCCESS;
        }

        if ($this->option('reset') && ! $isDryRun) {
            if (! $this->option('force') && ! $this->confirm('PERINGATAN: opsi --reset akan HAPUS semua installation_packages & water_tariff_blocks di DB baru. Lanjutkan?', false)) {
                $this->warn('Dibatalkan.');
                return self::SUCCESS;
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            WaterTariffBlock::truncate();
            InstallationPackage::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('Tabel DB baru sudah dikosongkan.');
        }

        $created = 0; $updated = 0; $errors = [];
        $bar = $this->output->createProgressBar($packages->count());
        $bar->start();

        foreach ($packages as $pkg) {
            try {
                $bizId = (int) $pkg->business_id;
                $bizSetting = $settingsByBiz[$bizId] ?? null;
                if (! $bizSetting) {
                    throw new \RuntimeException("business_id={$bizId} tidak punya settings di DB lama (lewati).");
                }

                $hargaArr = is_string($pkg->harga)
                    ? (json_decode($pkg->harga, true) ?: [])
                    : (array) $pkg->harga;
                $hargaArr = array_map(fn($v) => (float) $v, $hargaArr);

                // Samakan panjang array: jika harga lebih pendek dari blok → pad dengan elemen terakhir (atau 0).
                $blockCount = count($bizSetting['blocks']);
                while (count($hargaArr) < $blockCount) {
                    $hargaArr[] = empty($hargaArr) ? 0 : end($hargaArr);
                }
                // Jika harga lebih panjang, truncate.
                $hargaArr = array_slice($hargaArr, 0, $blockCount);

                // Susun blok tarif untuk DB baru
                $tariffBlocks = [];
                foreach ($bizSetting['blocks'] as $i => $block) {
                    [$min, $max] = $this->parseJarak($block['jarak'] ?? '');
                    $tariffBlocks[] = [
                        'usage_min_m3' => $min,
                        'usage_max_m3' => $max,
                        'price_per_m3' => (float) ($hargaArr[$i] ?? 0),
                    ];
                }

                // Nama paket: pakai kelas + suffix business biar unik lintas business
                $newName = $this->buildName($pkg->kelas, $bizId);

                if ($isDryRun) {
                    $this->line(sprintf(
                        "\n  [#%d] biz=%d | name=%s | fee=%.2f | abodemen=%.2f | denda=%.2f | %d blok",
                        $pkg->id, $bizId, $newName,
                        $bizSetting['pasang_baru'],
                        (float) $pkg->abodemen,
                        (float) $pkg->denda,
                        count($tariffBlocks)
                    ));
                    foreach ($tariffBlocks as $tb) {
                        $maxStr = $tb['usage_max_m3'] === null ? '∞' : $tb['usage_max_m3'];
                        $this->line(sprintf(
                            '         └─ %d–%s m³ @ Rp %.2f',
                            $tb['usage_min_m3'], $maxStr, $tb['price_per_m3']
                        ));
                    }
                    $bar->advance();
                    continue;
                }

                DB::transaction(function () use ($newName, $bizSetting, $pkg, $tariffBlocks, &$created, &$updated) {
                    $model = InstallationPackage::updateOrCreate(
                        ['name' => $newName],
                        [
                            'installation_fee' => (float) $bizSetting['pasang_baru'],
                            'monthly_abodemen' => (float) ($pkg->abodemen ?? 0),
                            'late_penalty'    => (float) ($pkg->denda ?? 0),
                        ]
                    );
                    $model->wasRecentlyCreated ? $created++ : $updated++;

                    WaterTariffBlock::where('package_id', $model->id)->delete();
                    foreach ($tariffBlocks as $tb) {
                        WaterTariffBlock::create([
                            'package_id'   => $model->id,
                            'usage_min_m3' => $tb['usage_min_m3'],
                            'usage_max_m3' => $tb['usage_max_m3'],
                            'price_per_m3' => $tb['price_per_m3'],
                        ]);
                    }
                });
                $bar->advance();
            } catch (\Throwable $e) {
                $errors[] = [
                    'legacy_id' => $pkg->id,
                    'kelas'     => $pkg->kelas ?? '-',
                    'error'     => $e->getMessage(),
                ];
                Log::error('Import package gagal', [
                    'legacy_id' => $pkg->id,
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
        $this->line('  - Gagal    : '.count($errors));

        if (! empty($errors)) {
            $this->line('');
            $this->warn('Detail error:');
            foreach ($errors as $err) {
                $this->line(sprintf(
                    '  - legacy_id=%s kelas=%s | %s',
                    $err['legacy_id'], $err['kelas'], $err['error']
                ));
            }
        }

        $this->line('');
        $this->info('Selesai.');
        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Parse string "0 - 10 M3" / "10-20 M3" / "21-100 M3" jadi [min, max].
     */
    private function parseJarak(string $jarak): array
    {
        $clean = preg_replace('/\s*M3\s*$/i', '', $jarak);
        $clean = str_replace(' ', '', $clean);
        if (! str_contains($clean, '-')) {
            $num = (int) $clean;
            return [$num, $num];
        }
        [$min, $max] = array_map('intval', explode('-', $clean, 2));
        // max = 999999 dianggap "tak terhingga" → null
        if ($max >= 999999) {
            $max = null;
        }
        return [$min, $max];
    }

    /**
     * Bikin nama paket untuk DB baru.
     * Pakai nama legacy langsung (tanpa suffix business_id) karena DB baru
     * sudah dipisah per business domain.
     */
    private function buildName(?string $kelas, int $businessId): string
    {
        $kelas = trim((string) ($kelas ?? ''));
        if ($kelas === '') {
            $kelas = 'Paket';
        }
        return $kelas;
    }
}