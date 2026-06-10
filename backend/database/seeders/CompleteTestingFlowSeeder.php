<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompleteTestingFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai pembuatan data simulasi Customer Aktif...');

        // 1. Ambil staff penanggung jawab (ID: 3 - Ini Teknisi, atau ambil yang role teknisi)
        $staff = User::where('role', 'teknisi')->first() ?? User::find(3) ?? User::first();

        // 2. Ambil data tiket pendaftaran yang ada untuk diubah statusnya menjadi completed
        // Kita ambil contoh tiket ID 18, 28, dan 29 yang sudah ada datanya
        $ticketIds = [18, 28, 29];
        
        DB::transaction(function () use ($ticketIds, $staff) {
            
            // Update status tiket menjadi 'completed' di database agar sesuai konsep Anda
            DB::table('installation_tickets')
                ->whereIn('id', $ticketIds)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            // 3. Loop untuk memasukkan data ke tabel customers & meter_readings
            foreach ($ticketIds as $index => $ticketId) {
                
                // Ambil data detail tiket untuk relasi user_id
                $ticket = DB::table('installation_tickets')->find($ticketId);
                
                if (!$ticket) {
                    continue; // Skip jika ID tiket tidak ditemukan di DB
                }

                // Cek apakah customer untuk tiket ini sudah pernah dibuat sebelumnya
                $customer = Customer::where('ticket_id', $ticketId)->first();

                if (!$customer) {
                    // Generate nomor customer unik, misal: CUST-2026-0001
                    $customerCode = 'CUST-' . date('Y') . '-' . str_pad($ticketId, 4, '0', STR_PAD_LEFT);

                    // Insert ke tabel customers sesuai struktur kolom Anda
                    $customer = Customer::create([
                        'ticket_id'              => $ticketId,
                        'user_id'                => $ticket->user_id, // Mengambil user_id dari tiket (Andi / Teknisi)
                        'customer_code'          => $customerCode,
                        'initial_meter_reading'  => 0,
                        'meter_photo_url'        => 'customers/initial_meter.jpg',
                        'activated_at'           => now(),
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);
                }

                // 4. Buat data meteran dummy di tabel meter_readings untuk Bulan Juni 2026
                $meterAwal = rand(10, 50);
                $meterAkhir = $meterAwal + rand(12, 35); // Simulasi pemakaian air

                // Pastikan tidak duplikat untuk periode Juni 2026
                $existsReading = MeterReading::where('customer_id', $customer->id)
                    ->where('reading_month', 6)
                    ->where('reading_year', 2026)
                    ->exists();

                if (!$existsReading) {
                    MeterReading::create([
                        'customer_id'   => $customer->id,
                        'recorded_by'   => $staff->id,
                        'reading_month' => 6,        // Juni
                        'reading_year'  => 2026,     // 2026
                        'meter_value'   => $meterAkhir,
                        'photo_url'     => 'meter-readings/dummy_juni.jpg',
                        'recorded_at'   => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        });

        $this->command->info('Sukses! Tiket berhasil disimulasikan ke Completed, data Customers terisi, dan data Meter Readings siap digunakan untuk testing.');
    }
}
