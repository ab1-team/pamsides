<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordForceChangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $mustChange, string $role = 'admin'): User
    {
        return User::create([
            'name' => 'User Force',
            'email' => 'force@pdam.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'must_change_password' => $mustChange,
        ]);
    }

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('auth_token')->plainTextToken];
    }

    #[Test]
    public function login_menyertakan_flag_must_change_password(): void
    {
        $this->makeUser(true);

        $response = $this->postJson('/api/login', [
            'email' => 'force@pdam.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);
    }

    #[Test]
    public function login_user_normal_flag_false(): void
    {
        $this->makeUser(false);

        $this->postJson('/api/login', [
            'email' => 'force@pdam.test',
            'password' => 'password',
        ])->assertStatus(200)
            ->assertJsonPath('data.must_change_password', false);
    }

    #[Test]
    public function request_api_lain_terblokir_403_saat_flag_aktif(): void
    {
        $user = $this->makeUser(true);

        $response = $this->withHeaders($this->bearer($user))
            ->getJson('/api/settings/kecamatan');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda wajib mengganti password terlebih dahulu.');
    }

    #[Test]
    public function me_dan_logout_tidak_pernah_terblokir(): void
    {
        $user = $this->makeUser(true);

        $this->withHeaders($this->bearer($user))
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.must_change_password', true);

        $this->withHeaders($this->bearer($user))
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    #[Test]
    public function ganti_password_sukses_melepas_blokir(): void
    {
        $user = $this->makeUser(true);
        $headers = $this->bearer($user);

        $this->withHeaders($headers)
            ->getJson('/api/settings/kecamatan')
            ->assertStatus(403);

        $this->withHeaders($headers)
            ->putJson('/api/me/password', [
                'current_password' => 'password',
                'new_password' => 'RahasiaBaru123',
                'new_password_confirmation' => 'RahasiaBaru123',
            ])
            ->assertStatus(200);

        $this->assertSame(false, $user->fresh()->must_change_password);

        $this->withHeaders($headers)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.must_change_password', false);

        $this->withHeaders($headers)
            ->getJson('/api/settings/kecamatan')
            ->assertStatus(200);
    }

    #[Test]
    public function request_tanpa_user_tetap_lolos(): void
    {
        $this->getJson('/api/health')->assertStatus(200);
    }
}
