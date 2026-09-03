<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeFallbackAdminCommand extends Command
{
    protected $signature = 'probe:fallback-admin';
    protected $description = 'Verify fallback admin id';

    public function handle(): int
    {
        $id = DB::table('users')->where('role', 'admin')->value('id');
        $this->line('first admin id: ' . $id);
        $this->line('exists: ' . (DB::table('users')->where('id', $id)->exists() ? 'yes' : 'no'));
        $this->line('user id 1 exists: ' . (DB::table('users')->where('id', 1)->exists() ? 'yes' : 'no'));
        return self::SUCCESS;
    }
}
