<?php
/**
 * Generate amount rows untuk semua akun, semua bulan, semua tahun.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "Truncate amount...\n";
DB::table('amount')->truncate();

$accounts = DB::table('accounts')->get();
echo "Accounts: " . count($accounts) . "\n";

// Range tahun yang dipakai di transactions
$minTahun = (int) DB::table('transactions')->min(DB::raw('YEAR(tgl_transaksi)'));
$maxTahun = (int) DB::table('transactions')->max(DB::raw('YEAR(tgl_transaksi)'));
echo "Tahun range: {$minTahun} - {$maxTahun}\n";

$start = microtime(true);
$total = 0;

foreach ($accounts as $account) {
    for ($tahun = $minTahun; $tahun <= $maxTahun; $tahun++) {
        for ($m = 1; $m <= 12; $m++) {
            $bulanStr = str_pad($m, 2, '0', STR_PAD_LEFT);
            $startOfYear = "{$tahun}-01-01";
            $endOfMonth = Carbon::create($tahun, $m, 1)->endOfMonth()->toDateString();

            $debit = (float) DB::table('transactions')
                ->where('account_debet', $account->kode_akun)
                ->whereNull('deleted_at')
                ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                ->sum('saldo');

            $kredit = (float) DB::table('transactions')
                ->where('account_kredit', $account->kode_akun)
                ->whereNull('deleted_at')
                ->whereBetween('tgl_transaksi', [$startOfYear, $endOfMonth])
                ->sum('saldo');

            if ($debit > 0 || $kredit > 0) {
                $id = (string) $account->id . $tahun . $bulanStr;
                DB::table('amount')->insert([
                    'id' => $id,
                    'account_id' => $account->id,
                    'tahun' => $tahun,
                    'bulan' => $bulanStr,
                    'debit' => $debit,
                    'kredit' => $kredit,
                ]);
                $total++;
            }
        }
    }
}

$elapsed = round(microtime(true) - $start, 1);
echo "Inserted: {$total} amount rows in {$elapsed}s\n";
echo "Final amount count: " . DB::table('amount')->count() . "\n";