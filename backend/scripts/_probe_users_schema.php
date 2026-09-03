<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
$cols = DB::select('DESCRIBE users');
foreach ($cols as $c) {
    echo $c->Field.' | '.$c->Type.' | null='.$c->Null.' | default='.($c->Default ?? '')."\n";
}
echo "\n--- existing users count by role ---\n";
$rows = DB::table('users')->select('role', DB::raw('COUNT(*) as c'))->groupBy('role')->get();
foreach ($rows as $r) echo $r->role.': '.$r->c."\n";
