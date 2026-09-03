<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Cek: untuk 222 tiket NULL, berapa nama yang punya multiple user existing di users.role=pelanggan?
$tickets = DB::table('installation_tickets')
    ->whereNull('user_id')
    ->orderBy('id')
    ->get();

$nameCount = [];
foreach ($tickets as $t) {
    $n = strtolower(trim($t->applicant_name));
    if ($n === '') continue;
    $nameCount[$n] = ($nameCount[$n] ?? 0) + 1;
}

$userByName = [];
$users = DB::table('users')->where('role','pelanggan')->get(['id','name']);
foreach ($users as $u) {
    $n = strtolower(trim($u->name));
    $userByName[$n][] = $u->id;
}

$ambigTicket = 0;
$ambigName = [];
$totalTickets = 0;
foreach ($nameCount as $name => $ticketsN) {
    $totalTickets += $ticketsN;
    $usersN = isset($userByName[$name]) ? count($userByName[$name]) : 0;
    if ($usersN > 1 && $ticketsN > 1) {
        // ambiguous: nama ini punya multiple user existing DAN multiple tiket NULL
        // patching akan link semua tiket ke user ID terkecil (pertama di orderBy)
        $ambigTicket += $ticketsN;
        $ambigName[$name] = ['tickets'=>$ticketsN, 'users'=>$usersN];
    }
}

echo "Total tiket NULL: $totalTickets\n";
echo "Tiket dengan nama AMBIGU (multiple user existing + multiple tiket NULL): $ambigTicket\n\n";
echo "Detail (top 10 by jumlah tiket):\n";
uasort($ambigName, fn($a,$b) => $b['tickets'] <=> $a['tickets']);
$i = 0;
foreach ($ambigName as $name => $info) {
    if ($i++ >= 10) break;
    printf("  '%s' : %d tiket NULL, %d user existing\n", $name, $info['tickets'], $info['users']);
}
