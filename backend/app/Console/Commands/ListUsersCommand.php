<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsersCommand extends Command
{
    protected $signature = 'list:users';
    protected $description = 'Tampilkan semua user di DB baru';

    public function handle(): int
    {
        $users = User::orderBy('id')->get();
        $this->info('Total: '.$users->count().' user');
        $this->table(
            ['ID', 'Name', 'Email', 'Role'],
            $users->map(fn($u) => [$u->id, $u->name, $u->email, $u->role])->toArray()
        );
        return self::SUCCESS;
    }
}