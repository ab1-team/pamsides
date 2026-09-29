<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping id legacy (sesuai gambar positions: 1=Direktur, dst)
        // id di-set eksplisit supaya konsisten dengan data legacy & mudah direferensikan.
        $jabatans = [
            1 => 'Direktur',
            2 => 'Sekretaris',
            3 => 'Bendahara',
            4 => 'Pengawas',
            5 => 'Caters',
            6 => 'Pos Bayar',
            7 => 'Teknisi',
            8 => 'Ketua',
        ];

        foreach ($jabatans as $id => $nama) {
            Jabatan::updateOrCreate(
                ['id' => $id],
                ['nama_jabatan' => $nama]
            );
        }
    }
}
