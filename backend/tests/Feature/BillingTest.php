<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\User;
use App\Models\WaterTariffBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);
    }
    private function setupCustomerWithMeterReading(): array
    {
        // Buat paket
        $package = InstallationPackage::create([
            'name'             => 'Paket Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty'     => 10_000,
        ]);

        WaterTariffBlock::insert([
            ['package_id' => $package->id, 'usage_min_m3' => 0,  'usage_max_m3' => 10,   'price_per_m3' => 1_500, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $package->id, 'usage_min_m3' => 10, 'usage_max_m3' => 20,   'price_per_m3' => 2_000, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $package->id, 'usage_min_m3' => 20, 'usage_max_m3' => null, 'price_per_m3' => 2_500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Buat user pelanggan
        $userPelanggan = User::create([
            'name'     => 'Pelanggan Test',
            'email'    => 'pelanggan@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'pelanggan',
        ]);

        // Buat tiket
        $admin = $this->admin;
        $ticket = InstallationTicket::create([
            'package_id'     => $package->id,
            'applicant_name' => 'Pelanggan Test',
            'nik'            => '3300000000000001',
            'address'        => 'Jl. Test No. 1',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
            'status'         => 'completed',
            'created_by'     => $admin->id,
        ]);

        // Buat customer
        $customer = Customer::create([
            'ticket_id'             => $ticket->id,
            'user_id'               => $userPelanggan->id,
            'customer_code'         => 'PDAM-TEST-00001',
            'initial_meter_reading' => 0,
            'meter_photo_url'       => '/storage/test.jpg',
            'activated_at'          => now(),
        ]);

        // Buat meter reading bulan ini
        $teknisi = User::create([
            'name'     => 'Teknisi Test',
            'email'    => 'teknisi@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'teknisi',
        ]);

        MeterReading::create([
            'customer_id'   => $customer->id,
            'recorded_by'   => $teknisi->id,
            'reading_year'  => now()->year,
            'reading_month' => now()->month,
            'meter_value'   => 25,
            'photo_url'     => '/storage/test.jpg',
            'recorded_at'   => now(),
        ]);

        return compact('package', 'customer', 'ticket');
    }

    #[Test]
    public function admin_dapat_generate_tagihan(): void
    {
        $admin = $this->admin;
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $this->setupCustomerWithMeterReading();

        $response = $this->postJson('/api/bills/generate', [
            'year'  => now()->year,
            'month' => now()->month,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'message'     => 'Tagihan berhasil digenerate.',
                         'total_bills' => 1,
                     ],
                 ]);
    }

    #[Test]
    public function generate_tagihan_gagal_jika_meter_belum_semua_dicatat(): void
    {
        $admin = $this->admin;
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        // Setup customer tanpa meter reading
        $package = InstallationPackage::create([
            'name'             => 'Paket Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty'     => 10_000,
        ]);

        $userPelanggan = User::create([
            'name'     => 'Pelanggan Test',
            'email'    => 'pelanggan@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id'     => $package->id,
            'applicant_name' => 'Pelanggan Test',
            'nik'            => '3300000000000001',
            'address'        => 'Jl. Test No. 1',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
            'status'         => 'completed',
            'created_by'     => $admin->id,
        ]);

        Customer::create([
            'ticket_id'             => $ticket->id,
            'user_id'               => $userPelanggan->id,
            'customer_code'         => 'PDAM-TEST-00001',
            'initial_meter_reading' => 0,
            'meter_photo_url'       => '/storage/test.jpg',
            'activated_at'          => now(),
        ]);

        $response = $this->postJson('/api/bills/generate', [
            'year'  => now()->year,
            'month' => now()->month,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);
    }

    #[Test]
    public function generate_tagihan_gagal_jika_sudah_pernah_digenerate(): void
    {
        $admin = $this->admin;
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $this->setupCustomerWithMeterReading();

        // Generate pertama
        $this->postJson('/api/bills/generate', [
            'year'  => now()->year,
            'month' => now()->month,
        ]);

        // Generate kedua — harus gagal
        $response = $this->postJson('/api/bills/generate', [
            'year'  => now()->year,
            'month' => now()->month,
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);
    }

    #[Test]
    public function admin_dapat_melihat_detail_tagihan(): void
    {
        $admin = $this->admin;
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $this->setupCustomerWithMeterReading();

        // Generate tagihan
        $this->postJson('/api/bills/generate', [
            'year'  => now()->year,
            'month' => now()->month,
        ]);

        // Lihat detail tagihan id 1
        $response = $this->getJson('/api/bills/1');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
