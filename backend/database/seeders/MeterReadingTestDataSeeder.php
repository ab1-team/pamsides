<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeterReadingTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil ID user pertama sebagai petugas pencatat (recorded_by)
        // Jika tidak ada user sama sekali, buat satu petugas dummy
        $staff = User::first() ?? User::factory()->create(['name' => 'Petugas Lapangan']);

        // 2. Ambil semua data customer yang ada di database Anda
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('Data customer masih kosong. Silakan isi tabel customers terlebih dahulu sebelum menjalankan seeder ini.');
            return;
        }

        $this->command->info('Memulai insert data testing meter_readings...');

        // Gunakan database transaction agar aman
        DB::transaction(function () use ($customers, $staff) {
            foreach ($customers as $customer) {
                // Tentukan angka meter acak untuk kebutuhan testing
                $meterAwal = rand(100, 150);
                $meterAkhir = $meterAwal + rand(5, 25); // pemakaian berkisar antara 5 - 25 m³

                // Cek apakah data untuk customer ini di Juni 2026 sudah ada
                $exists = MeterReading::where('customer_id', $customer->id)
                    ->where('reading_month', 6)
                    ->where('reading_year', 2026)
                    ->exists();

                if (!$exists) {
                    MeterReading::create([
                        'customer_id'   => $customer->id,
                        'recorded_by'   => $staff->id,
                        'reading_month' => 6,        
                        'reading_year'  => 2026,     
                        'meter_value'   => $meterAkhir,
                        'previous_reading' => $meterAwal, // Jika kolom ini ada di database Anda
                        'photo_url'     => 'meter-readings/dummy_photo.jpg',
                        'recorded_at'   => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        });

        $this->command->info('Berhasil menambahkan data meteran dummy untuk ' . $customers->count() . ' pelanggan!');
    }
}
