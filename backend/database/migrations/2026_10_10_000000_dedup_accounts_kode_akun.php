<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Validasi dulu: tidak boleh ada kode dengan >1 baris bersaldo.
        // Kalau ada, saldo-nya terpecah dan penghapusan di bawah akan menghilangkan
        // rupiah. Hentikan dengan pesan jelas, jangan dibiarkan lewat.
        $pecah = DB::table('accounts as a')
            ->join('amount as m', 'm.account_id', '=', 'a.id')
            ->groupBy('a.kode_akun')
            ->havingRaw('COUNT(DISTINCT a.id) > 1')
            ->get(DB::raw('a.kode_akun'));

        if ($pecah->isNotEmpty()) {
            throw new RuntimeException(
                'Ada '.count($pecah).' kode akun yang saldonya tersebar di lebih dari satu baris: '
                .$pecah->pluck('kode_akun')->implode(', ')
                .'. Gabungkan saldo manual dulu sebelum migration ini dijalankan.'
            );
        }

        // Simpan satu baris per kode: yang id-nya terbesar. Seluruh `amount` saat ini
        // sudah nunjuk ke blok seeder terbaru (594-689), jadi ini baris yang
        // benar-benar dipakai. Baris kode yang tak terpakai (jml_amount 0)
        // tetap ikut terhapus -- tidak ada yang perlu dilindungi.
        $hapus = DB::table('accounts as a')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accounts as b')
                    ->whereColumn('b.kode_akun', 'a.kode_akun')
                    ->where('b.id', '>', 'a.id');
            })
            ->pluck('a.id')
            ->all();

        if ($hapus !== []) {
            DB::table('accounts')->whereIn('id', $hapus)->delete();
        }

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