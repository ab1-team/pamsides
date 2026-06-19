<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterArusKasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum diisi
        DB::table('master_arus_kas')->truncate();

        $data = [
            ['id' => 1, 'nama_akun' => 'SALDO KAS SETARA KAS', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 2, 'nama_akun' => 'A. PENERIMAAN OPERASI', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 3, 'nama_akun' => null, 'debit' => null, 'kredit' => '4.1.01.00', 'parent_id' => 2],
            ['id' => 4, 'nama_akun' => null, 'debit' => null, 'kredit' => '4.2.01.00', 'parent_id' => 2],
            ['id' => 5, 'nama_akun' => null, 'debit' => null, 'kredit' => '4.1.02.00', 'parent_id' => 2],
            ['id' => 6, 'nama_akun' => null, 'debit' => null, 'kredit' => '4.1.03.00', 'parent_id' => 2],
            ['id' => 7, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.1.03.00', 'parent_id' => 2],
            ['id' => 8, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.1.05.00', 'parent_id' => 2],
            ['id' => 9, 'nama_akun' => 'PENGELUARAN', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 10, 'nama_akun' => 'B. Penyerahan Piutang Dan Rekening Antar', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 11, 'nama_akun' => null, 'debit' => '1.1.03.00', 'kredit' => null, 'parent_id' => 10],
            ['id' => 12, 'nama_akun' => null, 'debit' => '1.1.05.00', 'kredit' => null, 'parent_id' => 10],
            ['id' => 13, 'nama_akun' => 'C. Pengeluaran Operasi', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 14, 'nama_akun' => null, 'debit' => '2.1.02.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 15, 'nama_akun' => null, 'debit' => '2.1.03.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 16, 'nama_akun' => null, 'debit' => '5.1.01.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 17, 'nama_akun' => null, 'debit' => '5.1.02.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 18, 'nama_akun' => null, 'debit' => '5.1.03.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 19, 'nama_akun' => null, 'debit' => '5.1.04.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 20, 'nama_akun' => null, 'debit' => '5.1.05.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 22, 'nama_akun' => null, 'debit' => '5.1.09.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 23, 'nama_akun' => null, 'debit' => '5.2.01.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 24, 'nama_akun' => null, 'debit' => '5.3.01.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 25, 'nama_akun' => null, 'debit' => '5.3.03.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 26, 'nama_akun' => null, 'debit' => '5.4.01.00', 'kredit' => null, 'parent_id' => 13],
            ['id' => 27, 'nama_akun' => 'INVESTASI', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 28, 'nama_akun' => 'A. Penerimaan', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 29, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.1.06.00', 'parent_id' => 28],
            ['id' => 30, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.2.02.00', 'parent_id' => 28],
            ['id' => 31, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.2.01.00', 'parent_id' => 28],
            ['id' => 32, 'nama_akun' => null, 'debit' => null, 'kredit' => '1.2.04.00', 'parent_id' => 28],
            ['id' => 34, 'nama_akun' => null, 'debit' => null, 'kredit' => '4.3.01.00', 'parent_id' => 28],
            ['id' => 35, 'nama_akun' => 'B. Pengeluaran', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 36, 'nama_akun' => null, 'debit' => '1.1.06.00', 'kredit' => null, 'parent_id' => 35],
            ['id' => 37, 'nama_akun' => null, 'debit' => '1.2.01.00', 'kredit' => null, 'parent_id' => 35],
            ['id' => 38, 'nama_akun' => null, 'debit' => '1.2.03.00', 'kredit' => null, 'parent_id' => 35],
            ['id' => 39, 'nama_akun' => 'PENDANAAN', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 40, 'nama_akun' => 'A. Penerimaan', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 41, 'nama_akun' => null, 'debit' => null, 'kredit' => '3.1.01.00', 'parent_id' => 40],
            ['id' => 42, 'nama_akun' => null, 'debit' => null, 'kredit' => '2.1.01.00', 'parent_id' => 40],
            ['id' => 43, 'nama_akun' => null, 'debit' => null, 'kredit' => '2.2.01.00', 'parent_id' => 40],
            ['id' => 44, 'nama_akun' => null, 'debit' => null, 'kredit' => '2.1.05.00', 'parent_id' => 40],
            ['id' => 45, 'nama_akun' => null, 'debit' => null, 'kredit' => '2.2.02.00', 'parent_id' => 40],
            ['id' => 46, 'nama_akun' => 'B. Pengeluaran', 'debit' => null, 'kredit' => null, 'parent_id' => 0],
            ['id' => 47, 'nama_akun' => null, 'debit' => '2.1.04.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 48, 'nama_akun' => null, 'debit' => '2.2.01.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 49, 'nama_akun' => null, 'debit' => '2.1.01.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 50, 'nama_akun' => null, 'debit' => '2.1.02.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 51, 'nama_akun' => null, 'debit' => '2.1.05.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 52, 'nama_akun' => null, 'debit' => '2.2.02.00', 'kredit' => null, 'parent_id' => 46],
            ['id' => 53, 'nama_akun' => null, 'debit' => '5.1.08.00', 'kredit' => null, 'parent_id' => 13],
        ];

        DB::table('master_arus_kas')->insert($data);
    }
}
