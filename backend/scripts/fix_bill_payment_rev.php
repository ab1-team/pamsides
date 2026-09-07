<?php
/**
 * Fix bill_payment.reverence_id: harus bill_payments.id, bukan monthly_bills.id.
 * Optimized: pakai JOIN langsung.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Fixing bill_payment.reverence_id...\n";

// Drop trigger create_amount_debit (butuh update transactions)
DB::statement('SET FOREIGN_KEY_CHECKS=0');

// 1) Update reverence_id ke bill_payments.id via JOIN
echo "Step 1: update reverence_id dari monthly_bills.id → bill_payments.id (akurat: cocok paid_at + amount)\n";
$start = microtime(true);

// Disable trigger temporarily untuk speed
foreach (['create_amount_debit','update_amount_debit','delete_amount_debit'] as $t) {
    DB::statement("DROP TRIGGER IF EXISTS `{$t}`");
}

// Step 1a: Exact match (bill_id + paid_at date + amount) — paling akurat
$updatedExact = DB::update("
    UPDATE transactions t
    JOIN bill_payments bp
      ON bp.bill_id = t.reverence_id
     AND DATE(bp.paid_at) = DATE(t.tgl_transaksi)
     AND ROUND(bp.amount_paid, 2) = ROUND(t.saldo, 2)
    SET t.reverence_id = bp.id
    WHERE t.reverence_type = 'bill_payment'
      AND t.reverence_id IS NOT NULL
      AND bp.paid_at IS NOT NULL
");
echo "  exact match (bill_id+date+amount): {$updatedExact}\n";

// Step 1b: Fallback — kalau masih ada yang rev_id = monthly_bills.id, JOIN bill_id saja (ambil bp.id paling awal)
$updatedLoose = DB::update("
    UPDATE transactions t
    JOIN (
        SELECT bp1.id, bp1.bill_id
        FROM bill_payments bp1
        JOIN (
            SELECT bill_id, MIN(id) AS min_id
            FROM bill_payments
            GROUP BY bill_id
        ) first ON first.bill_id = bp1.bill_id AND first.min_id = bp1.id
    ) bp ON bp.bill_id = t.reverence_id
    SET t.reverence_id = bp.id
    WHERE t.reverence_type = 'bill_payment'
      AND t.reverence_id IS NOT NULL
      AND t.reverence_id NOT IN (SELECT id FROM bill_payments)
");
echo "  loose match (bill_id only, 1 per bill): {$updatedLoose}\n";

$updated = $updatedExact + $updatedLoose;

// 2) Set NULL untuk yang tidak ada bill_payment
echo "Step 2: set NULL untuk bill yang tidak punya bill_payments\n";
$nulled = DB::update("
    UPDATE transactions t
    LEFT JOIN bill_payments bp ON bp.bill_id = t.reverence_id
    SET t.reverence_id = NULL
    WHERE t.reverence_type = 'bill_payment'
      AND t.reverence_id IS NOT NULL
      AND bp.id IS NULL
      AND t.reverence_id NOT IN (SELECT id FROM bill_payments)
");
echo "  nulled: {$nulled}\n";

// 3) Recreate triggers via migration
echo "Step 3: recreate triggers via migration\n";
$exit = 0;
exec('php artisan migrate --path=database/migrations/2026_06_23_034006_create_amount_table.php --force 2>&1', $out, $exit);
echo "  migrate exit: {$exit}\n";

DB::statement('SET FOREIGN_KEY_CHECKS=1');

$elapsed = round(microtime(true) - $start, 2);

// Verifikasi
echo "\nVerifikasi:\n";
$validBp = DB::table('transactions')->where('reverence_type','bill_payment')->whereIn('reverence_id', DB::table('bill_payments')->pluck('id'))->count();
echo "  bill_payment tx rev_id ada di bill_payments: {$validBp}\n";

$stillMbOnly = DB::table('transactions')
    ->where('reverence_type','bill_payment')
    ->whereNotNull('reverence_id')
    ->whereNotIn('reverence_id', DB::table('bill_payments')->pluck('id'))
    ->count();
echo "  bill_payment tx rev_id TIDAK ada di bill_payments: {$stillMbOnly}\n";

echo "Elapsed: {$elapsed}s\n";