<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * SUMBER TUNGGAL definisi trigger MySQL untuk tabel `transactions`
 * (create_amount_debit / update_amount_debit / delete_amount_debit).
 *
 * Definisi di sini adalah EKSTRAKSI PERSIS dari migrasi
 * database/migrations/2026_06_23_034006_create_amount_table.php.
 * Isi trigger TIDAK boleh diubah di sini -- bila migrasi berubah, ekstrak ulang.
 *
 * Dipakai oleh:
 *  - App\Console\Commands\LegacyMigrateFastCommand   (drop -> import -> recompute -> create)
 *  - App\Console\Commands\LegacyRestoreTriggersCommand (restore bila mati keras)
 *  - App\Console\Commands\ResetOverdueBillsForTesting  (drop -> bulk -> create)
 */
class TransactionTriggers
{
    /**
     * Definisi trigger: nama => SQL `CREATE TRIGGER`.
     *
     * Placeholder teks di bawah adalah salinan literal dari DB::unprepared()
     * di migrasi 2026_06_23_034006_create_amount_table.php.
     *
     * @return array<string,string>
     */
    public static function definitions(): array
    {
        return [
            'create_amount_debit' => '
            CREATE TRIGGER create_amount_debit AFTER INSERT ON transactions
            FOR EACH ROW BEGIN

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(NEW.tgl_transaksi), LPAD(MONTH(NEW.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(NEW.tgl_transaksi),
                    LPAD(MONTH(NEW.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = NEW.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = NEW.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = NEW.account_debet
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = NEW.account_debet
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = NEW.account_debet
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi));

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(NEW.tgl_transaksi), LPAD(MONTH(NEW.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(NEW.tgl_transaksi),
                    LPAD(MONTH(NEW.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = NEW.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = NEW.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = NEW.account_kredit
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = NEW.account_kredit
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = NEW.account_kredit
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi));
            END
        ',
            'update_amount_debit' => '
            CREATE TRIGGER update_amount_debit AFTER UPDATE ON transactions
            FOR EACH ROW BEGIN

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(NEW.tgl_transaksi), LPAD(MONTH(NEW.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(NEW.tgl_transaksi),
                    LPAD(MONTH(NEW.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = NEW.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = NEW.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = NEW.account_debet
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = NEW.account_debet
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = NEW.account_debet
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi));

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(NEW.tgl_transaksi), LPAD(MONTH(NEW.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(NEW.tgl_transaksi),
                    LPAD(MONTH(NEW.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = NEW.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = NEW.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = NEW.account_kredit
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = NEW.account_kredit
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = NEW.account_kredit
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(NEW.tgl_transaksi), "-01-01") AND LAST_DAY(NEW.tgl_transaksi));
            END
        ',
            'delete_amount_debit' => '
            CREATE TRIGGER delete_amount_debit AFTER DELETE ON transactions
            FOR EACH ROW BEGIN

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(OLD.tgl_transaksi), LPAD(MONTH(OLD.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(OLD.tgl_transaksi),
                    LPAD(MONTH(OLD.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = OLD.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = OLD.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = OLD.account_debet
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = OLD.account_debet
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = OLD.account_debet
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi));

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR(OLD.tgl_transaksi), LPAD(MONTH(OLD.tgl_transaksi), 2, "0")),
                    a.id,
                    YEAR(OLD.tgl_transaksi),
                    LPAD(MONTH(OLD.tgl_transaksi), 2, "0"),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = OLD.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = OLD.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = OLD.account_kredit
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = OLD.account_kredit
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = OLD.account_kredit
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR(OLD.tgl_transaksi), "-01-01") AND LAST_DAY(OLD.tgl_transaksi));
            END
        ',        ];
    }

    /**
     * Nama ketiga trigger (urutan kanonik).
     *
     * @return array<int,string>
     */
    public static function names(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Drop ketiga trigger bila ada. MySQL tidak mendukung
     * `DROP TRIGGER IF EXISTS` pada versi lama -- tapi MySQL 8 menerimanya,
     * sehingga aman dipakai langsung.
     */
    public static function dropAll(): void
    {
        foreach (self::names() as $name) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$name}`");
        }
    }

    /**
     * Recreate ketiga trigger dari definisi kanonik.
     */
    public static function createAll(): void
    {
        foreach (self::definitions() as $sql) {
            DB::unprepared($sql);
        }
    }

    /**
     * Nama trigger yang BELUM ada di schema aktif (untuk verifikasi).
     *
     * @return array<int,string>
     */
    public static function missing(): array
    {
        $existing = DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->whereIn('TRIGGER_NAME', self::names())
            ->pluck('TRIGGER_NAME')
            ->all();

        return array_values(array_diff(self::names(), $existing));
    }

    /**
     * Recompute tabel `amount` secara SET-BASED (satu statement), sebagai
     * pengganti agregasi per-baris yang dilakukan trigger saat trigger mati.
     *
     * Untuk setiap (akun, tahun, bulan) yang punya transaksi aktif:
     *   debit  = SUM(saldo) WHERE account_debet  = kode_akun
     *   kredit = SUM(saldo) WHERE account_kredit = kode_akun
     * lalu upsert by primary key `id = account_id . tahun . bulan`.
     *
     * Baris `amount` yang TIDAK punya transaksi aktif TIDAK dihapus.
     *
     * @return int jumlah baris yang ditulis/disentuh
     */
    public static function recomputeAmount(): int
    {
        // Subquery (derived table) menghitung kunci (id/account_id/tahun/bulan)
        // sebagai KOLOM biasa, lalu agregasi di luar -- aman terhadap
        // sql_mode=ONLY_FULL_GROUP_BY (tidak meng-group by ekspresi).
        $sql = <<<'SQL'
INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
SELECT
    x.id,
    x.account_id,
    x.tahun,
    x.bulan,
    SUM(x.d) AS debit,
    SUM(x.k) AS kredit
FROM (
    SELECT
        CONCAT(a.id, YEAR(t.tgl_transaksi), LPAD(MONTH(t.tgl_transaksi), 2, '0')) AS id,
        a.id AS account_id,
        CAST(YEAR(t.tgl_transaksi) AS CHAR) AS tahun,
        LPAD(MONTH(t.tgl_transaksi), 2, '0') AS bulan,
        CASE WHEN t.account_debet  = a.kode_akun THEN t.saldo ELSE 0 END AS d,
        CASE WHEN t.account_kredit = a.kode_akun THEN t.saldo ELSE 0 END AS k
    FROM transactions t
    JOIN accounts a
        ON a.kode_akun IN (t.account_debet, t.account_kredit)
    WHERE t.deleted_at IS NULL
) x
GROUP BY x.id, x.account_id, x.tahun, x.bulan
ON DUPLICATE KEY UPDATE
    debit  = VALUES(debit),
    kredit = VALUES(kredit)
SQL;

        return DB::affectingStatement($sql);
    }

}
