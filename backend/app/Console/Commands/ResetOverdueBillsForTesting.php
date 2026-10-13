<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Support\TransactionTriggers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
 *     1. DROP trigger (App\Support\TransactionTriggers::dropAll)
 *     2. Bulk UPDATE (soft-delete)
 *     3. RECREATE trigger dari definisi yang sama (TransactionTriggers::createAll)
 *
 *   SUMBER TUNGGAL definisi trigger: app/Support/TransactionTriggers.php
 *   (ekstraksi persis dari migrasi 2026_06_23_034006_create_amount_table.php).
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

        // 1. Drop trigger
        $this->info('Drop trigger transactions...');
        TransactionTriggers::dropAll();

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
            TransactionTriggers::createAll();

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
                TransactionTriggers::createAll();
                $this->warn('Trigger di-recreate kembali setelah error.');
            } catch (\Throwable $e2) {
                $this->error('GAGAL RECREATE TRIGGER! Harap restore manual. Error: '.$e2->getMessage());
            }
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
