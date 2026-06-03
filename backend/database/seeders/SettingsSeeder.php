<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->insert([
            [
                'key' => 'lembaga',
                'value' => json_encode([
                    'nama_lembaga' => 'BUMDes Tirta Makmur',
                    'alamat' => 'Desa Sonosari',
                    'telepon' => '08123456789'
                ])
            ],
            [
                'key' => 'sistem_tagihan',
                'value' => json_encode([
                    'jatuh_tempo' => 20,
                    'denda' => 5000
                ])
            ],
            [
                'key' => 'pasang_baru',
                'value' => json_encode([
                    'biaya_pendaftaran' => 150000,
                    'estimasi_hari' => 3
                ])
            ],
            [
                'key' => 'whatsapp',
                'value' => json_encode([
                    'template_tagihan' => 'Tagihan Anda {{total}}',
                    'template_pasang_baru' => 'Pengajuan diproses'
                ])
            ],
            [
                'key' => 'logo',
                'value' => json_encode([
                    'logo' => 'sop/logo/test.png'
                ])
            ]
        ]);
    }
}
