<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$total = DB::table('installation_tickets')->count();
$null  = DB::table('installation_tickets')->whereNull('user_id')->count();
echo "Total installation_tickets : $total\n";
echo "user_id NULL              : $null\n";
echo "user_id NOT NULL          : ".($total - $null)."\n";

echo "\nRelasi check: tiket NULL tanpa customers row: ";
$ids = DB::table('installation_tickets')->whereNull('user_id')->pluck('id');
echo $ids->count()."\n";

echo "\nSample tiket yg baru di-link:\n";
$samples = DB::table('installation_tickets as t')
    ->join('users as u', 'u.id', '=', 't.user_id')
    ->whereIn('t.id', DB::table('installation_tickets')->whereNull('user_id')->pluck('id'))
    ->select('t.id','t.applicant_name','u.id as uid','u.email','u.name as uname')
    ->whereIn('t.id', [186, 199, 205])
    ->get();
foreach ($samples as $s) {
    printf("  ticket#%d '%s' -> user#%d '%s' (%s)\n", $s->id, $s->applicant_name, $s->uid, $s->uname, $s->email);
}
