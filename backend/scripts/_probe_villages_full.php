<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Total villages ===\n";
$total = DB::table('villages')->count();
echo "Total: $total\n\n";

echo "=== Cek nama duplikat ===\n";
$dups = DB::table('villages')
    ->select('village_name', DB::raw('COUNT(*) as c'))
    ->groupBy('village_name')
    ->having('c','>',1)->get();
foreach ($dups as $d) printf("  '%s' : %d\n", $d->village_name, $d->c);
echo "Total nama duplikat: ".count($dups)."\n\n";

echo "=== Cek apakah ada kode existing di tabel manapun (prefix DS/desa)? ===\n";
$hasCode = DB::select("SHOW COLUMNS FROM villages LIKE 'code'");
echo "Kolom 'code' di villages: ".(count($hasCode) ? 'sudah ADA' : 'belum ada')."\n";

echo "\n=== Sample full data villages (semua) ===\n";
$all = DB::table('villages')->orderBy('id')->get();
foreach ($all as $v) {
    printf("  id=%-3d | '%s' / '%s' | addr=%s | phone=%s\n",
        $v->id, $v->village_name, $v->hamlet_name,
        substr($v->address ?? 'NULL', 0, 40), $v->phone ?? 'NULL');
}
