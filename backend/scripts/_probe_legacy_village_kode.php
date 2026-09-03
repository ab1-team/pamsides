<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Legacy villages: ALL data (semua baris) ===\n";
$all = DB::connection('legacy')->table('villages')->orderBy('id')->get();
foreach ($all as $r) {
    printf("  legacy_id=%-3d kode=%-25s | %s / %s | kat=%d\n",
        $r->id, $r->kode, $r->nama, $r->dusun ?? '-', $r->kategori);
}

echo "\n=== Mapping legacy.villages.id -> new.villages.id (by nama+dusun) ===\n";
$legacy = DB::connection('legacy')->table('villages')->orderBy('id')->get();
$new    = DB::table('villages')->orderBy('id')->get();

$map = [];
foreach ($legacy as $lg) {
    $matched = null;
    foreach ($new as $nw) {
        if (strtolower(trim($nw->village_name)) === strtolower(trim($lg->nama))
            && strtolower(trim($nw->hamlet_name ?? '')) === strtolower(trim($lg->dusun ?? ''))) {
            $matched = $nw;
            break;
        }
    }
    $map[$lg->id] = $matched?->id ?? null;
    printf("  legacy#%d (kode=%s, %s/%s) -> new#%s\n",
        $lg->id, $lg->kode, $lg->nama, $lg->dusun ?? '-', $map[$lg->id] ?? 'NULL');
}
