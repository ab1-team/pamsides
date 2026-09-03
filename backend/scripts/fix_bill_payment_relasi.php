<?php
/**
 * Fix bill_payment.reverence_id untuk baris yang masih NULL,
 * dengan lookup via relasi (nama pelanggan) + customer_id.
 *
 * Pola data: trx account_debet=1.1.01.01 (kas) → account_kredit=1.1.03.01 (piutang)
 *            dengan keterangan "Bayar/Pelunasan..." dan relasi = nama pelanggan.
 *
 * Lookup:
 *   customers.applicant_name via installation_tickets = trx.relasi
 *   → monthly_bills.customer_id
 *   → bill_payments.bill_id (with paid_at = trx.tgl_transaksi, amount match jika possible)
 *
 * Filter: hanya NULL + reverence_type='bill_payment' + relasi tidak kosong.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Fixing NULL bill_payment.reverence_id via relasi lookup\n";
echo "Started: ".date('c')."\n\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');

// Drop triggers
foreach (['create_amount_debit','update_amount_debit','delete_amount_debit'] as $t) {
    DB::statement("DROP TRIGGER IF EXISTS `{$t}`");
}

// Cache: customer name (lowercase trimmed) → list of (customer_id, ticket_id)
$custCache = [];
foreach (DB::table('customers as c')
    ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
    ->select('c.id as customer_id', 't.applicant_name')
    ->get() as $cr) {
    $key = strtolower(trim((string) $cr->applicant_name));
    if (! isset($custCache[$key])) $custCache[$key] = [];
    $custCache[$key][] = (int) $cr->customer_id;
}

// Cache: customer_id → list of bp (id, paid_at_date, amount_paid, bill_id)
$bpCache = [];
foreach (DB::table('bill_payments as bp')
    ->join('monthly_bills as mb', 'mb.id', '=', 'bp.bill_id')
    ->select('bp.id as bp_id', 'bp.amount_paid', 'bp.paid_at', 'mb.customer_id', 'mb.id as mb_id')
    ->get() as $bpr) {
    $bpCache[(int) $bpr->customer_id][] = [
        'bp_id'   => (int) $bpr->bp_id,
        'mb_id'   => (int) $bpr->mb_id,
        'paid_at' => $bpr->paid_at,
        'amount'  => (float) $bpr->amount_paid,
    ];
}

echo "Customer cache: ".count($custCache)."\n";
echo "Bill-payment cache entries: ".array_sum(array_map('count', $bpCache))."\n\n";

// Loop NULL bill_payment
$rows = DB::table('transactions')
    ->where('reverence_type', 'bill_payment')
    ->whereNull('reverence_id')
    ->whereNotNull('relasi')
    ->where('relasi', '!=', '')
    ->orderBy('id')
    ->get();

$updated = 0; $nulled = 0; $skipped = 0;
$samples = [];

foreach ($rows as $r) {
    $relKey = strtolower(trim((string) $r->relasi));
    $customers = $custCache[$relKey] ?? [];
    if (empty($customers)) {
        $nulled++; // no customer match — keep NULL
        if (count($samples) < 10) $samples[] = "trx={$r->id} relasi='{$r->relasi}' → no customer";
        continue;
    }

    $trxDay = substr((string) $r->tgl_transaksi, 0, 10);
    $trxAmt = round((float) $r->saldo, 2);

    $bestBp = null;
    $bestScore = -1;

    foreach ($customers as $custId) {
        if (! isset($bpCache[$custId])) continue;
        foreach ($bpCache[$custId] as $cand) {
            $candDay = substr((string) $cand['paid_at'], 0, 10);
            if ($candDay !== $trxDay) continue;

            // Skor: 2 kalau amount match persis, 1 kalau hanya date match
            $score = 0;
            if (round($cand['amount'], 2) === $trxAmt) $score = 2;
            elseif ($cand['amount'] >= $trxAmt) $score = 1; // bp amount >= trx amount (trx = komponen)

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestBp = $cand['bp_id'];
            }
        }
    }

    if ($bestBp === null) {
        $nulled++;
        if (count($samples) < 10) $samples[] = "trx={$r->id} tgl={$trxDay} relasi='{$r->relasi}' saldo={$trxAmt} → no bp match";
        continue;
    }

    DB::table('transactions')->where('id', $r->id)->update(['reverence_id' => $bestBp]);
    $updated++;
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Recreate triggers
echo "Recreate triggers via migration...\n";
$exit = 0;
exec('php artisan migrate --path=database/migrations/2026_06_23_034006_create_amount_table.php --force 2>&1', $out, $exit);
echo "migrate exit: {$exit}\n\n";

// Verifikasi
$stillNull = DB::table('transactions')
    ->where('reverence_type', 'bill_payment')
    ->whereNull('reverence_id')
    ->count();
echo "Updated: {$updated}\n";
echo "Nulled (kept): {$nulled}\n";
echo "Still NULL bill_payment tx: {$stillNull}\n";

$validBp = DB::table('transactions')
    ->where('reverence_type', 'bill_payment')
    ->whereNotNull('reverence_id')
    ->whereIn('reverence_id', DB::table('bill_payments')->pluck('id'))
    ->count();
echo "Valid bp reverence_id: {$validBp}\n";

echo "\nSamples:\n";
foreach ($samples as $s) echo "  $s\n";
echo "\nFinished: ".date('c')."\n";