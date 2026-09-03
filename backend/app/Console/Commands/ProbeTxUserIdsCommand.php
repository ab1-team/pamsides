<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProbeTxUserIdsCommand extends Command
{
    protected $signature = 'probe:tx-user-ids';
    protected $description = 'Probe distinct user_id in legacy transactions vs mapped IDs';

    public function handle(): int
    {
        $existingMax = DB::table('users')->max('id') ?? 0;
        $this->line('existingMax: ' . $existingMax);

        $rows = DB::connection('legacy')->select('SELECT DISTINCT user_id, COUNT(*) c FROM transactions GROUP BY user_id ORDER BY user_id');
        foreach ($rows as $r) {
            $uid = (int) $r->user_id;
            $lu = DB::connection('legacy')->table('users')->where('id', $uid)->first();
            $role = $lu ? match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'surveyor',
                7 => 'teknisi',
                default => 'admin',
            } : '?';
            $name = $lu ? (string) $lu->nama : '(no user)';
            $key = strtolower(trim($name)).'|'.strtolower($role);
            $newId = DB::table('users')->whereRaw('LOWER(TRIM(name)) = ? AND LOWER(role) = ?', [strtolower(trim($name)), strtolower($role)])->value('id');
            if (! $newId) {
                $newId = $existingMax + $uid;
                $flag = 'FALLBACK';
            } else {
                $flag = 'matched';
            }
            $exists = DB::table('users')->where('id', $newId)->exists() ? 'OK' : 'MISSING';
            $this->line(sprintf('legacy %3d (%s, %s) n=%5d → new %5d [%s] %s', $uid, $name, $role, $r->c, $newId, $flag, $exists));
        }
        return self::SUCCESS;
    }
}
