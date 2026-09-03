<?php
/**
 * Fix monthly_bill.reverence_id & overdue_bill.reverence_id untuk baris yang masih NULL.
 *
 * monthly_bill: tagihan bulan tgl_transaksi (atau bulan di parse dari keterangan).
 * overdue_bill: "Utang [Abodemen|Pemakaian|Denda] [BULAN TAHUN] [Nama]"
 *               Parse bulan-tahun dari keterangan_transaksi, lookup monthly_bill by customer+period.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Fixing NULL monthly_bill & overdue_bill.reverence_id via relasi lookup\n";
echo "Started: ".date('c')."\n\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');

foreach (['create_amount_debit','update_amount_debit','delete_amount_debit'] as $t) {
    DB::statement("DROP TRIGGER IF EXISTS `{$t}`");
}

// Cache
$custCache = [];
foreach (DB::table('customers as c')
    ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
    ->select('c.id as customer_id', 't.applicant_name')
    ->get() as $cr) {
    $key = strtolower(trim((string) $cr->applicant_name));
    if (! isset($custCache[$key])) $custCache[$key] = [];
    $custCache[$key][] = (int) $cr->customer_id;
}

$mbCache = [];
foreach (DB::table('monthly_bills')
    ->select('id', 'customer_id', 'billing_period_year', 'billing_period_month')
    ->get() as $mb) {
    $mbCache[(int) $mb->customer_id][sprintf('%04d-%02d', $mb->billing_period_year, $mb->billing_period_month)] = (int) $mb->id;
}

echo "Customer cache: ".count($custCache)."\n";
echo "MB cache customers: ".count($mbCache)."\n\n";

// Map nama bulan indo ke nomor
$bulanMap = [
    'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
    'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
    'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
];

function parseBulanTahun(string $ket, array $bulanMap): ?array {
    // cari pola "[Bulan] [Tahun]" dalam keterangan
    if (preg_match('/\b(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})\b/i', $ket, $m)) {
        $bulan = $bulanMap[strtolower($m[1])] ?? null;
        $tahun = (int) $m[2];
        if ($bulan && $tahun > 2000) return ['month' => $bulan, 'year' => $tahun];
    }
    return null;
}

function lookupMb(array $custCache, array $mbCache, string $relKey, int $year, int $month): ?int {
    $cands = $custCache[$relKey] ?? [];
    foreach ($cands as $custId) {
        $key = sprintf('%04d-%02d', $year, $month);
        if (isset($mbCache[$custId][$key])) {
            return $mbCache[$custId][$key];
        }
    }
    return null;
}

$rows = DB::table('transactions')
    ->whereIn('reverence_type', ['monthly_bill', 'overdue_bill'])
    ->whereNull('reverence_id')
    ->whereNotNull('relasi')
    ->where('relasi', '!=', '')
    ->orderBy('id')
    ->get();

$updatedMb = 0; $updatedOb = 0; $nulled = 0;
$samples = [];

foreach ($rows as $r) {
    $relKey = strtolower(trim((string) $r->relasi));
    $trxTs = strtotime((string) $r->tgl_transaksi);
    if ($trxTs === false) { $nulled++; continue; }

    // Parse bulan-tahun dari keterangan kalau ada
    $parsed = parseBulanTahun((string) $r->keterangan_transaksi, $bulanMap);
    if ($parsed) {
        [$year, $month] = [$parsed['year'], $parsed['month']];
    } else {
        $year = (int) date('Y', $trxTs);
        $month = (int) date('n', $trxTs);
    }

    // Untuk monthly_bill: coba bulan parsed, fallback bulan trx
    // Untuk overdue_bill: bulan parsed adalah bulan tagihan tunggakan
    $mbId = lookupMb($custCache, $mbCache, $relKey, $year, $month);
    if (! $mbId && $r->reverence_type === 'monthly_bill') {
        // fallback ke bulan transaksi
        $mbId = lookupMb($custCache, $mbCache, $relKey, (int) date('Y', $trxTs), (int) date('n', $trxTs));
    }

    if ($mbId) {
        DB::table('transactions')->where('id', $r->id)->update(['reverence_id' => $mbId]);
        if ($r->reverence_type === 'monthly_bill') $updatedMb++;
        else $updatedOb++;
    } else {
        $nulled++;
        if (count($samples) < 10) $samples[] = "trx={$r->id} type={$r->reverence_type} tgl={$r->tgl_transaksi} relasi='{$r->relasi}' parsed={$year}-{$month} → no mb";
    }
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Recreate triggers
echo "Recreate triggers...\n";
$exit = 0;
exec('php artisan migrate --path=database/migrations/2026_06_23_034006_create_amount_table.php --force 2>&1', $out, $exit);
echo "migrate exit: {$exit}\n\n";

// Verifikasi
$stillNullMb = DB::table('transactions')->where('reverence_type','monthly_bill')->whereNull('reverence_id')->count();
$stillNullOb = DB::table('transactions')->where('reverence_type','overdue_bill')->whereNull('reverence_id')->count();

echo "Updated monthly_bill: $updatedMb\n";
echo "Updated overdue_bill: $updatedOb\n";
echo "Kept NULL: $nulled\n";
echo "Still NULL monthly_bill: $stillNullMb\n";
echo "Still NULL overdue_bill: $stillNullOb\n";

echo "\nSamples (NULL remaining):\n";
foreach ($samples as $s) echo "  $s\n";
echo "\nFinished: ".date('c')."\n";