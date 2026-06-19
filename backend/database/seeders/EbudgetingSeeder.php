<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EbudgetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu sebelum diisi
        DB::table('ebudgeting')->truncate();

        $data = [
            ['id' => 1, 'account_id' => 48, 'tahun' => 2025, 'bulan' => 3, 'jumlah' => 100000000],
            ['id' => 2, 'account_id' => 49, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 3, 'account_id' => 50, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 4, 'account_id' => 51, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 5, 'account_id' => 52, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 6, 'account_id' => 53, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 7, 'account_id' => 55, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 8, 'account_id' => 56, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 9, 'account_id' => 57, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 10, 'account_id' => 58, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 11, 'account_id' => 59, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 12, 'account_id' => 60, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 13, 'account_id' => 61, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 14, 'account_id' => 62, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 15, 'account_id' => 63, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 16, 'account_id' => 64, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 17, 'account_id' => 65, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 18, 'account_id' => 66, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 19, 'account_id' => 67, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 20, 'account_id' => 68, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 21, 'account_id' => 69, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 22, 'account_id' => 70, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 23, 'account_id' => 71, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 24, 'account_id' => 72, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 25, 'account_id' => 73, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 26, 'account_id' => 74, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 27, 'account_id' => 75, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 28, 'account_id' => 76, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 29, 'account_id' => 77, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 30, 'account_id' => 78, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 31, 'account_id' => 79, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 32, 'account_id' => 80, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 33, 'account_id' => 81, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 34, 'account_id' => 82, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 35, 'account_id' => 83, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 36, 'account_id' => 84, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 37, 'account_id' => 85, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 38, 'account_id' => 86, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 39, 'account_id' => 87, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 40, 'account_id' => 88, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 41, 'account_id' => 89, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 42, 'account_id' => 90, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 43, 'account_id' => 91, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
            ['id' => 44, 'account_id' => 92, 'tahun' => 2024, 'bulan' => 3, 'jumlah' => 0],
        ];

        DB::table('ebudgeting')->insert($data);
    }
}