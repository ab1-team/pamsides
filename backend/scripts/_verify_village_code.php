<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Hasil backfill code villages ===\n";
$v = DB::table('villages')->orderBy('id')->get();
foreach ($v as $r) {
    printf("  id=%-3d | code=%-22s | %s / %s\n",
        $r->id, $r->code ?? 'NULL', $r->village_name, $r->hamlet_name);
}

echo "\n=== Test auto-generate desa baru (999.xxxxx) ===\n";
$test = \App\Models\Village::create([
    'village_name' => 'TEST_AUTO_CODE',
    'hamlet_name' => 'TEST',
]);
echo "  Created: id=$test->id code=$test->code\n";
$test->delete();

echo "\n=== Test kirim code manual ===\n";
$test2 = \App\Models\Village::create([
    'code' => '010.0005.0001',
    'village_name' => 'TEST_MANUAL_CODE',
    'hamlet_name' => 'MANUAL',
]);
echo "  Created: id=$test2->id code=$test2->code\n";
$test2->delete();
