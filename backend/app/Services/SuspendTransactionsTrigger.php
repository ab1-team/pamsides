<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Matikan trigger `amount` pada tabel `transactions` sementara, lalu hidupkan
 * lagi — dipakai saat operasi tulis BULK yang besar.
 *
 * Kenapa ini perlu ada
 * -------------------
 * Tabel `transactions` punya 3 trigger (`create_amount_debit`,
 * `update_amount_debit`, `delete_amount_debit`). Semuanya menulis tabel
 * `amount` dengan cara: SUM ulang SELURUH tabel untuk satu bulan, lalu
 * `INSERT ... ON DUPLICATE KEY UPDATE`.
 *
 * Diukur di DB produksi (tabel ~105k baris):
 *   - 1 baris INSERT dengan trigger aktif  : 2.199 ms
 *   - 1 baris INSERT tanpa trigger          :   0,17 ms
 *
 * Untuk bulk 44.378 baris (22.189 tagihan x 2 jurnal), selisihnya membuat
 * proses turun dari 48 jam menjadi sekitar 9 detik. Karena hasil trigger
 * benar-benar tidak dibutuhkan (tabel `amount` dihitung ulang satu kali di
 * akhir), menangguhkan trigger untuk operasi bulk ini aman dan jauh lebih cepat.
 *
 * Keamanan
 * --------
 * - Definisi trigger dibaca dari `SHOW CREATE TRIGGER` (bukan ditulis ulang
 *   dari source code), sehingga yang dikembalikan persis seperti aslinya.
 * - `DEFINER=` dibuang saat recreating supaya tidak butuh hak SUPER.
 * - `restore()` aman dipanggil berkali-kali dan dipanggil lagi di `catch()`
 *   pemanggil, jadi trigger tidak pernah tertinggal mati.
 * - Bila proses PHP mati mendadak (kill / OOM), trigger tetap aktif karena
 *   `DROP TRIGGER` bersifat persisten, bukan per-sesi.
 */
class SuspendTransactionsTrigger
{
    /** @var array<string,string> Definisi trigger yang aktif sebelum ini dimatikan. */
    private array $saved = [];

    private bool $disabled = false;

    public function __construct(private string $table = 'transactions') {}

    /**
     * Simpan definisi trigger lalu hapus. Idempotent.
     *
     * @return bool true bila trigger berhasil dimatikan
     */
    public function disable(): bool
    {
        if ($this->disabled) {
            return true;
        }

        try {
            foreach ($this->currentTriggers() as $name) {
                $sql = $this->showCreate($name);

                if ($sql === null) {
                    // Tidak bisa definisi => JANGAN drop, biar tidak kehilangan
                    // trigger tanpa ada cara mengembalikannya.
                    $this->warn("Definisi trigger {$name} tidak terbaca; trigger dibiarkan aktif.");

                    continue;
                }

                $this->saved[$name] = $sql;
            }

            foreach (array_keys($this->saved) as $name) {
                DB::statement("DROP TRIGGER IF EXISTS `{$name}`");
            }

            $this->disabled = true;

            return true;
        } catch (\Throwable $e) {
            $this->warn('Gagal menonaktifkan trigger: '.$e->getMessage());
            $this->restore();

            return false;
        }
    }

    /**
     * Kembalikan trigger seperti semula. Aman dipanggil lebih dari sekali,
     * dan aman dipanggil walau `disable()` gagal.
     */
    public function restore(): void
    {
        // Tidak ada yang perlu dikembalikan kalau `disable()` tidak pernah
        // sukses — mis. `restore()` dipanggil di `catch()` padahal proses
        // gagal SEBELUM trigger sempat ditangguhkan. Di jalur ini trigger
        // masih utuh, jadi jangan sentuh sama sekali.
        if (empty($this->saved)) {
            $this->disabled = false;

            return;
        }

        foreach ($this->saved as $name => $sql) {
            try {
                DB::statement("DROP TRIGGER IF EXISTS `{$name}`");
                DB::unprepared($sql);
            } catch (\Throwable $e) {
                $this->error("Gagal mengembalikan trigger {$name}: ".$e->getMessage());
            }
        }

        $this->saved = [];
        $this->disabled = false;
    }

    /**
     * True bila trigger sedang dalam keadaan dimatikan oleh objek ini.
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Nama trigger milik tabel ini.
     *
     * @return array<int,string>
     */
    private function currentTriggers(): array
    {
        $names = [];

        foreach (DB::select('SHOW TRIGGERS') as $t) {
            if (property_exists($t, 'Table') && $t->Table === $this->table) {
                $names[] = $t->Trigger;
            }
        }

        return $names;
    }

    /**
     * Ambil statement CREATE TRIGGER lengkap.
     */
    private function showCreate(string $name): ?string
    {
        $database = DB::getDatabaseName();

        try {
            $rows = DB::select("SHOW CREATE TRIGGER `{$database}`.`{$name}`");
        } catch (\Throwable) {
            return null;
        }

        foreach ((array) ($rows[0] ?? []) as $column => $value) {
            if (stripos($column, 'SQL Original Statement') !== false) {
                // Buang DEFINER: user placeholder tidak selalu punya hak untuk
                // membuat objek dengan definisi user lain.
                return preg_replace('/CREATE\s+DEFINER=[^\s]+\s*/i', 'CREATE ', (string) $value);
            }
        }

        return null;
    }

    private function warn(string $message): void
    {
        Log::warning('[SuspendTransactionsTrigger] '.$message);
    }

    private function error(string $message): void
    {
        Log::error('[SuspendTransactionsTrigger] '.$message);
    }
}
