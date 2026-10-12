<?php

namespace App\Console\Commands;

use App\Support\TransactionTriggers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Jalur migrasi legacy CEPAT: trigger OFF -> import -> recompute -> trigger ON.
 *
 * Kenapa ada:
 *   Trigger `AFTER INSERT/UPDATE` pada tabel `transactions` menjalankan agregasi
 *   SUM(saldo) PER BARIS yang ditulis (dan mengulanginya di ON DUPLICATE KEY
 *   UPDATE). Untuk ~79.077 baris legacy jalur normal butuh >7 jam.
 *
 * Pola:
 *   1. Drop 3 trigger (create/update/delete_amount_debit).
 *   2. Jalankan rantai `import:*` BERURUTAN (bulk, tanpa trigger).
 *   3. Recompute tabel `amount` secara SET-BASED (satu INSERT ... SELECT ... GROUP BY).
 *   4. Recreate 3 trigger -- DIJAMIN via `finally`, walau importer error.
 *
 * Verifikasi trigger dipulihkan ada di luar `finally`; kalau tidak lengkap
 * command keluar dengan exit code non-zero.
 */
class LegacyMigrateFastCommand extends Command
{
    protected $signature = 'legacy:migrate-fast
                            {--business=5 : Filter business_id di DB legacy}
                            {--from= : Mulai dari nama command import tertentu (resume)}
                            {--memory=512M : memory_limit untuk proses import (importer legacy butuh >128M)}
                            {--yes : Lewati konfirmasi interaktif}';

    protected $description = 'Import legacy cepat: trigger OFF -> rantai import -> recompute amount set-based -> trigger ON.';

    /**
     * Rantai import berurutan. `business` = true bila command mendukung --business.
     *
     * @var array<int,array{command:string,business:bool}>
     */
    protected array $chain = [
        ['command' => 'import:villages',        'business' => false],
        ['command' => 'import:packages',        'business' => true],
        ['command' => 'import:accounts',        'business' => true],
        ['command' => 'import:users',           'business' => false],
        ['command' => 'import:tickets',         'business' => true],
        ['command' => 'import:customers',       'business' => true],
        ['command' => 'import:meter-readings',  'business' => true],
        ['command' => 'import:monthly-bills',   'business' => true],
        ['command' => 'import:bill-payments',   'business' => true],
        ['command' => 'import:transactions',    'business' => true],
        ['command' => 'import:payments',        'business' => true],
    ];

