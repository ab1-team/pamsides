<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Village;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== Simulasi aktivasi 3x untuk village #67 ===\n\n";

$v = Village::find(67);

// Ambil ticket_id yg ada (random sample)
$ticketIds = DB::table('installation_tickets')->limit(3)->pluck('id');

for ($i = 1; $i <= 3; $i++) {
    $code = $v->generateNextCustomerCode();
    $tid = $ticketIds[$i-1];

    $email = "test_simulasi_{$i}_".uniqid()."@test.local";
    $userId = DB::table('users')->insertGetId([
        'name' => "Test Simulasi $i",
        'email' => $email,
        'password' => Hash::make('password'),
        'role' => 'pelanggan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $custId = DB::table('customers')->insertGetId([
        'ticket_id' => $tid,
        'user_id' => $userId,
        'customer_code' => $code,
        'initial_meter_reading' => 0,
        'meter_photo_url' => null,
        'activated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  Insert #{$i}: customer_code={$code}, cust_id={$custId}, ticket_id={$tid}\n";
}

echo "\nSetelah insert, generate lagi (harus naik ke .4):\n";
echo "  Hasil: ".$v->generateNextCustomerCode()."\n";

// Cek existing customers dengan prefix
echo "\n=== Customers dgn prefix 005.0001.100. ===\n";
$cs = DB::table('customers')->where('customer_code','like','005.0001.100.%')->get(['customer_code','id']);
foreach ($cs as $c) echo "  cust#{$c->id} = {$c->customer_code}\n";

// Cleanup
$deletedCust = DB::table('customers')->where('customer_code','like','005.0001.100.%')->delete();
$deletedUsers = DB::table('users')->where('email','like','test_simulasi_%')->delete();
echo "\nCleanup: {$deletedCust} customers, {$deletedUsers} users dihapus.\n";
