<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Total installation_tickets ===\n";
$total = DB::table('installation_tickets')->count();
echo "Total: $total\n\n";

echo "=== Breakdown user_id NULL vs NOT NULL ===\n";
$nullCount = DB::table('installation_tickets')->whereNull('user_id')->count();
$filledCount = $total - $nullCount;
echo "NULL user_id : $nullCount\n";
echo "Filled       : $filledCount\n\n";

echo "=== Status breakdown untuk user_id NULL ===\n";
$rows = DB::table('installation_tickets')
    ->select('status', DB::raw('COUNT(*) as c'))
    ->whereNull('user_id')
    ->groupBy('status')
    ->orderByDesc('c')
    ->get();
foreach ($rows as $r) {
    printf("  %-12s : %d\n", $r->status, $r->c);
}

echo "\n=== Apakah ada customers row untuk tiket yg user_id NULL? ===\n";
$ticketIdsNull = DB::table('installation_tickets')->whereNull('user_id')->pluck('id')->all();
$withCustomers = DB::table('customers')->whereIn('ticket_id', $ticketIdsNull)->count();
echo "Tiket NULL dgn customers row: $withCustomers\n";
echo "Tiket NULL tanpa customers  : ".(count($ticketIdsNull) - $withCustomers)."\n";

echo "\n=== Sample 10 tiket NULL ===\n";
$samples = DB::table('installation_tickets')
    ->select('id','applicant_name','nik','status','order_date','created_at')
    ->whereNull('user_id')
    ->orderBy('id')
    ->limit(10)
    ->get();
foreach ($samples as $s) {
    printf("  id=%-5d | %-25s | nik=%-18s | status=%-10s | order=%s\n",
        $s->id, $s->applicant_name, $s->nik ?? 'NULL', $s->status, $s->order_date ?? 'NULL');
}

echo "\n=== Legacy check: instalasi dgn status='A' hitungannya ===\n";
try {
    $legacyA = DB::connection('legacy')->table('installations')->where('status','A')->count();
    $legacyAll = DB::connection('legacy')->table('installations')->count();
    $legacyCust = DB::connection('legacy')->table('customers')->count();
    $legacyCustNoInstA = DB::connection('legacy')->table('customers')
        ->whereNotIn('id', function($q){
            $q->select('customer_id')->from('installations')->where('status','A');
        })->count();
    echo "legacy.installations        : $legacyAll\n";
    echo "legacy.installations (A)    : $legacyA\n";
    echo "legacy.customers            : $legacyCust\n";
    echo "legacy.customers TANPA inst.A: $legacyCustNoInstA\n";
} catch (\Throwable $e) {
    echo "Legacy error: ".$e->getMessage()."\n";
}
