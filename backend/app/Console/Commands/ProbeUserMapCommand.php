<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeUserMapCommand extends Command
{
    protected $signature = 'probe:user-map';
    protected $description = 'Trace userMap building';

    public function handle(): int
    {
        $existingMax = DB::table('users')->max('id') ?? 0;
        $this->line('existingMax: ' . $existingMax);
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);
        $newUsersAll = DB::table('users')->get(['id', 'name', 'role']);
        $byNameRole = [];
        foreach ($newUsersAll as $u) {
            $key = strtolower(trim((string) $u->name)).'|'.strtolower((string) $u->role);
            if (! isset($byNameRole[$key])) $byNameRole[$key][] = (int) $u->id;
        }
        foreach ($legacyUsers as $lu) {
            $role = match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'surveyor',
                7 => 'teknisi',
                default => 'admin',
            };
            $key = strtolower(trim((string) $lu->nama)).'|'.strtolower($role);
            if (isset($byNameRole[$key]) && ! empty($byNameRole[$key])) {
                $newId = (int) array_shift($byNameRole[$key]);
                $this->line("legacy {$lu->id} ({$lu->nama} | {$role}) → new {$newId} (matched by name+role)");
            } else {
                $newId = $existingMax + (int) $lu->id;
                $this->line("legacy {$lu->id} ({$lu->nama} | {$role}) → new {$newId} (NO MATCH, fallback)");
            }
        }
        return self::SUCCESS;
    }
}
