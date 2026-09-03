<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tabel villages schema ===\n";
$cols = DB::select('DESCRIBE villages');
foreach ($cols as $c) {
    echo "  $c->Field | $c->Type | null=$c->Null\n";
}

echo "\n=== Sample villages ===\n";
$v = DB::table('villages')->limit(5)->get();
foreach ($v as $r) {
    printf("  id=%d | setting_id=%s | village=%s | hamlet=%s\n",
        $r->id, $r->setting_id ?? 'NULL', $r->village_name, $r->hamlet_name);
}

echo "\n=== Sample customers (customer_code) ===\n";
$c = DB::table('customers')->limit(15)->get(['id','ticket_id','customer_code','user_id']);
foreach ($c as $r) {
    printf("  cust#%d | ticket#%d | code='%s' | user#%s\n",
        $r->id, $r->ticket_id, $r->customer_code, $r->user_id ?? 'NULL');
}

echo "\n=== Distribusi customer_code ===\n";
echo "Total customers: ".DB::table('customers')->count()."\n";
echo "Distinct customer_code: ".DB::table('customers')->distinct()->count('customer_code')."\n";
echo "NULL customer_code: ".DB::table('customers')->whereNull('customer_code')->count()."\n";
echo "Sample pattern: \n";
$samples = DB::table('customers')->select('customer_code')->limit(20)->get();
foreach ($samples as $s) echo "  '".($s->customer_code ?? 'NULL')."'\n";

echo "\n=== Link customers.ticket_id -> installation_tickets.village_id ===\n";
$link = DB::table('customers as c')
    ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
    ->select('c.id','c.customer_code','t.village_id','t.applicant_name')
    ->limit(10)->get();
foreach ($link as $r) {
    $vname = DB::table('villages')->where('id', $r->village_id)->value('village_name');
    printf("  cust#%d code='%s' -> village_id=%d (%s)\n",
        $r->id, $r->customer_code, $r->village_id ?? 0, $vname ?? 'NULL');
}
