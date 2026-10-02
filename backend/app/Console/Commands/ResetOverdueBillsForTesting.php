<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RESET jurnal overdue_bill untuk testing.
 *
 * Konteks:
 * - Command ini menghapus SEMUA baris `transactions` dengan `reverence_type='overdue_bill'`
 *   (soft-delete: set deleted_at).
 * - Tidak menyentuh jurnal lain (payment, bill_payment, pasang_baru, dll).
 * - TIDAK mengubah status bill (`unpaid`/`paid` tetap).
 *
 * PENTING â€” TENTANG TRIGGER MYSQL:
 *   Tabel `transactions` punya 3 trigger (create_amount_debit, update_amount_debit,
 *   delete_amount_debit). Trigger `update_amount_debit` melakukan SELECT SUM besar
 *   ke tabel transactions untuk setiap UPDATE â€” ini sangat lambat untuk bulk update
 *   (lebih dari 20k baris = timeout).
 *   MySQL tidak punya syntax disable trigger, jadi command ini:
 *     1. DROP trigger (definisi hardcode â€” lihat $triggerDefs)
 *     2. Bulk UPDATE (soft-delete)
 *     3. RECREATE trigger dari definisi yang sama
 *
 *   Definisi trigger hardcode SUMBER: database/migrations/2026_06_23_034006_create_amount_table.php
 *   Jika trigger di migrasi berubah, update juga $triggerDefs di sini.
 *
 * Keamanan:
 *   - Wajib konfirmasi interaktif (kecuali --yes).
 */
class ResetOverdueBillsForTesting extends Command
{
    protected $signature = 'billing:reset-overdue-bills
                            {--yes : Lewati konfirmasi interaktif}
                            {--dry-run : Hanya hitung, jangan hapus}';

    protected $description = 'Hapus semua jurnal overdue_bill (untuk testing). TIDAK mengubah status bill.';

    /**
     * Definisi trigger HARDCODED (lihat migrasi 2026_06_23_034006_create_amount_table).
     * Pakai placeholders {target} untuk NEW/OLD yang konsisten.
     */
    private function triggerDefs(): array
    {
        $insertUpdateBody = function (string $target): string {
            // $target = 'NEW' atau 'OLD'
            return "
                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR({$target}.tgl_transaksi), LPAD(MONTH({$target}.tgl_transaksi), 2, '0')),
                    a.id,
                    YEAR({$target}.tgl_transaksi),
                    LPAD(MONTH({$target}.tgl_transaksi), 2, '0'),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = {$target}.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = {$target}.account_debet
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = {$target}.account_debet
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = {$target}.account_debet
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = {$target}.account_debet
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi));

                INSERT INTO amount (id, account_id, tahun, bulan, debit, kredit)
                SELECT
                    CONCAT(a.id, YEAR({$target}.tgl_transaksi), LPAD(MONTH({$target}.tgl_transaksi), 2, '0')),
                    a.id,
                    YEAR({$target}.tgl_transaksi),
                    LPAD(MONTH({$target}.tgl_transaksi), 2, '0'),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_debet = {$target}.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi)),
                    (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                     WHERE account_kredit = {$target}.account_kredit
                       AND deleted_at IS NULL
                       AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi))
                FROM accounts a
                WHERE a.kode_akun = {$target}.account_kredit
                ON DUPLICATE KEY UPDATE
                    debit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                             WHERE account_debet = {$target}.account_kredit
                               AND deleted_at IS NULL
                               AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi)),
                    kredit = (SELECT IFNULL(SUM(saldo), 0) FROM transactions
                              WHERE account_kredit = {$target}.account_kredit
                                AND deleted_at IS NULL
                                AND tgl_transaksi BETWEEN CONCAT(YEAR({$target}.tgl_transaksi), '-01-01') AND LAST_DAY({$target}.tgl_transaksi));
            ";
        };

        return [
            'create_amount_debit' => "CREATE TRIGGER create_amount_debit AFTER INSERT ON transactions
                FOR EACH ROW BEGIN
                    " . $insertUpdateBody('NEW') . "
                END",
            'update_amount_debit' => "CREATE TRIGGER update_amount_debit AFTER UPDATE ON transactions
                FOR EACH ROW BEGIN
                    " . $insertUpdateBody('NEW') . "
                END",
            'delete_amount_debit' => "CREATE TRIGGER delete_amount_debit AFTER DELETE ON transactions
                FOR EACH ROW BEGIN
                    " . $insertUpdateBody('OLD') . "
                END",
        ];
    }

    public function handle()
    {
        $count = Transaction::where('reverence_type', 'overdue_bill')->whereNull('deleted_at')->count();

        if ($count === 0) {
            $this->info('Tidak ada jurnal overdue_bill aktif untuk dihapus.');
            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$count} jurnal overdue_bill.");
        $this->warn('Bill dengan status unpaid TIDAK akan diubah â€” hanya jurnal piutang tunggakan.');

        $perAkun = Transaction::where('reverence_type', 'overdue_bill')
            ->whereNull('deleted_at')
            ->selectRaw('account_kredit, COUNT(*) as cnt, SUM(saldo) as total')
            ->groupBy('account_kredit')
            ->orderBy('account_kredit')
            ->get();

        $this->table(
            ['Akun Kredit', 'Jumlah Jurnal', 'Total Saldo'],
            $perAkun->map(fn ($r) => [
                $r->account_kredit,
                $r->cnt,
                number_format((float) $r->total, 0, ',', '.'),
            ])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->info('--dry-run aktif: tidak ada data yang dihapus.');
            return self::SUCCESS;
        }

        if (! $this->option('yes')) {
            $this->newLine();
            if (! $this->confirm("Hapus {$count} jurnal overdue_bill? (Status bill TIDAK berubah)", false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $startedAt = microtime(true);
        $triggerDefs = $this->triggerDefs();

        // 1. Drop trigger
        $this->info('Drop trigger transactions...');
        foreach (array_keys($triggerDefs) as $name) {
            DB::statement("DROP TRIGGER IF EXISTS `{$name}`");
        }

        $deleted = 0;
        try {
            // 2. Bulk UPDATE soft-delete â€” tanpa trigger, jauh lebih cepat.
            $now = now();
            $deleted = DB::table('transactions')
                ->where('reverence_type', 'overdue_bill')
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);

            $durationDelete = (int) ((microtime(true) - $startedAt) * 1000);
            $this->info("Berhasil soft-delete {$deleted} jurnal overdue_bill dalam {$durationDelete} ms.");

            // 3. Recreate trigger
            $this->info('Recreate trigger transactions...');
            foreach ($triggerDefs as $name => $sql) {
                DB::statement($sql);
            }

            $duration = (int) ((microtime(true) - $startedAt) * 1000);
            $this->newLine();
            $this->comment('LANGKAH SELANJUTNYA:');
            $this->comment('1. Login di tanggal generate (saat ini tgl 1) untuk trigger generate ulang.');
            $this->comment('2. Cek tabel transactions: harus ada jurnal baru dengan reverence_type=overdue_bill.');
            $this->comment('3. Bill unpaid tetap 3.295 (tidak berubah).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Upayakan recreate trigger agar DB tidak rusak
            try {
                foreach ($triggerDefs as $name => $sql) {
                    DB::statement($sql);
                }
                $this->warn('Trigger di-recreate kembali setelah error.');
            } catch (\Throwable $e2) {
                $this->error('GAGAL RECREATE TRIGGER! Harap restore manual. Error: ' . $e2->getMessage());
            }
            $this->error('Gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
