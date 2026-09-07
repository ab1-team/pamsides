<?php
/**
 * Fix payment.reverence_id: harus payments.id, bukan customers.id.
 *
 * Kasus data:
 *   - reverence_type = 'payment' + reverence_id = customers.id (SALAH, dari versi lama)
 *   - reverence_type = 'payment' + reverence_id IS NULL (lookup belum jalan, dari baris live / import)
 *
 * Yang benar:
 *   reverence_id harus payments.id (type='installation_fee') untuk ticket terkait.
 *
 * Lookup: customers.ticket_id → payments.ticket_id (type='installation_fee').
 * Kalau lebih dari satu payment untuk ticket yang sama, pilih yang paid_at
 * paling dekat dengan transactions.tgl_transaksi.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Fixing payment.reverence_id → payments.id\n";
echo "Started: ".date('c')."\n\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');

// Drop triggers supaya update tidak recompute amount
foreach (['create_amount_debit','update_amount_debit','delete_amount_debit'] as $t) {
    DB::statement("DROP TRIGGER IF EXISTS `{$t}`");
}

// 1) Deteksi baris payment yang perlu lookup:
//   - (A) reverence_type='payment' + reverence_id IS NOT NULL tapi rev_id bukan payments.id
//         (kasus lama: rev_id = customers.id, join dengan customers)
//   - (B) reverence_type='payment' + reverence_id IS NULL → lookup pakai relasi/nama
$rows = DB::table('transactions as t')
    ->where('t.reverence_type', 'payment')
    ->where(function ($q) {
        $q->whereNull('t.reverence_id')
          ->orWhereNotIn('t.reverence_id', DB::table('payments')->select('id'));
    })
    ->orderBy('t.id')
    ->get(['t.id as trx_id','t.tgl_transaksi','t.saldo','t.relasi','t.reverence_id']);

echo "Baris payment yang perlu lookup: ".count($rows)."\n";

$updated = 0;
$nulled  = 0;
$alreadyCorrect = 0;
$samples = [];

// Index payments by ticket_id untuk lookup cepat
$paymentsByTicket = [];
foreach (DB::table('payments')->where('type', 'installation_fee')->orderBy('id')->get(['id','ticket_id','amount','paid_at']) as $p) {
    $paymentsByTicket[(int) $p->ticket_id][] = $p;
}

foreach ($rows as $r) {
    $trxId   = (int) $r->trx_id;
    $trxDay  = substr((string) $r->tgl_transaksi, 0, 10);
    $trxAmt  = number_format((float) $r->saldo, 2, '.', '');
    $relasi  = trim((string) ($r->relasi ?? ''));

    // Cari ticket_id:
    //   - kalau rev_id IS NOT NULL, dulu rev_id = customers.id → customers.ticket_id
    //   - kalau rev_id IS NULL, pakai relasi nama → installation_tickets.applicant_name
    $ticketId = null;
    if ($r->reverence_id !== null) {
        $ticketId = DB::table('customers')->where('id', (int) $r->reverence_id)->value('ticket_id');
    }
    if (! $ticketId && $relasi !== '') {
        $ticketId = DB::table('installation_tickets')
            ->whereRaw('LOWER(TRIM(applicant_name)) = ?', [strtolower($relasi)])
            ->value('id');
    }

    if (! $ticketId) {
        DB::table('transactions')->where('id', $trxId)->update(['reverence_id' => null]);
        $nulled++;
        if (count($samples) < 30) $samples[] = "trx={$trxId} ticket=NULL → NULL (no ticket match)";
        continue;
    }

    $candidates = $paymentsByTicket[(int) $ticketId] ?? [];
    if (empty($candidates)) {
        DB::table('transactions')->where('id', $trxId)->update(['reverence_id' => null]);
        $nulled++;
        if (count($samples) < 30) $samples[] = "trx={$trxId} ticket={$ticketId} → NULL (no payment)";
        continue;
    }

    $picked = null;
    $bestDiff = PHP_INT_MAX;
    $bestExactDiff = PHP_INT_MAX;
    foreach ($candidates as $p) {
        $payDay = $p->paid_at ? substr((string) $p->paid_at, 0, 10) : null;
        $payAmt = number_format((float) $p->amount, 2, '.', '');
        // Exact match (paid_at + amount)
        if ($payDay === $trxDay && $payAmt === $trxAmt) {
            $picked = (int) $p->id;
            break;
        }
    }

    // Fallback: paid_at paling dekat (≤ 90 hari) jika tidak exact
    if (! $picked) {
        $targetTs = strtotime($trxDay);
        foreach ($candidates as $p) {
            if (! $p->paid_at) continue;
            $ts = strtotime(substr((string) $p->paid_at, 0, 10));
            if ($ts === false || $targetTs === false) continue;
            $diff = abs($ts - $targetTs);
            if ($diff < $bestDiff) { $bestDiff = $diff; $picked = (int) $p->id; }
        }
        if ($picked && $bestDiff > 86400 * 90) {
            $picked = null;
        }
    }

    if (! $picked) {
        DB::table('transactions')->where('id', $trxId)->update(['reverence_id' => null]);
        $nulled++;
        if (count($samples) < 30) $samples[] = "trx={$trxId} ticket={$ticketId} → NULL (no date match)";
        continue;
    }

    DB::table('transactions')->where('id', $trxId)->update(['reverence_id' => $picked]);
    $updated++;
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Re-create triggers via migration
echo "\nRecreate triggers via migration...\n";
$exit = 0;
exec('php artisan migrate --path=database/migrations/2026_06_23_034006_create_amount_table.php --force 2>&1', $out, $exit);
echo "migrate exit: {$exit}\n";

// Verifikasi
echo "\nVerifikasi:\n";
$ok = DB::table('transactions')
    ->where('reverence_type', 'payment')
    ->whereNotNull('reverence_id')
    ->whereIn('reverence_id', DB::table('payments')->pluck('id'))
    ->count();
echo "  payment tx rev_id ada di payments: {$ok}\n";

$stillCust = DB::table('transactions as t')
    ->join('customers as c', 'c.id', '=', 't.reverence_id')
    ->where('t.reverence_type', 'payment')
    ->whereNotNull('t.reverence_id')
    ->count();
echo "  payment tx rev_id masih = customers.id: {$stillCust}\n";

$stillNull = DB::table('transactions')
    ->where('reverence_type', 'payment')
    ->whereNull('reverence_id')
    ->count();
echo "  payment tx rev_id NULL (no match): {$stillNull}\n";

echo "\nStats: updated={$updated} nulled={$nulled}\n";
echo "Samples:\n";
foreach (array_slice($samples, 0, 20) as $s) echo "  {$s}\n";
echo "\nFinished: ".date('c')."\n";
