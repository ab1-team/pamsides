<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkunLevel2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum diisi
        DB::table('akun_level_2')->truncate();

        $data = [
            ['id' => 11, 'parent_id' => 1, 'lev1' => 1, 'lev2' => 1, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '1.1.00.00', 'nama_akun' => 'Aset Lancar', 'jenis_mutasi' => 'Debet'],
            ['id' => 12, 'parent_id' => 1, 'lev1' => 1, 'lev2' => 2, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '1.2.00.00', 'nama_akun' => 'Aset Tidak Lancar', 'jenis_mutasi' => 'Debet'],
            ['id' => 13, 'parent_id' => 1, 'lev1' => 1, 'lev2' => 3, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '1.3.00.00', 'nama_akun' => 'Aset Lain-lain', 'jenis_mutasi' => 'Debet'],
            
            ['id' => 21, 'parent_id' => 2, 'lev1' => 2, 'lev2' => 1, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '2.1.00.00', 'nama_akun' => 'Utang Jangka Pendek', 'jenis_mutasi' => 'Kredit'],
            ['id' => 22, 'parent_id' => 2, 'lev1' => 2, 'lev2' => 2, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '2.2.00.00', 'nama_akun' => 'Utang Jangka Panjang', 'jenis_mutasi' => 'Kredit'],
            
            ['id' => 31, 'parent_id' => 3, 'lev1' => 3, 'lev2' => 1, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '3.1.00.00', 'nama_akun' => 'Modal Disetor', 'jenis_mutasi' => 'Kredit'],
            ['id' => 32, 'parent_id' => 3, 'lev1' => 3, 'lev2' => 2, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '3.2.00.00', 'nama_akun' => 'Laba Rugi', 'jenis_mutasi' => 'Kredit'],
            
            ['id' => 41, 'parent_id' => 4, 'lev1' => 4, 'lev2' => 1, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '4.1.00.00', 'nama_akun' => 'Pendapatan Usaha', 'jenis_mutasi' => 'Kredit'],
            ['id' => 42, 'parent_id' => 4, 'lev1' => 4, 'lev2' => 2, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '4.2.00.00', 'nama_akun' => 'Pendapatan Non Usaha', 'jenis_mutasi' => 'Kredit'],
            ['id' => 43, 'parent_id' => 4, 'lev1' => 4, 'lev2' => 3, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '4.3.00.00', 'nama_akun' => 'Pendapatan Luar biasa', 'jenis_mutasi' => 'Kredit'],
            
            ['id' => 51, 'parent_id' => 5, 'lev1' => 5, 'lev2' => 1, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '5.1.00.00', 'nama_akun' => 'Beban Usaha', 'jenis_mutasi' => 'Debet'],
            ['id' => 52, 'parent_id' => 5, 'lev1' => 5, 'lev2' => 2, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '5.2.00.00', 'nama_akun' => 'Beban Pemasaran', 'jenis_mutasi' => 'Debet'],
            ['id' => 53, 'parent_id' => 5, 'lev1' => 5, 'lev2' => 3, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '5.3.00.00', 'nama_akun' => 'Beban Non Usaha', 'jenis_mutasi' => 'Debet'],
            ['id' => 54, 'parent_id' => 5, 'lev1' => 5, 'lev2' => 4, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '5.4.00.00', 'nama_akun' => 'Beban Pajak', 'jenis_mutasi' => 'Debet'],
        ];

        DB::table('akun_level_2')->insert($data);
    }
}
