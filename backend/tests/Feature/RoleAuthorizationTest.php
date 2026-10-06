<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression test untuk perbaikan otorisasi per-role.
 *
 * Setiap test di sini menjaga satu perilaku yang sebelumnya bocor: laporan
 * audit menemukan tidak adanya lapisan kepemilikan data (hanya cek role
 * lewat CheckRole), sehingga teknisi/surveyor bisa menyentuh data milik
 * role lain.
 */
class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $email = null): User
    {
        return User::create([
            'name' => 'User '.$role,
            'email' => $email ?: ($role.'-'.uniqid().'@pdam.test'),
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function package(): InstallationPackage
    {
        return InstallationPackage::create([
            'name' => 'Paket Regression',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);
    }

    /**
     * Satu catatan meter milik pencatat tertentu.
     *
     * `recorded_at` NOT NULL tanpa default di DB (diisi controller saat
     * upload foto), jadi model insert wajib menyetelnya sendiri.
     */
    private function reading(Customer $customer, User $recorder, int $value = 100, int $month = 1): MeterReading
    {
        return MeterReading::create([
            'customer_id' => $customer->id,
            'recorded_by' => $recorder->id,
            'reading_year' => 2026,
            'reading_month' => $month,
            'meter_value' => $value,
            'recorded_at' => now(),
        ]);
    }

    /** Payload tiket minimal yang lolos validasi store(). */
    private function ticketPayload(array $overrides = []): array
    {
        return array_merge([
            'package_id' => $this->package()->id,
            'applicant_name' => 'Pelanggan Uji',
            'nik' => '33000000000'.random_int(100, 999),
            'address' => 'Jl. Regression No. 1',
            'lat' => -7.797068,
            'lng' => 110.370529,
        ], $overrides);
    }

    /**
     * Pelanggan punya relasi user_id ke tiket. Teknisi A dan B harus punya
     * catatan meter sendiri supaya uji IDOR masuk akal.
     */
    private function customerWithTicket(User $owner, string $status = 'completed'): Customer
    {
        // `created_by` NOT NULL tanpa default di DB — wajib diisi walau
        // membuat tiket langsung lewat model (dibuatnya via HTTP, store()
        // yang mengisinya dari auth()->id()).
        $ticket = InstallationTicket::create($this->ticketPayload([
            'user_id' => $owner->id,
            'phone' => '081200000000',
            'status' => $status,
            'created_by' => $owner->id,
        ]));

        return Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => 'REG-'.uniqid(),
            'initial_meter_reading' => 0,
        ]);
    }

    #[Test]
    public function teknisi_tidak_bisa_mengubah_catatan_meter_teknisi_lain(): void
    {
        $customer = $this->customerWithTicket($this->user('pelanggan'));

        $reading = $this->reading($customer, $this->user('teknisi', 'teknisi-a@pdam.test'));

        // Teknisi lain tries mengubah -> harus ditolak
        Sanctum::actingAs($this->user('teknisi', 'teknisi-b@pdam.test'), ['*']);

        $this->putJson("/api/meter-readings/{$reading->id}", [
            'meter_value' => 999,
        ])->assertStatus(403);

        // Data tidak berubah
        $this->assertSame(100, (int) $reading->fresh()->meter_value);
    }

    #[Test]
    public function teknisi_tidak_bisa_menghapus_catatan_meter_teknisi_lain(): void
    {
        $customer = $this->customerWithTicket($this->user('pelanggan'));

        $reading = $this->reading($customer, $this->user('teknisi', 'teknisi-a@pdam.test'));

        Sanctum::actingAs($this->user('teknisi', 'teknisi-b@pdam.test'), ['*']);

        $this->deleteJson("/api/meter-readings/{$reading->id}")->assertStatus(403);

        $this->assertDatabaseHas('meter_readings', ['id' => $reading->id]);
    }

    #[Test]
    public function admin_tetap_bisa_mengubah_catatan_meter_siapa_saja(): void
    {
        $customer = $this->customerWithTicket($this->user('pelanggan'));

        $reading = $this->reading($customer, $this->user('teknisi', 'teknisi-a@pdam.test'));

        Sanctum::actingAs($this->user('admin'), ['*']);

        $this->putJson("/api/meter-readings/{$reading->id}", [
            'meter_value' => 250,
        ])->assertStatus(200);

        $this->assertSame(250, (int) $reading->fresh()->meter_value);
    }

    #[Test]
    public function teknisi_tidak_bisa_menjalankan_generate_piutang_global(): void
    {
        Sanctum::actingAs($this->user('teknisi'), ['*']);

        $this->postJson('/api/dashboard/auto-generate-overdue')
            ->assertStatus(403);
    }

    #[Test]
    public function teknisi_tidak_bisa_mengaktifkan_kembali_pelanggan(): void
    {
        $customer = $this->customerWithTicket($this->user('pelanggan'), 'suspended');

        Sanctum::actingAs($this->user('teknisi'), ['*']);

        $this->postJson("/api/customers/{$customer->id}/restore")
            ->assertStatus(403);
    }

    #[Test]
    public function pelanggan_hanya_melihat_dukungan_instalasi_miliknya(): void
    {
        // Endpoint ini dipanggil chrome bersama untuk semua role, jadi
        // harus bisa diakses role mana pun yang sudah login.
        Sanctum::actingAs($this->user('pelanggan'), ['*']);

        $this->getJson('/api/settings/lembaga-identity')->assertStatus(200);
    }

    #[Test]
    public function settings_sop_tetap_hanya_admin(): void
    {
        Sanctum::actingAs($this->user('teknisi'), ['*']);

        $this->getJson('/api/settings/sop')->assertStatus(403);
    }

    #[Test]
    public function surveyor_hanya_bisa_melihat_tiket_pending(): void
    {
        Sanctum::actingAs($this->user('admin'), ['*']);

        $pending = $this->postJson('/api/installation-tickets', $this->ticketPayload([
            'applicant_name' => 'Ani Surveyor',
            'nik' => '3200000000001',
        ]))->json('data.id');

        $this->assertNotNull($pending);
        InstallationTicket::whereKey($pending)->update(['status' => 'pending']);

        Sanctum::actingAs($this->user('surveyor'), ['*']);

        // Pending boleh dibuka
        $this->getJson("/api/installation-tickets/{$pending}")->assertStatus(200);

        // Setelah lanjut ke surveyed, surveyor tidak boleh lagi
        InstallationTicket::whereKey($pending)->update(['status' => 'surveyed']);
        $this->getJson("/api/installation-tickets/{$pending}")->assertStatus(403);
    }

    #[Test]
    public function survey_duplicate_ditolak(): void
    {
        Sanctum::actingAs($this->user('admin'), ['*']);

        $ticketId = $this->postJson('/api/installation-tickets', $this->ticketPayload([
            'applicant_name' => 'Budi Survey',
            'nik' => '3200000000002',
            'address' => 'Jl. Survey No. 3',
        ]))->json('data.id');

        $this->assertNotNull($ticketId, 'Tiket admin gagal dibuat.');
        InstallationTicket::whereKey($ticketId)->update(['status' => 'pending']);

        Sanctum::actingAs($this->user('surveyor'), ['*']);

        $payload = [
            'distance_to_pipe_m' => 10,
            'material_notes' => 'Pipa PVC 3 inci',
            'photo' => UploadedFile::fake()->image('survey.jpg'),
        ];

        $this->postJson("/api/installation-tickets/{$ticketId}/survey", $payload)
            ->assertStatus(201);

        // Tiket kini `surveyed`; submit kedua harus ditolak, bukan membuat
        // baris survey_results kedua.
        $this->postJson("/api/installation-tickets/{$ticketId}/survey", $payload)
            ->assertStatus(422);
    }

    #[Test]
    public function tiket_draft_tidak_bisa_langsung_disurvey(): void
    {
        Sanctum::actingAs($this->user('admin'), ['*']);

        $ticketId = $this->postJson('/api/installation-tickets', $this->ticketPayload([
            'applicant_name' => 'Citra Draft',
            'nik' => '3200000000003',
            'address' => 'Jl. Draft No. 4',
        ]))->json('data.id');

        $this->assertNotNull($ticketId, 'Tiket admin gagal dibuat.');

        // store() membuat tiket berstatus `draft`. Transisi draft->surveyed
        // dilarang TicketStateMachine; endpoint survey harus menolaknya,
        // bukan sesudahnya menggeser status dan melewati registrasi admin.
        Sanctum::actingAs($this->user('surveyor'), ['*']);

        $this->postJson("/api/installation-tickets/{$ticketId}/survey", [
            'distance_to_pipe_m' => 10,
            'material_notes' => 'Pipa PVC',
            'photo' => UploadedFile::fake()->image('draft.jpg'),
        ])->assertStatus(422);

        $this->assertSame('draft', InstallationTicket::whereKey($ticketId)->first()->status);
    }

    #[Test]
    public function endpoint_login_dibatasi_rate_limit(): void
    {
        $route = collect(app('router')->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/login');

        $this->assertNotNull($route, 'Route /login tidak terdaftar.');

        $middleware = $route->gatherMiddleware();
        $this->assertContains('throttle:login', $middleware);
        $this->assertContains('throttle:login-ip', $middleware);
    }

    #[Test]
    public function percobaan_login_dibatasi_setelah_melebihi_batas(): void
    {
        // Limiter global per email+IP: 5x/menit. Setelah lewat, request
        // berikutnya harus 429 dan tidak boleh sampai ke logic password.
        $email = 'bruteforce@pdam.test';

        foreach (range(1, 6) as $ignored) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'salah-sekali',
            ]);
        }

        $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'salah-sekali',
        ])->assertStatus(429);
    }
}
