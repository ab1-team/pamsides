<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataPelaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Matikan proteksi foreign key & bersihkan data lama agar tidak duplikat saat seeding ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('monthly_bills')->truncate();
        DB::table('customers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Insert data ke tabel induk (customers) terlebih dahulu
        DB::table('customers')->insert([
            [
                'id' => 1,
                'ticket_id' => 18, // Mengikat ke tiket Irwansyah (Status: Completed)
                'user_id' => 3,
                'customer_code' => 'CUST-2026-0001',
                'initial_meter_reading' => 0,
                'meter_photo_url' => 'photos/meter1.jpg',
                'activated_at' => '2026-05-13 06:34:39',
                'created_at' => '2026-05-13 06:34:39',
                'updated_at' => '2026-05-13 06:34:39',
            ],
            [
                'id' => 2,
                'ticket_id' => 29, // Mengikat ke tiket Faril Raehan (Status: Completed)
                'user_id' => 3,
                'customer_code' => 'CUST-2026-0002',
                'initial_meter_reading' => 0,
                'meter_photo_url' => 'photos/meter2.jpg',
                'activated_at' => '2026-06-01 13:13:26',
                'created_at' => '2026-06-01 13:13:26',
                'updated_at' => '2026-06-01 13:13:26',
            ]
        ]);

        // 3. Masukkan data transaksional ke tabel monthly_bills (Aman dari FK Constraint)
        DB::table('monthly_bills')->insert([
            [
                'customer_id' => 1,
                'billing_period_year' => 2026,
                'billing_period_month' => 5,
                'meter_reading_start' => 0,
                'meter_reading_end' => 15,
                'usage_m3' => 15,
                'usage_charge' => 25000.00,
                'abodemen' => 5000.00,
                'penalty_amount' => 0.00,
                'total_amount' => 30000.00,
                'status' => 'paid',
                'due_date' => '2026-05-20',
                'created_at' => '2026-05-01 07:00:00',
                'updated_at' => '2026-05-05 09:00:00',
            ],
            [
                'customer_id' => 1,
                'billing_period_year' => 2026,
                'billing_period_month' => 6,
                'meter_reading_start' => 15,
                'meter_reading_end' => 32,
                'usage_m3' => 17,
                'usage_charge' => 34000.00,
                'abodemen' => 5000.00,
                'penalty_amount' => 0.00,
                'total_amount' => 39000.00,
                'status' => 'unpaid',
                'due_date' => '2026-06-20',
                'created_at' => '2026-06-01 07:00:00',
                'updated_at' => '2026-06-01 07:00:00',
            ],
            [
                'customer_id' => 2,
                'billing_period_year' => 2026,
                'billing_period_month' => 6,
                'meter_reading_start' => 0,
                'meter_reading_end' => 10,
                'usage_m3' => 10,
                'usage_charge' => 15000.00,
                'abodemen' => 5000.00,
                'penalty_amount' => 0.00,
                'total_amount' => 20000.00,
                'status' => 'paid',
                'due_date' => '2026-06-20',
                'created_at' => '2026-06-01 07:00:00',
                'updated_at' => '2026-06-02 14:00:00',
            ]
        ]);
    }
}
