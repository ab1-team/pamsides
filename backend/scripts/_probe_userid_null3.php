<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Reproduksi logika buildTicketMap persis seperti di ImportLegacyCustomersCommand
echo "=== Reproduksi bug lookup di buildTicketMap ===\n\n";

// Step 2: ambil legacy inst_id dari ticketMap (semua customer yg punya inst.status=A)
$sql = "SELECT customer_id, MIN(id) AS inst_id FROM installations WHERE status='A' GROUP BY customer_id";
$rows = DB::connection('legacy')->select($sql);
$ticketMap = [];
foreach ($rows as $r) {
    $ticketMap[(int)$r->customer_id] = (int)$r->inst_id;
}
echo "ticketMap entries: ".count($ticketMap)."\n";

$legacyInstIds = array_values($ticketMap);
$legacyInsts = DB::connection('legacy')->table('installations')
    ->whereIn('id', $legacyInstIds)
    ->get(['id','kode_instalasi','package_id','desa','aktif']);

// Step 3: pre-load new tickets
$newTickets = DB::table('installation_tickets')
    ->orderBy('id')
    ->get(['id','applicant_name','village_id','package_id']);

$byTriple = [];
foreach ($newTickets as $nt) {
    $key = strtolower(trim($nt->applicant_name)).'|'.$nt->village_id.'|'.$nt->package_id;
    if (! isset($byTriple[$key])) {
        $byTriple[$key] = $nt->id;
    }
}

$byName = [];
foreach ($newTickets as $nt) {
    $key = strtolower(trim($nt->applicant_name));
    if (! isset($byName[$key])) {
        $byName[$key] = $nt->id;
    }
}

$instToCust = array_flip($ticketMap);
$custIds = array_keys($ticketMap);
$customers = DB::connection('legacy')->table('customers')->whereIn('id', $custIds)->get(['id','nama']);
$custById = [];
foreach ($customers as $c) {
    $custById[(int)$c->id] = $c;
}

$byTripleHits = 0;
$byNameHits = 0;
$noMatch = 0;
$samples = [];

foreach ($legacyInsts as $li) {
    $custId = $instToCust[$li->id] ?? null;
    if (! $custId) continue;
    $cust = $custById[$custId] ?? null;
    if (! $cust) continue;

    $name = strtolower(trim($cust->nama ?? ''));
    // PERHATIKAN: key di sini pakai legacy package_id, tapi $byTriple key-nya pakai NEW package_id
    $key = $name.'|'.$li->desa.'|'.$li->package_id;

    if (isset($byTriple[$key])) {
        $byTripleHits++;
    } elseif (isset($byName[$name])) {
        $byNameHits++;
        if (count($samples) < 5) {
            $samples[] = ['legacy_inst'=>$li->id, 'name'=>$name, 'legacy_pkg'=>$li->package_id, 'desa'=>$li->desa, 'mapped_to'=>$byName[$name]];
        }
    } else {
        $noMatch++;
    }
}

echo "\nHasil:\n";
echo "  byTriple MATCH (legacy pkg=NEW pkg, mustahil): $byTripleHits\n";
echo "  byName   MATCH (fallback name only)         : $byNameHits\n";
echo "  No match                                   : $noMatch\n";

echo "\nSample byName hits:\n";
foreach ($samples as $s) {
    // lihat new ticket yg di-match, package_id nya berapa
    $t = DB::table('installation_tickets')->where('id', $s['mapped_to'])->first();
    printf("  legacy inst#%d name='%s' legacy_pkg=%d desa=%d -> new ticket#%d (new_pkg=%d village=%d)\n",
        $s['legacy_inst'], $s['name'], $s['legacy_pkg'], $s['desa'], $t->id, $t->package_id, $t->village_id);
}

echo "\n=== Hitung: kalau byName hits = 195 (yg punya inst.A tapi NULL), apakah cukup? ===\n";
// Estimate: dari 222 NULL total, 195 punya inst.A di legacy
// byName hits dari semua 3079 inst.A di legacy seharusnya jauh lebih besar
echo "Legacy inst.A total: ".DB::connection('legacy')->table('installations')->where('status','A')->count()."\n";
