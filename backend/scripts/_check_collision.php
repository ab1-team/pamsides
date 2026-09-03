<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Cek apakah generate berikutnya untuk village 68 akan bentrok dgn existing
$existing = DB::table('customers')
    ->where('customer_code', 'like', '005.0001.0002-%')
    ->pluck('customer_code')
    ->all();
echo "Existing customers dgn prefix '005.0001.0002-': ".count($existing)."\n";
foreach (array_slice($existing, 0, 10) as $c) echo "  $c\n";

echo "\nSampel kode legacy '1.01.xxxx' (existing), apakah ada yg bentrok?\n";
$bentrok = DB::table('customers')
    ->whereIn('customer_code', ['005.0001.0002-001','005.0001.0001-001','005.0002.0004-001'])
    ->get();
echo "Bentrok: ".$bentrok->count()."\n";
