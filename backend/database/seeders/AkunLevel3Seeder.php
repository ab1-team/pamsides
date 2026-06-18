<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkunLevel3Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum diisi
        DB::table('akun_level_3')->truncate();

        $data = [
            // ASET (Aset Lancar)
            ['id' => 111, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '1.1.01.00', 'nama_akun' => 'Kas', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 112, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '1.1.02.00', 'nama_akun' => 'Kas Setara Kas', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 113, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '1.1.03.00', 'nama_akun' => 'Piutang', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 114, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 4, 'lev4' => 0, 'kode_akun' => '1.1.04.00', 'nama_akun' => 'Cadangan Kerugian Piutang', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 115, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 5, 'lev4' => 0, 'kode_akun' => '1.1.05.00', 'nama_akun' => 'Rekening antar Kantor', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 116, 'parent_id' => 11, 'lev1' => 1, 'lev2' => 1, 'lev3' => 6, 'lev4' => 0, 'kode_akun' => '1.1.06.00', 'nama_akun' => 'Investasi', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            
            // ASET (Aset Tidak Lancar)
            ['id' => 121, 'parent_id' => 12, 'lev1' => 1, 'lev2' => 2, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '1.2.01.00', 'nama_akun' => 'Aktiva Tetap dan Inventaris', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 122, 'parent_id' => 12, 'lev1' => 1, 'lev2' => 2, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '1.2.02.00', 'nama_akun' => 'Akumulasi Penyusutan Aktiva Tetap dan Inventaris', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 123, 'parent_id' => 12, 'lev1' => 1, 'lev2' => 2, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '1.2.03.00', 'nama_akun' => 'Aset Tak Berwujud', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 124, 'parent_id' => 12, 'lev1' => 1, 'lev2' => 2, 'lev3' => 4, 'lev4' => 0, 'kode_akun' => '1.2.04.00', 'nama_akun' => 'Akumulasi Amortisasi Aset Tak Berwujud', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 125, 'parent_id' => 12, 'lev1' => 1, 'lev2' => 2, 'lev3' => 5, 'lev4' => 0, 'kode_akun' => '1.2.05.00', 'nama_akun' => 'Konstruksi Dalam Pengerjaan', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            
            // ASET (Aset Lain-lain)
            ['id' => 131, 'parent_id' => 13, 'lev1' => 1, 'lev2' => 3, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '1.3.01.00', 'nama_akun' => 'Aset Lain-lain', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            
            // UTANG (Jangka Pendek)
            ['id' => 211, 'parent_id' => 21, 'lev1' => 2, 'lev2' => 1, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '2.1.01.00', 'nama_akun' => 'Utang Dividen', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 212, 'parent_id' => 21, 'lev1' => 2, 'lev2' => 1, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '2.1.02.00', 'nama_akun' => 'Utang Biaya Operasional', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 213, 'parent_id' => 21, 'lev1' => 2, 'lev2' => 1, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '2.1.03.00', 'nama_akun' => 'Utang Pajak', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 214, 'parent_id' => 21, 'lev1' => 2, 'lev2' => 1, 'lev3' => 4, 'lev4' => 0, 'kode_akun' => '2.1.04.00', 'nama_akun' => 'Simpanan Jangka Pendek', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 215, 'parent_id' => 21, 'lev1' => 2, 'lev2' => 1, 'lev3' => 5, 'lev4' => 0, 'kode_akun' => '2.1.05.00', 'nama_akun' => 'Utang Jangka Pendek Lainnya', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            
            // UTANG (Jangka Panjang)
            ['id' => 221, 'parent_id' => 22, 'lev1' => 2, 'lev2' => 2, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '2.2.01.00', 'nama_akun' => 'Utang Jangka Panjang Lainnya', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 222, 'parent_id' => 22, 'lev1' => 2, 'lev2' => 2, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '2.2.02.00', 'nama_akun' => 'Simpanan Jangka Panjang', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            
            // MODAL
            ['id' => 311, 'parent_id' => 31, 'lev1' => 3, 'lev2' => 1, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '3.1.01.00', 'nama_akun' => 'Modal Disetor', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 312, 'parent_id' => 31, 'lev1' => 3, 'lev2' => 1, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '3.1.02.00', 'nama_akun' => 'Modal Lain-lain', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 321, 'parent_id' => 32, 'lev1' => 3, 'lev2' => 2, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '3.2.01.00', 'nama_akun' => 'Laba Ditahan', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 322, 'parent_id' => 32, 'lev1' => 3, 'lev2' => 2, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '3.2.02.00', 'nama_akun' => 'Laba Rugi Berjalan', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            
            // PENDAPATAN
            ['id' => 411, 'parent_id' => 41, 'lev1' => 4, 'lev2' => 1, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '4.1.01.00', 'nama_akun' => 'Pendapatan Usaha Utama', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 412, 'parent_id' => 41, 'lev1' => 4, 'lev2' => 1, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '4.1.02.00', 'nama_akun' => 'Pendapatan Usaha Lain', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 413, 'parent_id' => 41, 'lev1' => 4, 'lev2' => 1, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '4.1.03.00', 'nama_akun' => 'Pendapatan Usaha Lain Lainnya', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 421, 'parent_id' => 42, 'lev1' => 4, 'lev2' => 2, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '4.2.01.00', 'nama_akun' => 'Pendapatan Non Usaha', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            ['id' => 431, 'parent_id' => 43, 'lev1' => 4, 'lev2' => 3, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '4.3.01.00', 'nama_akun' => 'Pendapatan Luar biasa', 'posisi' => 1, 'jenis_mutasi' => 'kredit'],
            
            // BEBAN
            ['id' => 511, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '5.1.01.00', 'nama_akun' => 'Beban Gaji dan Honor', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 512, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '5.1.02.00', 'nama_akun' => 'Beban Tunjangan dan Bonus', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 513, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '5.1.03.00', 'nama_akun' => 'Beban ATK dan Umum', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 514, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 4, 'lev4' => 0, 'kode_akun' => '5.1.04.00', 'nama_akun' => 'Beban Rapat', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 515, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 5, 'lev4' => 0, 'kode_akun' => '5.1.05.00', 'nama_akun' => 'Transportasi dan Perjalanan Dinas', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 516, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 6, 'lev4' => 0, 'kode_akun' => '5.1.06.00', 'nama_akun' => 'Beban Penyisihan Cadangan', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 517, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 7, 'lev4' => 0, 'kode_akun' => '5.1.07.00', 'nama_akun' => 'Beban Penyusutan dan Amortisasi', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 518, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 8, 'lev4' => 0, 'kode_akun' => '5.1.08.00', 'nama_akun' => 'Beban Usaha Lainnya', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 519, 'parent_id' => 51, 'lev1' => 5, 'lev2' => 1, 'lev3' => 9, 'lev4' => 0, 'kode_akun' => '5.1.09.00', 'nama_akun' => 'Beban Bunga Utang', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 521, 'parent_id' => 52, 'lev1' => 5, 'lev2' => 2, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '5.2.01.00', 'nama_akun' => 'Beban Pemasaran', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 531, 'parent_id' => 53, 'lev1' => 5, 'lev2' => 3, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '5.3.01.00', 'nama_akun' => 'Beban Pajak, Bunga dan Administrasi Bank', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 532, 'parent_id' => 53, 'lev1' => 5, 'lev2' => 3, 'lev3' => 2, 'lev4' => 0, 'kode_akun' => '5.3.02.00', 'nama_akun' => 'Beban Penghapusan Aset Tetap', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 533, 'parent_id' => 53, 'lev1' => 5, 'lev2' => 3, 'lev3' => 3, 'lev4' => 0, 'kode_akun' => '5.3.03.00', 'nama_akun' => 'Beban Non Usaha Lainnya', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
            ['id' => 541, 'parent_id' => 54, 'lev1' => 5, 'lev2' => 4, 'lev3' => 1, 'lev4' => 0, 'kode_akun' => '5.4.01.00', 'nama_akun' => 'Beban PPh', 'posisi' => 1, 'jenis_mutasi' => 'debet'],
        ];

        DB::table('akun_level_3')->insert($data);
    }
}
