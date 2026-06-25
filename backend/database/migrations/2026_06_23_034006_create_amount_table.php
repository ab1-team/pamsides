<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amount', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('account_id');
            $table->string('bulan', 5)->nullable();
            $table->string('tahun', 10)->nullable();
            $table->decimal('debit', 15, 2)->nullable();
            $table->decimal('kredit', 15, 2)->nullable();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        // Trigger INSERT
        DB::unprepared('
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
        ');

        // Trigger UPDATE
        DB::unprepared('
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
        ');

        // Trigger DELETE
        DB::unprepared('
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
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS create_amount_debit');
        DB::unprepared('DROP TRIGGER IF EXISTS update_amount_debit');
        DB::unprepared('DROP TRIGGER IF EXISTS delete_amount_debit');

        Schema::dropIfExists('amount');
    }
};
