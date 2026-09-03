<?php
/**
 * Set reverence_type='payment' untuk baris NULL dengan account_debet=1.1.01.01
 * (Kas) dan account_kredit=4.1.01.01 (Pasang Baru). Ini adalah jurnal
 * pembayaran instalasi yang dibuat live (id_user=89 Ahmad George Sukabumi
 * atau import legacy) sebelum observer/lookup di command import mengisi
 * klasifikasinya.
 *
 * Setelah ini, jalankan fix_payment_rev.php untuk isi reverence_id
 * (payments.id type=installation_fee).
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Set reverence_type='payment' untuk baris NULL (Kas → 4.1.01.01)\n";
echo "Started: ".date('c')."\n\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');

// Drop triggers
foreach (['create_amount_debit','update_amount_debit','delete_amount_debit'] as $t) {
    DB::statement("DROP TRIGGER IF EXISTS `{$t}`");
}

$before = DB::table('transactions')
    ->where('account_debet', '1.1.01.01')
    ->where('account_kredit', '4.1.01.01')
    ->whereNull('reverence_type')
    ->count();
echo "Baris NULL (Kas → 4.1.01.01) sebelum: {$before}\n";

// Klasifikasi pasang baru = account_debet=1.1.01.01 (Kas) + account_kredit=4.1.01.01 (Pasang Baru)
$updated = DB::table('transactions')
    ->where('account_debet', '1.1.01.01')
    ->where('account_kredit', '4.1.01.01')
    ->whereNull('reverence_type')
    ->update(['reverence_type' => 'payment']);

echo "Updated reverence_type → 'payment': {$updated}\n";

DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Re-create triggers
echo "\nRecreate triggers...\n";
exec('php artisan migrate --path=database/migrations/2026_06_23_034006_create_amount_table.php --force 2>&1', $out, $exit);
echo "migrate exit: {$exit}\n";

// Verifikasi
echo "\nVerifikasi:\n";
$stillNull = DB::table('transactions')
    ->where('account_debet', '1.1.01.01')
    ->where('account_kredit', '4.1.01.01')
    ->whereNull('reverence_type')
    ->count();
echo "  Sisa NULL (Kas → 4.1.01.01): {$stillNull}\n";

$paymentCount = DB::table('transactions')
    ->where('account_debet', '1.1.01.01')
    ->where('account_kredit', '4.1.01.01')
    ->where('reverence_type', 'payment')
    ->count();
$paymentWithId = DB::table('transactions')
    ->where('account_debet', '1.1.01.01')
    ->where('account_kredit', '4.1.01.01')
    ->where('reverence_type', 'payment')
    ->whereNotNull('reverence_id')
    ->count();
echo "  Total reverence_type='payment' (Kas → 4.1.01.01): {$paymentCount}\n";
echo "  Diantaranya punya reverence_id: {$paymentWithId}\n";

echo "\nFinished: ".date('c')."\n";
