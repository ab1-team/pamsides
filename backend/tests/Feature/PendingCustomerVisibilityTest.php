<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendingCustomerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teknisi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-pending@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->teknisi = User::create([
            'name' => 'Wanto Uji',
            'email' => 'teknisi-pending@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
        ]);
    }

    private function makePelanggan(string $status = 'pending'): array
    {
        $package = InstallationPackage::create([
            'name' => 'Paket Uji',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        $owner = User::create([
            'name' => 'Suwarno Uji',
            'email' => uniqid().'@pelanggan.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $this->teknisi->id,
            'applicant_name' => 'Suwarno Uji',
            'nik' => '3310010501570001',
            'address' => 'Jl. Uji No. 1',
            'lat' => 0,
            'lng' => 0,
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);

        $customer = Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => '5.0002.007.670',
            'initial_meter_reading' => 0,
        ]);

        return [$customer, $ticket, $package];
    }

    #[Test]
    public function pencarian_pelanggan_menemukan_tiket_pending_lewat_kode_pelanggan(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        [$customer] = $this->makePelanggan('pending');

        $res = $this->getJson('/api/customers/search?search='.urlencode('5.0002.007.670'));

        $res->assertOk();
        $this->assertSame(
            $customer->id,
            $res->json('data.0.id'),
            'Pelanggan dengan tiket pending harus bisa dicari lewat customer_code.'
        );
    }

    #[Test]
    public function daftar_pemakaian_menampilkan_tagihan_pelanggan_pending(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        [$customer] = $this->makePelanggan('pending');

        MonthlyBill::create([
            'customer_id' => $customer->id,
            'billing_period_year' => 2026,
            'billing_period_month' => 9,
            'meter_segment' => 'normal',
            'meter_reading_start' => 58,
            'meter_reading_end' => 74,
            'usage_m3' => 16,
            'usage_charge' => 64_000,
            'abodemen' => 10_000,
            'penalty_amount' => 0,
            'total_amount' => 74_000,
            'status' => 'unpaid',
            'due_date' => '2026-10-27',
        ]);

        $res = $this->getJson('/api/monthly-bills/usage?month=9&year=2026&user_id='.$this->teknisi->id);

        $res->assertOk();
        $row = collect($res->json('data'))->firstWhere('id', $customer->id);

        $this->assertNotNull(
            $row,
            'Pelanggan pending yang sudah punya tagihan harus tampil di Data Pemakaian Air.'
        );
        $this->assertSame(74_000, (int) $row['tagihan']);
    }

    #[Test]
    public function pencatatan_meter_menampilkan_pelanggan_pending_yang_belum_dicatat(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        [$customer] = $this->makePelanggan('pending');

        $res = $this->getJson('/api/meter-readings/pending?month=9&year=2026');

        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('id');

        $this->assertTrue(
            $ids->contains($customer->id),
            'Pelanggan pending yang belum dicatat harus muncul di daftar pencatatan meter.'
        );
    }
}
