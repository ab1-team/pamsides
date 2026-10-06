<?php

namespace Tests\Feature;

use App\Models\InstallationPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallationTicketTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        return User::create([
            'name'     => 'Test User',
            'email'    => $role . '@pdam.test',
            'password' => Hash::make('password'),
            'role'     => $role,
        ]);
    }

    private function createPackage(): InstallationPackage
    {
        return InstallationPackage::create([
            'name'             => 'Paket Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty'     => 10_000,
        ]);
    }

    #[Test]
    public function admin_dapat_membuat_tiket(): void
    {
        Sanctum::actingAs($this->createUser('admin'), ['*']);
        $package = $this->createPackage();

        $response = $this->postJson('/api/installation-tickets', [
            'package_id'     => $package->id,
            'applicant_name' => 'Budi Santoso',
            'nik'            => '3300000000000001',
            'address'        => 'Jl. Test No. 1',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['status' => 'draft'],
                 ]);
    }

    #[Test]
    public function admin_dapat_melihat_daftar_tiket(): void
    {
        Sanctum::actingAs($this->createUser('admin'), ['*']);

        $response = $this->getJson('/api/installation-tickets');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    #[Test]
    public function state_machine_transisi_valid(): void
    {
        Sanctum::actingAs($this->createUser('admin'), ['*']);
        $package = $this->createPackage();

        $tiket = $this->postJson('/api/installation-tickets', [
            'package_id'     => $package->id,
            'applicant_name' => 'Siti Rahayu',
            'nik'            => '3300000000000002',
            'address'        => 'Jl. Test No. 2',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
        ]);

        $ticketId = $tiket->json('data.id');

        // draft -> pending (transisi valid)
        $this->patchJson("/api/installation-tickets/{$ticketId}/transition", [
            'status' => 'pending',
        ])->assertStatus(200)
          ->assertJson(['success' => true, 'data' => ['status' => 'pending']]);

        // pending -> surveyed (transisi valid)
        $response = $this->patchJson("/api/installation-tickets/{$ticketId}/transition", [
            'status' => 'surveyed',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['status' => 'surveyed'],
                 ]);
    }

    #[Test]
    public function state_machine_transisi_tidak_valid_ditolak(): void
    {
        Sanctum::actingAs($this->createUser('admin'), ['*']);
        $package = $this->createPackage();

        $tiket = $this->postJson('/api/installation-tickets', [
            'package_id'     => $package->id,
            'applicant_name' => 'Eko Prasetyo',
            'nik'            => '3300000000000003',
            'address'        => 'Jl. Test No. 3',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
        ]);

        $ticketId = $tiket->json('data.id');

        $response = $this->patchJson("/api/installation-tickets/{$ticketId}/transition", [
            'status' => 'completed',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function pelanggan_tidak_bisa_akses_tiket(): void
    {
        Sanctum::actingAs($this->createUser('pelanggan'), ['*']);

        $response = $this->getJson('/api/installation-tickets');

        $response->assertStatus(403);
    }

    /**
     * Teknisi BOLEH membuka daftar/detail tiket, tapi hanya tiket berstatus
     * instalasi. Endpoint-nya dibuka ke `role:admin,surveyor,teknisi`
     * (sebelumnya teknisi kena 403 sehingga halaman Hasil Instalasi tidak
     * bisa dipakai sama sekali), dan barisnya tetap di-scope lewat
     * User::TICKET_STATUSES_FOR_TEKNISI.
     */
    #[Test]
    public function teknisi_hanya_melihat_tiket_tahap_instalasi(): void
    {
        $package = $this->createPackage();

        // Tiket harus dibuat sebagai ADMIN: store() hanya boleh admin, jadi
        // kalau sesi masih teknisi, POST-nya 403 dan $draft bernilai null.
        Sanctum::actingAs($this->createUser('admin'), ['*']);

        // Tiket baru dibuat berstatus `draft` (lihat store()), yaitu masih
        // pekerjaan awal admin dan di luar kewenangan teknisi.
        $draft = $this->postJson('/api/installation-tickets', [
            'package_id'     => $package->id,
            'applicant_name' => 'Tiwik Teknisi',
            'nik'            => '3300000000000099',
            'address'        => 'Jl. Uji Coba No. 9',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
        ])->json('data.id');

        $this->assertNotNull($draft, 'Tiket admin gagal dibuat.');

        Sanctum::actingAs($this->createUser('teknisi'), ['*']);

        $index = $this->getJson('/api/installation-tickets');
        $index->assertStatus(200);
        $index->assertJsonPath('data.total', 0);

        // Membuka detail tiket draft secara langsung juga harus ditolak.
        $this->getJson("/api/installation-tickets/{$draft}")->assertStatus(403);
    }

    /**
     * Guard: `isPrivileged()` hanya boleh true untuk admin. Kalau role lain
     * ikut lolos, semua scoping kepemilikan data jadi tidak berarti.
     */
    #[Test]
    public function dropdown_registrasi_tetap_admin_saja(): void
    {
        Sanctum::actingAs($this->createUser('surveyor'), ['*']);

        $this->getJson('/api/installation-tickets/register-dropdown')
            ->assertStatus(403);
    }
}
