<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Reset password akun (B4): set password acak kuat + wajib ganti saat login
 * pertama. Aman dijalankan berulang; user yang tidak ada hanya dilaporkan.
 *
 * Contoh:
 *   php artisan users:reset-password 12 budi@legacy.local
 *   php artisan users:reset-password --staff --print
 */
class UsersResetPasswordCommand extends Command
{
    protected $signature = 'users:reset-password
                            {users?* : Daftar id atau email user}
                            {--staff : Reset semua staff (role admin/teknisi)}
                            {--print : Cetak kredensial baru sekali ke konsol}';

    protected $description = 'Reset password akun ke password acak dan wajibkan ganti saat login pertama';

    private const STAFF_ROLES = ['admin', 'teknisi'];

    public function handle(): int
    {
        $print = (bool) $this->option('print');
        $targets = collect($this->argument('users') ?? []);

        if ($targets->isEmpty() && ! $this->option('staff')) {
            $this->error('Tentukan daftar id|email atau pakai opsi --staff.');

            return self::FAILURE;
        }

        $found = collect();
        if ($this->option('staff')) {
            $found = User::whereIn('role', self::STAFF_ROLES)->orderBy('id')->get();
        }

        $missing = [];
        foreach ($targets->unique()->values() as $target) {
            $target = trim((string) $target);
            if ($target === '') {
                continue;
            }
            $user = ctype_digit($target)
                ? User::find((int) $target)
                : User::where('email', $target)->first();

            if (! $user) {
                $missing[] = $target;

                continue;
            }
            $found->push($user);
        }

        $found = $found->unique('id')->values();

        $credentials = [];
        foreach ($found as $user) {
            $plain = Str::password(16);
            $user->forceFill([
                'password' => Hash::make($plain),
                'must_change_password' => true,
            ])->save();
            $credentials[] = ['id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'password' => $plain];
        }

        $this->info(sprintf('Password disetel ulang: %d akun (wajib ganti saat login pertama).', count($credentials)));

        if (! empty($missing)) {
            $this->warn(sprintf('Tidak ditemukan (%d): %s', count($missing), implode(', ', $missing)));
        }

        if ($print && ! empty($credentials)) {
            $this->warn('Kredensial baru (hanya ditampilkan sekali):');
            $this->table(
                ['ID', 'Email', 'Role', 'Password'],
                array_map(fn ($c) => [$c['id'], $c['email'], $c['role'], $c['password']], $credentials)
            );
        }

        return ! empty($missing) && empty($credentials) ? self::FAILURE : self::SUCCESS;
    }
}
