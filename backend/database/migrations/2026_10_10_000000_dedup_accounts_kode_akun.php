<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->konsolidasikanAmountDuplikat();
            $this->hapusAkunDuplikat();
        });

        $this->dropIndexIfExists('accounts', 'idx_accounts_kode_akun');

        // MySQL tidak punya `ADD UNIQUE INDEX IF NOT EXISTS`, jadi cek dulu
        // lewat information_schema supaya migration ini idempoten.
        if (! $this->indexExists('accounts', 'accounts_kode_akun_unique')) {
            DB::statement('ALTER TABLE accounts ADD UNIQUE INDEX accounts_kode_akun_unique (kode_akun)');
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('accounts', 'accounts_kode_akun_unique');

        if (! $this->indexExists('accounts', 'idx_accounts_kode_akun')) {
            DB::statement('ALTER TABLE accounts ADD INDEX idx_accounts_kode_akun (kode_akun)');
        }
    }

    /**
     * Pindahkan seluruh saldo `amount` dari akun duplikat (ID lama) ke akun
     * penampung (ID terbesar) dengan ATURAN YANG AMAN untuk uang.
     *
     * Latar belakang: `accounts` menyisakan blok lama berdampingan dengan blok
     * seeder terbaru untuk kode akun yang sama. `TransactionObserver` mencari
     * akun lewat `first()` tanpa `orderBy`, jadi saat jurnal Oktober 2026
     * masuk, `amount` ditulis ke akun ID LAMA -- membuat saldo untuk satu
     * kode_akun terbagi di dua baris akun:
     *
     *   1.1.01.01 Kas Tunai            -> id 1   (Okt 2026) + id 594 (riwayat)
     *   4.1.01.02 Pendapatan Abodemen  -> id 49  (Okt 2026) + id 642
     *   4.1.01.03 Tagihan Pemakaian    -> id 50  (Okt 2026) + id 643
     *
     * Versi migration sebelumnya melempar RuntimeException saat menemukan
     * kondisi ini, sehingga `php artisan migrate` di produksi PASTI gagal.
     * Sekarang saldo digabung otomatis.
     *
     * `amount.id` berformat "{account_id}{tahun}{bulan}" (lihat
     * TransactionObserver::updateAmountForAccount). Jadi:
     *
     *   - Periode SUDAH ada di akun target : jumlahkan debit & kredit,
     *     lalu hapus baris duplikatnya. Tidak ada rupiah yang berubah --
     *     hanya pindah dari satu baris ke baris yang menyatu.
     *   - Periode BELUM ada di akun target : sama sekali tidak menyentuh
     *     kolom `id` (memakai UPDATE pada PK), jadi baris itu tetap utuh.
     *     Ambil id target dari tabel agar tidak salah menebak panjang digit.
     */
    private function konsolidasikanAmountDuplikat(): void
    {
        $targetPerKode = $this->kodeDenganIdTerbesar();

        foreach ($targetPerKode as $kode => $targetId) {
            $sumber = DB::table('accounts')
                ->where('kode_akun', $kode)
                ->where('id', '<>', $targetId)
                ->pluck('id')
                ->all();

            if ($sumber === []) {
                continue;
            }

            // Periode yang sudah tercatat di akun target (kunci penggabungan).
            $periodeTarget = DB::table('amount')
                ->where('account_id', $targetId)
                ->get()
                ->map(fn ($r) => $r->tahun.'|'.$r->bulan)
                ->flip();

            foreach ($sumber as $sumberId) {
                $amountSumber = DB::table('amount')->where('account_id', $sumberId)->get();

                foreach ($amountSumber as $row) {
                    $key = $row->tahun.'|'.$row->bulan;

                    if ($periodeTarget->has($key)) {
                        // Periode kembar: jumlahkan ke baris target.
                        $barisTarget = DB::table('amount')
                            ->where('account_id', $targetId)
                            ->where('tahun', $row->tahun)
                            ->where('bulan', $row->bulan)
                            ->first();

                        if ($barisTarget) {
                            DB::table('amount')
                                ->where('id', $barisTarget->id)
                                ->update([
                                    'debit' => round((float) $barisTarget->debit + (float) $row->debit, 2),
                                    'kredit' => round((float) $barisTarget->kredit + (float) $row->kredit, 2),
                                ]);

                            DB::table('amount')->where('id', $row->id)->delete();
                        }
                    } else {
                        // Periode belum ada di target: alihkan baris apa adanya.
                        // Kolom `id` SENGAJA tidak diubah (primary key ikut
                        // memuat account_id lama) supaya baris tetap utuh
                        // dan tidak bentrok dengan baris lain.
                        DB::table('amount')
                            ->where('id', $row->id)
                            ->update(['account_id' => $targetId]);

                        $periodeTarget->put($key, true);
                    }
                }

                DB::table('amount')->where('account_id', $sumberId)->delete();
            }
        }
    }

    /**
     * @return array<string,int> kode_akun => id akun terbesar (penampung)
     */
    private function kodeDenganIdTerbesar(): array
    {
        return DB::table('accounts')
            ->selectRaw('kode_akun, MAX(id) as id')
            ->groupBy('kode_akun')
            ->pluck('id', 'kode_akun')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Hapus akun duplikat: untuk setiap kode_akun, simpan ID terbesar
     * (blok seeder terbaru) dan hapus sisanya.
     *
     * Query ditulis eksplisit, bukan lewat `whereExists` + `whereColumn`.
     * Versi closure sebelumnya pernah mengembalikan SEMUA akun (termasuk
     * yang seharusnya disimpan) sehingga tabel `accounts` terkosongkan --
     * hal yang baru ketahuan lewat simulasi. SQL di bawah sudah diverifikasi:
     * hanya ID yang punya pasangan dengan ID lebih besar yang ikut terhapus.
     */
    private function hapusAkunDuplikat(): void
    {
        $hapus = DB::select(
            'SELECT a.id FROM accounts a
             WHERE EXISTS (
                 SELECT 1 FROM accounts b
                 WHERE b.kode_akun = a.kode_akun AND b.id > a.id
             )'
        );

        if ($hapus === []) {
            return;
        }

        DB::table('accounts')->whereIn('id', array_map(fn ($r) => (int) $r->id, $hapus))->delete();
    }

    /**
     * MySQL tidak mendukung `DROP INDEX IF EXISTS` -- syntax itu milik
     * PostgreSQL/SQLite. Memakainya di sini membuat MySQL 8 gagal dengan
     * SQLSTATE[42000] error 1064. Karena itu existence dicek dulu lewat
     * information_schema, baru statement-nya dijalankan.
     */
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement('ALTER TABLE '.$table.' DROP INDEX '.$index);
        }
    }
};