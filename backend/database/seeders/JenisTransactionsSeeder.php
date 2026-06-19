<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('jenis_transactions')->truncate();

        $data = [
            ['id' => 1, 'nama_jt' => 'Aset Masuk'],
            ['id' => 2, 'nama_jt' => 'Aset Keluar'],
            ['id' => 3, 'nama_jt' => 'Pemindahan Saldo / Aset'],
        ];

        DB::table('jenis_transactions')->insert($data);
    }
}
