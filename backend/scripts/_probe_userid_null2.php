<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tiket NULL (completed/terminated) vs legacy customers & installations ===\n";
$newTickets = DB::table('installation_tickets')
    ->select('id','applicant_name','status','village_id','package_id')
    ->whereNull('user_id')
    ->whereIn('status', ['completed','terminated','suspended'])
    ->orderBy('id')
    ->limit(8)
    ->get();

foreach ($newTickets as $nt) {
    echo "\n--- new ticket #{$nt->id} '{$nt->applicant_name}' status={$nt->status} village={$nt->village_id} pkg={$nt->package_id} ---\n";

    // Cari di legacy customers by nama
    $legacyCusts = DB::connection('legacy')
        ->table('customers')
        ->where('nama', 'LIKE', $nt->applicant_name.'%')
        ->get();

    if ($legacyCusts->isEmpty()) {
        echo "  [tidak ketemu di legacy.customers]\n";
        continue;
    }

    foreach ($legacyCusts as $lc) {
        echo "  legacy customer #{$lc->id} '{$lc->nama}':\n";
        $insts = DB::connection('legacy')
            ->table('installations')
            ->where('customer_id', $lc->id)
            ->orderBy('id')
            ->get();
        foreach ($insts as $i) {
            printf("    inst#%d status=%s pkg=%d desa=%d kode=%s\n",
                $i->id, $i->status, $i->package_id, $i->desa, $i->kode_instalasi);
        }
    }
}

echo "\n\n=== Coba lookup by kode_instalasi di legacy.installations, apakah tiket baru punya padanan? ===\n";
$orphans = DB::table('installation_tickets')
    ->whereNull('user_id')
    ->whereIn('status', ['completed','terminated','suspended'])
    ->count();
echo "Total NULL+non-pending: $orphans\n";

echo "\n=== Cek: 222 tiket NULL, apakah SEMUA berasal dari customer legacy yg punya installations.status='A'? ===\n";
// Heuristic: by nama applicant_name, lihat di legacy customers
$nullTickets = DB::table('installation_tickets')
    ->select('id','applicant_name')
    ->whereNull('user_id')
    ->whereIn('status', ['completed','terminated','suspended'])
    ->orderBy('id')
    ->get();

$foundLegacy = 0;
$notFoundLegacy = 0;
$hasInstA = 0;
$noInstA = 0;
$hasCustNoInst = 0;
$matchedByTriple = 0;
$matchedByName = 0;

foreach ($nullTickets as $nt) {
    $lc = DB::connection('legacy')
        ->table('customers')
        ->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower(trim($nt->applicant_name))])
        ->first();

    if (! $lc) {
        $notFoundLegacy++;
        continue;
    }
    $foundLegacy++;

    $hasA = DB::connection('legacy')
        ->table('installations')
        ->where('customer_id', $lc->id)
        ->where('status','A')
        ->exists();

    if ($hasA) {
        $hasInstA++;
    } else {
        $noInstA++;
    }
}

echo "NULL+completed/term/susp total: ".count($nullTickets)."\n";
echo "  -> Ketemu di legacy.customers by name : $foundLegacy\n";
echo "  -> Tidak ketemu di legacy.customers    : $notFoundLegacy\n";
echo "     di antaranya yg punya inst.status=A : $hasInstA\n";
echo "     di antaranya yg TIDAK punya inst.A : $noInstA\n";
