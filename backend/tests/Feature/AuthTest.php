<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'admin'): User
    {
        return User::create([
            'name'     => 'Test User',
            'email'    => 'test@pdam.test',
            'password' => Hash::make('password'),
            'role'     => $role,
        ]);
    }

    #[Test]
    public function user_dapat_login(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/login', [
            'email'    => 'test@pdam.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['user', 'token'],
                 ]);
    }

    #[Test]
    public function login_gagal_dengan_kredensial_salah(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/login', [
            'email'    => 'test@pdam.test',
            'password' => 'salah123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function user_dapat_logout(): void
    {
        $user  = $this->createUser();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    #[Test]
    public function akses_endpoint_tanpa_token_ditolak(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
