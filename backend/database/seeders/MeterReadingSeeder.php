<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeterReadingSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan data lama agar tidak bentrok saat run ulang
        DB::table('meter_readings')->delete();

        DB::table('meter_readings')->insert([
            // DATA ANDI PELANGGAN (customer_id: 1)
            [
                'customer_id' => 1,
                'recorded_by' => 3, // ID Ini Teknisi
                'reading_year' => 2026,
                'reading_month' => 3, // Maret 2026
                'meter_value' => 45,
                'photo_url' => 'meter-readings/sample1.jpg',
                'recorded_at' => Carbon::create(2026, 3, 25, 9, 0, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 1,
                'recorded_by' => 3,
                'reading_year' => 2026,
                'reading_month' => 4, // April 2026
                'meter_value' => 85,
                'photo_url' => 'meter-readings/sample2.jpg',
                'recorded_at' => Carbon::create(2026, 4, 25, 9, 15, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 1,
                'recorded_by' => 3,
                'reading_year' => 2026,
                'reading_month' => 5, // Mei 2026 (Muncul di filter halaman utama)
                'meter_value' => 120,
                'photo_url' => 'meter-readings/sample3.jpg',
                'recorded_at' => Carbon::create(2026, 5, 25, 10, 0, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DATA SASMINTO (customer_id: 15)
            [
                'customer_id' => 15,
                'recorded_by' => 3,
                'reading_year' => 2026,
                'reading_month' => 4, // April 2026
                'meter_value' => 25,
                'photo_url' => 'meter-readings/sample4.jpg',
                'recorded_at' => Carbon::create(2026, 4, 25, 9, 30, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 15,
                'recorded_by' => 3,
                'reading_year' => 2026,
                'reading_month' => 5, // Mei 2026 (Muncul di filter halaman utama)
                'meter_value' => 60,
                'photo_url' => 'meter-readings/sample5.jpg',
                'recorded_at' => Carbon::create(2026, 5, 25, 10, 20, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
