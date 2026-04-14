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
                     'data'    => ['status' => 'pending'],
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
    public function non_admin_tidak_bisa_akses_tiket(): void
    {
        Sanctum::actingAs($this->createUser('teknisi'), ['*']);

        $response = $this->getJson('/api/installation-tickets');

        $response->assertStatus(403);
    }
}
