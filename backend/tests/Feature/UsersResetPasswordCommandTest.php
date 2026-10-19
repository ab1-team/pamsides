<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersResetPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, string $role): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('lama'),
            'role' => $role,
        ]);
    }

    #[Test]
    public function reset_by_id_dan_email_menandai_wajib_ganti(): void
    {
        $a = $this->makeUser('a@legacy.local', 'teknisi');
        $b = $this->makeUser('b@legacy.local', 'admin');

        $this->artisan('users:reset-password', ['users' => [(string) $a->id, 'b@legacy.local']])
            ->expectsOutputToContain('Password disetel ulang: 2 akun')
            ->assertSuccessful();

        foreach ([$a, $b] as $user) {
            $fresh = $user->fresh();
            $this->assertSame(true, $fresh->must_change_password);
            $this->assertFalse(Hash::check('lama', $fresh->password));
        }
    }

    #[Test]
    public function user_tidak_ditemukan_dilaporkan_bukan_error_fatal(): void
    {
        $this->artisan('users:reset-password', ['users' => ['9999', 'tidak@ada.local']])
            ->expectsOutputToContain('Password disetel ulang: 0 akun')
            ->expectsOutputToContain('Tidak ditemukan (2)')
            ->assertFailed();
    }

    #[Test]
    public function opsi_staff_hanya_mereset_admin_dan_teknisi(): void
    {
        $admin = $this->makeUser('admin@legacy.local', 'admin');
        $tek = $this->makeUser('tek@legacy.local', 'teknisi');
        $pel = $this->makeUser('pel@legacy.local', 'pelanggan');

        $this->artisan('users:reset-password', ['--staff' => true])
            ->expectsOutputToContain('Password disetel ulang: 2 akun')
            ->assertSuccessful();

        $this->assertSame(true, $admin->fresh()->must_change_password);
        $this->assertSame(true, $tek->fresh()->must_change_password);
        $this->assertSame(false, $pel->fresh()->must_change_password);
    }
}
