<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeUserIdsCommand extends Command
{
    protected $signature = 'probe:user-ids';
    protected $description = 'Probe user id range and sample admins';

    public function handle(): int
    {
        $row = DB::selectOne('SELECT COUNT(*) c, MIN(id) mi, MAX(id) m FROM users');
        $this->line('count=' . $row->c . ' min=' . $row->mi . ' max=' . $row->m);
        $samples = DB::select("SELECT id, name, role FROM users WHERE role = 'admin' ORDER BY id LIMIT 10");
        foreach ($samples as $u) {
            $this->line($u->id . ' | ' . $u->role . ' | ' . $u->name);
        }
        return self::SUCCESS;
    }
}
