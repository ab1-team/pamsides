<?php
/**
 * Fix: customers yang ada di legacy business_id=5 tapi belum dibuat di local.
 *
 * Untuk instalasi legacy (business_id=5) yang kode_instalasi-nya BELUM ada
 * di tabel customers local, tapi SUDAH ada ticket-nya di installation_tickets
 * (status=completed) — otomatis:
 *   - buat user role=pelanggan (kalau belum ada by nama)
 *   - buat customer dengan customer_code = kode_instalasi
 *   - set activated_at dari legacy installations.aktif
 *   - link ke installation_tickets yang applicant_name & teknisi cocok
 *
 * Filter: hanya instalasi yang ada ticket-nya di local (skip instalasi yang
 * ticket-nya belum dibuat sama sekali — perlu investigasi manual).
 *
 * Mode:
 *   --dry-run : simulasi
 *   --force   : eksekusi
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$opts = getopt('', ['dry-run', 'force', 'business::']);
$isDry = isset($opts['dry-run']);
$isForce = isset($opts['force']);
$businessId = isset($opts['business']) ? (int) $opts['business'] : 5;
if (! $isDry && ! $isForce) {
    fwrite(STDERR, "ERROR: pakai --dry-run atau --force\n");
    exit(1);
}

echo $isDry ? ">>> DRY-RUN <<<\n" : ">>> FORCE <<<\n";
echo "business_id filter: $businessId\n\n";

// 1. Map teknisi legacy (jabatan=5, business=5) → local user id by nama
$legacyCaterName = [];
foreach (DB::connection('legacy')->table('users')
    ->where('business_id', $businessId)
    ->where('jabatan', 5)
    ->get() as $u) {
    $legacyCaterName[(int) $u->id] = strtolower(trim((string) $u->nama));
}

$localTekByName = [];
foreach (DB::connection('mysql')->table('users')
    ->where('role', 'teknisi')
    ->get() as $u) {
    $localTekByName[strtolower(trim((string) $u->name))] = (int) $u->id;
}

// 2. Map customer_code → customer (di local)
$localCodes = DB::connection('mysql')->table('customers')->pluck('customer_code')->all();
$localCodeSet = array_flip(array_filter($localCodes));

// 3. Index local ticket: teknisi+applicant_name+village_id → ticket_id
$localTicketIdx = [];
foreach (DB::connection('mysql')->table('installation_tickets')
    ->where('status', 'completed')
    ->whereIn('user_id', array_values($localTekByName))
    ->select('id', 'user_id', 'applicant_name', 'village_id')
    ->get() as $t) {
    $key = $t->user_id . '|' . strtolower(trim((string) $t->applicant_name)) . '|' . $t->village_id;
    if (! isset($localTicketIdx[$key])) $localTicketIdx[$key] = [];
    $localTicketIdx[$key][] = (int) $t->id;
}

// 4. Ambil legacy instalasi business_id=5
$legacyInst = DB::connection('legacy')->table('installations as i')
    ->join('customers as c', 'c.id', '=', 'i.customer_id')
    ->where('i.business_id', $businessId)
    ->select('i.*', 'c.nama as cust_nama', 'c.foto as cust_foto')
    ->get();

$stats = [
    'candidates' => 0,
    'have_local_ticket' => 0,
    'created' => 0,
    'skipped_already_exists' => 0,
    'skipped_no_ticket' => 0,
    'failed' => 0,
];
$samples = [];

function makeEmail(string $name, string $code): string
{
    $local = strtolower($name);
    $local = preg_replace('/[^a-z0-9]/', '', $local);
    if ($local === '' || strlen($local) < 2) {
        $local = 'user';
    }
    $local .= '.' . preg_replace('/[^a-z0-9]/', '', strtolower($code));
    return substr($local, 0, 60) . '@pelanggan.local';
}

$now = now();

foreach ($legacyInst as $inst) {
    $code = (string) $inst->kode_instalasi;
    if (isset($localCodeSet[$code])) {
        $stats['skipped_already_exists']++;
        continue;
    }
    $stats['candidates']++;

    $caterNm = $legacyCaterName[(int) $inst->cater_id] ?? '';
    $localTekId = $localTekByName[$caterNm] ?? null;
    if ($localTekId === null) {
        $stats['skipped_no_ticket']++;
        continue;
    }

    $cNama = (string) $inst->cust_nama;
    $key = $localTekId . '|' . strtolower(trim($cNama)) . '|' . $inst->desa;
    if (empty($localTicketIdx[$key])) {
        $stats['skipped_no_ticket']++;
        continue;
    }
    $stats['have_local_ticket']++;

    // Resolve ticket_id (kalau >1, ambil yang pertama)
    $ticketIds = $localTicketIdx[$key];
    // Kalau customer sudah ada untuk ticket itu, skip
    $existing = DB::connection('mysql')->table('customers')->whereIn('ticket_id', $ticketIds)->first();
    if ($existing) {
        $stats['skipped_already_exists']++;
        continue;
    }

    $ticketId = $ticketIds[0];
    $samples[] = "kode=$code cust='$cNama' cater=$caterNm (local_id=$localTekId) village={$inst->desa} ticket#$ticketId";

    if ($isForce) {
        DB::connection('mysql')->beginTransaction();
        try {
            // 1. Cari/buat user pelanggan
            $userId = DB::connection('mysql')->table('users')
                ->where('role', 'pelanggan')
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($cNama))])
                ->value('id');
            if (! $userId) {
                $email = makeEmail($cNama, $code);
                $suffix = 0;
                $baseEmail = $email;
                while (DB::connection('mysql')->table('users')->where('email', $email)->exists()) {
                    $suffix++;
                    $email = preg_replace('/@/', $suffix . '@', $baseEmail, 1);
                    if ($suffix > 50) throw new \RuntimeException("email collision");
                }
                $userId = DB::connection('mysql')->table('users')->insertGetId([
                    'name'       => $cNama,
                    'email'      => $email,
                    'password'   => Hash::make('password'),
                    'role'       => 'pelanggan',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2. Buat customer dengan customer_code = kode_instalasi
            $aktif = $inst->aktif && $inst->aktif !== '0000-00-00' ? $inst->aktif : '2022-01-01';
            $customerId = DB::connection('mysql')->table('customers')->insertGetId([
                'ticket_id'             => $ticketId,
                'user_id'               => $userId,
                'customer_code'         => $code,
                'initial_meter_reading' => 0,
                'meter_photo_url'       => $inst->cust_foto ?: null,
                'activated_at'          => $aktif,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Tandai ticket sudah selesai (sudah completed, jadi tidak perlu update)
            DB::connection('mysql')->commit();
            $stats['created']++;
        } catch (\Throwable $e) {
            DB::connection('mysql')->rollBack();
            $stats['failed']++;
            echo "  [FAIL] $code: {$e->getMessage()}" . PHP_EOL;
        }
    }
}

echo "=== Ringkasan ===\n";
echo "Total instalasi legacy business_id=$businessId : " . count($legacyInst) . "\n";
echo "Kandidat (no customer_code di local)         : {$stats['candidates']}\n";
echo "Punya ticket lokal (akan diproses)            : {$stats['have_local_ticket']}\n";
echo "Created                                       : {$stats['created']}\n";
echo "Skipped (sudah ada)                           : {$stats['skipped_already_exists']}\n";
echo "Skipped (no ticket lokal)                     : {$stats['skipped_no_ticket']}\n";
echo "Failed                                        : {$stats['failed']}\n\n";

echo "=== Sample (first 20) ===\n";
foreach (array_slice($samples, 0, 20) as $s) echo "  $s\n";

if ($isDry && $stats['have_local_ticket'] > 0) {
    echo "\n*** DRY-RUN. Jalankan dengan --force untuk mengeksekusi. ***\n";
}