<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Village;
use Illuminate\Support\Facades\DB;

echo "=== Test real generate (counter increment per prefix) ===\n\n";

// Cek existing customers dgn prefix 005.0001.100.
$prefix = '005.0001.100.';
$existing = DB::table('customers')->where('customer_code','like',$prefix.'%')->pluck('customer_code')->all();
echo "Existing customers prefix '{$prefix}': ".count($existing)."\n";
foreach ($existing as $c) echo "  $c\n";

echo "\nGenerate baru (Village #67, code 005.0001.0001):\n";
$v = Village::find(67);
echo "  Hasil: ".$v->generateNextCustomerCode()."\n";

echo "\nGenerate ke-2 (harus naik):\n";
echo "  Hasil: ".$v->generateNextCustomerCode()."\n";

// Cek juga format baru untuk village dengan segment hanya 2 (999.001)
echo "\nGenerate Village #76 (code 999.001):\n";
$v76 = Village::find(76);
echo "  Hasil: ".$v76->generateNextCustomerCode()."\n";

// Test syntax check
echo "\n=== PHP syntax check ===\n";
echo "Village.php: ";
$out = shell_exec('"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" -l "C:\laragon\www\pamsides\backend\app\Models\Village.php"');
echo trim($out)."\n";
