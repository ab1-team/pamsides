<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tabel settings (kecamatan di sistem baru) ===\n";
$cols = DB::select('DESCRIBE settings');
foreach ($cols as $c) printf("  %s | %s | null=%s\n", $c->Field, $c->Type, $c->Null);
echo "\nData:\n";
$ss = DB::table('settings')->get();
foreach ($ss as $s) {
    print_r(json_decode(json_encode($s), true));
}

echo "\n=== Apakah legacy punya tabel kecamatan/desa? ===\n";
$tables = DB::connection('legacy')->select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array)$t)[0];
    if (preg_match('/village|desa|kecam|setting/i', $name)) echo "  $name\n";
}

echo "\n=== legacy.villages schema ===\n";
$tableNames = array_map(fn($t) => array_values((array)$t)[0], $tables);
if (in_array('villages', $tableNames)) {
    $cols = DB::connection('legacy')->select('DESCRIBE villages');
    foreach ($cols as $c) printf("  %s | %s | null=%s\n", $c->Field, $c->Type, $c->Null);

    echo "\nSample data:\n";
    $sample = DB::connection('legacy')->table('villages')->limit(10)->get();
    foreach ($sample as $r) print_r(json_decode(json_encode($r), true));
}

echo "\n=== Legacy: distribusi installations per desa_id ===\n";
$rows = DB::connection('legacy')->table('installations')
    ->select('desa', DB::raw('COUNT(*) as c'))
    ->groupBy('desa')
    ->orderBy('desa')
    ->get();
foreach ($rows as $r) printf("  desa_id=%d : %d instalasi\n", $r->desa, $r->c);

echo "\n=== new.villages: distribusi per setting_id ===\n";
$rows = DB::table('villages')
    ->select('setting_id', DB::raw('COUNT(*) as c'))
    ->groupBy('setting_id')
    ->get();
foreach ($rows as $r) printf("  setting_id=%s : %d villages\n", $r->setting_id ?? 'NULL', $r->c);
