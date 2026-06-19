<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkunLevel1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum diisi
        DB::table('akun_level_1')->truncate();

        $data = [
            ['id' => 1, 'lev1' => 1, 'lev2' => 0, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '1.0.00.00', 'nama_akun' => 'Aset', 'jenis_mutasi' => 'Debet'],
            ['id' => 2, 'lev1' => 2, 'lev2' => 0, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '2.0.00.00', 'nama_akun' => 'Utang', 'jenis_mutasi' => 'Kredit'],
            ['id' => 3, 'lev1' => 3, 'lev2' => 0, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '3.0.00.00', 'nama_akun' => 'Modal', 'jenis_mutasi' => 'Kredit'],
            ['id' => 4, 'lev1' => 4, 'lev2' => 0, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '4.0.00.00', 'nama_akun' => 'Pendapatan', 'jenis_mutasi' => 'Kredit'],
            ['id' => 5, 'lev1' => 5, 'lev2' => 0, 'lev3' => 0, 'lev4' => 0, 'kode_akun' => '5.0.00.00', 'nama_akun' => 'Beban', 'jenis_mutasi' => 'Debet'],
        ];

        DB::table('akun_level_1')->insert($data);
    }
}
