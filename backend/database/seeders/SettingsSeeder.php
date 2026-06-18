<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            [
                'key' => 'sop',
                'value' => '',
                'nama' => 'PAMSIDES Tirto Mulo',
                'alamat' => 'Desa Sonosari, Kecamatan Karangmalang, Kabupaten Sragen',
                'email' => 'admin@tirtomulo.desa.id',
                'telepon' => '08123456789',
                'domain' => 'https://tirtomulo.desa.id',
                'status_pembayaran' => true,
                'batas_tagihan' => 10,
                'toleransi_tunggakan' => 3,
                'logo' => null,
                'pesan_tagihan' => 'Yth. {customer}, tagihan air bulan ini sebesar {jumlah_tagihan}. Mohon segera melakukan pembayaran sebelum jatuh tempo. Terima kasih.',
                'pesan_pembayaran' => 'Yth. {customer}, pembayaran tagihan air sebesar {tagihan} telah kami terima. Terima kasih.',
                'updated_at' => now(),
            ]
        );

        // Hapus baris duplikat dari seeder lama (key-value lama)
        DB::table('settings')->where('id', '!=', 1)->delete();
    }
}
