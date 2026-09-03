<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Cek: apakah legacy.installations menyimpan kode desa? ===\n";
$cols = DB::connection('legacy')->select('DESCRIBE installations');
foreach ($cols as $c) {
    echo "  $c->Field | $c->Type\n";
}

echo "\n=== Cek: legacy.villages/desa? ===\n";
$tables = DB::connection('legacy')->select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array)$t)[0];
    if (preg_match('/desa|village|kecamatan|setting/i', $name)) {
        echo "  $name\n";
    }
}

echo "\n=== Cek: legacy.kode_instalasi vs legacy.desa ===\n";
$sample = DB::connection('legacy')->table('installations')
    ->select('id','kode_instalasi','desa','customer_id')
    ->limit(15)->get();
foreach ($sample as $r) {
    printf("  inst#%d kode=%s desa_id=%d customer=%d\n",
        $r->id, $r->kode_instalasi, $r->desa, $r->customer_id);
}

echo "\n=== Cek: new.villages ada setting_id? (kecamatan) ===\n";
$cs = DB::table('settings')->count();
echo "settings count: $cs\n";
$ss = DB::table('settings')->limit(5)->get();
foreach ($ss as $s) printf("  setting#%d %s\n", $s->id, $s->name ?? $s->setting_name ?? '-');

echo "\n=== Apakah ada ticket baru yang village_id NULL? ===\n";
$vn = DB::table('installation_tickets')->whereNull('village_id')->count();
echo "Tiket tanpa village_id: $vn\n";

echo "\n=== Distribusi tiket per village (top 5) ===\n";
$rows = DB::table('installation_tickets as t')
    ->select('t.village_id', 'v.village_name', 'v.hamlet_name', DB::raw('COUNT(*) as c'))
    ->leftJoin('villages as v', 'v.id', '=', 't.village_id')
    ->groupBy('t.village_id','v.village_name','v.hamlet_name')
    ->orderByDesc('c')
    ->limit(5)->get();
foreach ($rows as $r) {
    printf("  village_id=%-3d %s/%s : %d tiket\n",
        $r->village_id ?? 0, $r->village_name ?? 'NULL', $r->hamlet_name ?? '-', $r->c);
}
