<?php

namespace App\Console\Commands;

use App\Support\TransactionTriggers;
use Illuminate\Console\Command;

/**
 * Pulihkan 3 trigger `amount` dari definisi kanonik (TransactionTriggers).
 *
 * Gunanya: bila `legacy:migrate-fast` mati keras (mis. kill -9) sehingga blok
 * `finally` tidak sempat jalan, trigger tetap OFF dan tabel `amount` tidak
 * terpelihara. Satu perintah ini memulihkannya.
 */
class LegacyRestoreTriggersCommand extends Command
{
    protected $signature = 'legacy:restore-triggers';

    protected $description = 'Recreate 3 trigger amount (create/update/delete_amount_debit) dari definisi kanonik.';

    public function handle(): int
    {
        $this->info('Recreate trigger transactions dari TransactionTriggers...');

        try {
            TransactionTriggers::createAll();
        } catch (\Throwable $e) {
            $this->error('GAGAL recreate trigger: '.$e->getMessage());

            return self::FAILURE;
        }

        $missing = TransactionTriggers::missing();

        if ($missing !== []) {
            $this->error('VERIFIKASI GAGAL: trigger belum lengkap. Missing: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->info('OK: ketiga trigger ada ('.implode(', ', TransactionTriggers::names()).').');

        return self::SUCCESS;
    }
}