    public function handle(): int
    {
        $business = (string) $this->option('business');
        $from = $this->option('from');

        if ($from !== null && $from !== '') {
            $known = array_column($this->chain, 'command');
            if (! in_array($from, $known, true)) {
                $this->error("--from='{$from}' bukan command import yang dikenal. Pilihan: ".implode(', ', $known));

                return self::FAILURE;
            }
        }

        // ================= memory =================
        // Importer legacy (khususnya import:transactions) MEMBUTUHKAN heap > 128M
        // secara independen dari trigger -- ini footprint importer, bukan
        // workaround lambatnya trigger (solusi trigger = drop trigger + recompute
        // set-based, di bawah). Default 512M mengikuti memory_limit asli
        // environment ini (/etc/php.ini.rpmsave). Tidak menyentuh php.ini global.
        $targetMemory = (string) $this->option('memory');
        $this->ensureMemoryLimit($targetMemory);

        $this->newLine();
        $this->warn('============================================================');
        $this->warn(' PERINGATAN: 3 TRIGGER amount AKAN DIMATIKAN SELAMA PROSES.');
        $this->warn(' Selama trigger OFF, tabel `amount` TIDAK terpelihara.');
        $this->warn(' Trigger DIJAMIN di-recreate lewat `finally`, dan diverifikasi');
        $this->warn(' di akhir. Bila proses mati keras (kill -9), pulihkan dengan:');
        $this->warn('   php artisan legacy:restore-triggers');
        $this->warn('============================================================');
        $this->newLine();
        $this->line("Business id : {$business}");
        $this->line('Mulai dari  : '.($from ?: $this->chain[0]['command']));
        $this->newLine();

        if (! $this->option('yes')) {
            if (! $this->confirm('Lanjutkan migrasi cepat ini?', false)) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $overallStart = microtime(true);
        $timings = [];

        try {
            // (a) Drop trigger + verifikasi hilang.
            $this->info('[1/4] Drop trigger transactions...');
            TransactionTriggers::dropAll();

            $present = array_values(array_diff(TransactionTriggers::names(), TransactionTriggers::missing()));
            if ($present !== []) {
                $this->error('GAGAL: trigger masih ada setelah drop: '.implode(', ', $present));

                return self::FAILURE;
            }
            $this->info('     OK: ketiga trigger sudah hilang.');

            // (b) Rantai import berurutan.
            $this->newLine();
            $this->info('[2/4] Rantai import ('.$this->countChain($from).' command)...');
            $skip = $from !== null && $from !== '';
            foreach ($this->chain as $step) {
                $name = $step['command'];

                if ($skip) {
                    if ($name === $from) {
                        $skip = false;
                    } else {
                        continue;
                    }
                }

                $args = ['--force' => true];
                if ($step['business']) {
                    $args['--business'] = $business;
                }

                $t0 = microtime(true);
                $exit = Artisan::call($name, $args);
                $secs = microtime(true) - $t0;
                $timings[] = [$name, number_format($secs, 2).' s', $exit === 0 ? 'OK' : "EXIT={$exit}"];

                $this->line(sprintf('     %-22s %8.2f s  (%s)', $name, $secs, $exit === 0 ? 'OK' : "EXIT={$exit}"));

                if ($exit !== 0) {
                    throw new \RuntimeException("Command {$name} gagal dengan exit code {$exit}.");
                }
            }

            // (c) Recompute amount set-based.
            $this->newLine();
            $this->info('[3/4] Recompute `amount` set-based...');
            $t0 = microtime(true);
            $affected = TransactionTriggers::recomputeAmount();
            $recomputeSecs = microtime(true) - $t0;
            $amountRows = DB::table('amount')->count();
            $this->line(sprintf('     OK: recompute %d baris affected, tabel amount kini %d baris (%.2f s).', $affected, $amountRows, $recomputeSecs));
        } finally {
            // (d) Recreate trigger -- WAJIB, walau importer error.
            $this->newLine();
            $this->info('[4/4] Recreate trigger transactions (finally)...');
            try {
                TransactionTriggers::createAll();
                $this->info('     OK: createAll() dijalankan.');
            } catch (\Throwable $e) {
                $this->error('GAGAL recreate trigger di finally: '.$e->getMessage());
            }
        }

        // (c) Tabel durasi per-command.
        $this->newLine();
        $this->table(['Command', 'Durasi', 'Status'], $timings);

        $total = microtime(true) - $overallStart;
        $this->newLine();
        $this->info(sprintf('Total waktu: %.2f s (%.2f menit).', $total, $total / 60));

        // Verifikasi trigger lengkap (di luar finally).
        $missing = TransactionTriggers::missing();
        if ($missing !== []) {
            $this->newLine();
            $this->error('VERIFIKASI GAGAL: trigger belum lengkap. Missing: '.implode(', ', $missing));
            $this->error('Jalankan: php artisan legacy:restore-triggers');

            return self::FAILURE;
        }

        $this->info('VERIFIKASI OK: ketiga trigger ada ('.implode(', ', TransactionTriggers::names()).').');

        return self::SUCCESS;
    }

    /**
     * Naikkan memory_limit proses ini bila perlu (memory_limit adalah
     * PHP_INI_ALL, bisa diubah runtime di CLI). Hanya menaikkan, tidak menurunkan.
     */
    protected function ensureMemoryLimit(string $target): void
    {
        $bytes = $this->parseBytes($target);
        $current = $this->parseBytes((string) ini_get('memory_limit'));

        if ($current !== -1 && $bytes > 0 && $current < $bytes) {
            ini_set('memory_limit', $target);
            $this->line('memory_limit: '.ini_get('memory_limit').' (semula '.$this->formatBytes($current).')');
        } else {
            $this->line('memory_limit: '.ini_get('memory_limit').' (tidak diubah)');
        }
    }

    protected function parseBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    protected function formatBytes(int $bytes): string
    {
        return round($bytes / 1048576).'M';
    }

    protected function countChain(?string $from): int
    {
        if ($from === null || $from === '') {
            return count($this->chain);
        }

        $names = array_column($this->chain, 'command');
        $idx = array_search($from, $names, true);

        return $idx === false ? count($this->chain) : count($this->chain) - $idx;
    }
}
