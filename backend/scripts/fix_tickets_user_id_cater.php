<?php
/**
 * Fix: installation_tickets.user_id untuk tiket business_id=5 yang cater-nya
 *       salah di data baru.
 *
 * Cross-reference:
 *   legacy.installations.kode_instalasi = local.customers.customer_code
 *   legacy.installations.cater_id     -> legacy.users (jabatan=5, business_id=5)
 *   legacy.users.nama                 -> local.users (role=teknisi)
 *
 * Untuk setiap tiket business_id=5 yang user_id-nya teknisi tapi nama teknisi
 * TIDAK sama dengan cater_id legacy, update ke teknisi yang benar.
 *
 * Mode:
 *   --dry-run : simulasi
 *   --force   : eksekusi
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$opts = getopt('', ['dry-run', 'force']);
$isDry = isset($opts['dry-run']);
$isForce = isset($opts['force']);
if (! $isDry && ! $isForce) {
    fwrite(STDERR, "ERROR: pakai --dry-run atau --force\n");
    exit(1);
}

echo $isDry ? ">>> DRY-RUN <<<\n" : ">>> FORCE <<<\n\n";

// Map: legacy cater name (lowercase) -> legacy cater user id
$legacyCaterByName = [];
foreach (DB::connection('legacy')->table('users')
    ->where('business_id', 5)->where('jabatan', 5)->get() as $u) {
    $nm = strtolower(trim((string) $u->nama));
    if ($nm === '') continue;
    $legacyCaterByName[$nm] = (int) $u->id;
}

// Map: local teknisi name (lowercase) -> local user id
$localTeknisiByName = [];
foreach (DB::connection('mysql')->table('users')
    ->where('role', 'teknisi')->get() as $u) {
    $nm = strtolower(trim((string) $u->name));
    if ($nm === '') continue;
    $localTeknisiByName[$nm] = (int) $u->id;
}

echo "legacy caters   : " . count($legacyCaterByName) . "\n";
echo "local teknisi   : " . count($localTeknisiByName) . "\n\n";

// Map: customer_code -> [ticket_id, current_user_id]
$ticketByCode = [];
foreach (DB::connection('mysql')->table('customers as c')
    ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
    ->select('c.customer_code', 't.id as ticket_id', 't.user_id')
    ->get() as $r) {
    $ticketByCode[(string) $r->customer_code] = [
        'ticket_id' => (int) $r->ticket_id,
        'user_id'   => (int) $r->user_id,
    ];
}

// Ambil semua installations business_id=5 dengan nama cater valid
$instRows = DB::connection('legacy')->table('installations as i')
    ->join('users as u', 'u.id', '=', 'i.cater_id')
    ->where('i.business_id', 5)
    ->select('i.kode_instalasi', 'u.nama as cater_name')
    ->get();

$fixed = 0;
$alreadyOk = 0;
$skipped = 0;
$log = [];

foreach ($instRows as $row) {
    $code = (string) $row->kode_instalasi;
    $caterNm = strtolower(trim((string) $row->cater_name));
    if (! isset($localTeknisiByName[$caterNm])) {
        $skipped++;
        continue;
    }
    $expected = $localTeknisiByName[$caterNm];
    if (! isset($ticketByCode[$code])) {
        // instalasi legacy tidak ada di local customers → di luar scope
        continue;
    }
    $cur = $ticketByCode[$code]['user_id'];
    $tid = $ticketByCode[$code]['ticket_id'];
    if ($cur === $expected) {
        $alreadyOk++;
        continue;
    }
    // cek user_id lama role teknisi?
    $curUser = DB::connection('mysql')->table('users')->where('id', $cur)->first();
    $log[] = sprintf(
        "ticket#%d code=%s expected_user=%d ('%s') -> was_user=%d ('%s' role=%s)",
        $tid, $code, $expected, $caterNm, $cur, $curUser?->name, $curUser?->role
    );
    if ($isForce) {
        DB::connection('mysql')->table('installation_tickets')
            ->where('id', $tid)
            ->update([
                'user_id'    => $expected,
                'updated_at' => now(),
            ]);
    }
    $fixed++;
}

echo "=== Ringkasan ===\n";
echo "Instalasi legacy business_id=5 diproses       : " . count($instRows) . "\n";
echo "Sudah benar                                    : $alreadyOk\n";
echo "MISMATCH diperbaiki (planned/eks" . ($isDry ? " simulasi" : "ekusi") . ") : $fixed\n";
echo "Skip (cater name kosong di legacy)             : $skipped\n";

if ($fixed > 0) {
    echo "\n=== Detail perubahan ===\n";
    foreach ($log as $l) echo "  $l\n";
}

if ($isDry && $fixed > 0) {
    echo "\n*** Ini DRY-RUN. Jalankan dengan --force untuk mengeksekusi. ***\n";
} elseif ($isForce && $fixed > 0) {
    // Verifikasi post-update
    $remaining = 0;
    foreach ($instRows as $row) {
        $code = (string) $row->kode_instalasi;
        $caterNm = strtolower(trim((string) $row->cater_name));
        if (! isset($localTeknisiByName[$caterNm]) || ! isset($ticketByCode[$code])) continue;
        $expected = $localTeknisiByName[$caterNm];
        $cur = DB::connection('mysql')->table('customers as c')
            ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
            ->where('c.customer_code', $code)
            ->value('t.user_id');
        if ((int) $cur !== $expected) $remaining++;
    }
    echo "\nSisa mismatch setelah update: $remaining\n";
}