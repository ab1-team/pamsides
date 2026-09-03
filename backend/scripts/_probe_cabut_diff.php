<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LEGACY installations ===\n";
foreach (['C','I','A','R','B'] as $s) {
    $c = DB::connection('legacy')->table('installations')->where('status', $s)->count();
    echo "  status='{$s}' : {$c}\n";
}
$totalLegacy = DB::connection('legacy')->table('installations')->count();
echo "  TOTAL legacy : {$totalLegacy}\n";

echo "\n=== NEW installation_tickets ===\n";
foreach (['terminated','completed','suspended','pending','draft','surveyed','unpaid','processing'] as $s) {
    $c = DB::table('installation_tickets')->where('status', $s)->count();
    echo "  status='{$s}' : {$c}\n";
}
$totalNew = DB::table('installation_tickets')->count();
echo "  TOTAL new    : {$totalNew}\n";

echo "\n=== Detail 9 terminated (cabut) di DB baru ===\n";
$rows = DB::table('installation_tickets')
    ->where('status', 'terminated')
    ->orderBy('id')
    ->get(['id','applicant_name','nik','village_id','created_at','updated_at']);
foreach ($rows as $r) {
    echo "  #{$r->id} | {$r->applicant_name} | NIK=" . ($r->nik ?? 'NULL') . " | village={$r->village_id} | created={$r->created_at}\n";
}
