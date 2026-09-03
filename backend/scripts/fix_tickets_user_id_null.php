<?php
/**
 * Fix: installation_tickets.user_id NULL → link ke users (existing atau baru)
 *
 * Strategi:
 * 1. Untuk setiap tiket NULL, coba cari user existing:
 *    - by email (kalau di legacy ada email, atau derivatif)
 *    - by nama + role=pelanggan
 * 2. Kalau tidak ketemu, buat user baru dengan email unik.
 * 3. Set installation_tickets.user_id = user.id
 *
 * Mode:
 *  --dry-run  : cuma simulasi, tidak ada insert/update
 *  --force    : jalankan
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$opts = getopt('', ['dry-run', 'force', 'chunk::']);
$isDryRun = isset($opts['dry-run']);
$isForce  = isset($opts['force']);
$chunkSize = isset($opts['chunk']) ? max(1, (int)$opts['chunk']) : 100;

if (! $isDryRun && ! $isForce) {
    fwrite(STDERR, "ERROR: pakai --dry-run atau --force\n");
    exit(1);
}

echo $isDryRun ? ">>> DRY-RUN (tidak ada perubahan) <<<\n" : ">>> FORCE (data akan berubah) <<<\n";
echo "chunk size: $chunkSize\n\n";

// Statistik
$stats = [
    'tickets_total'   => 0,
    'linked_existing' => 0,
    'linked_new'      => 0,
    'skipped'         => 0,
    'failed'          => 0,
];

// Ambil semua tiket NULL user_id
$tickets = DB::table('installation_tickets')
    ->whereNull('user_id')
    ->orderBy('id')
    ->get();

$stats['tickets_total'] = count($tickets);
echo "Total tiket NULL user_id: {$stats['tickets_total']}\n\n";

// Helper: generate email unik dari nama + ticket id
function makeEmail(string $name, int $ticketId): string
{
    $local = strtolower($name);
    $local = preg_replace('/[^a-z0-9]/', '', $local);
    if ($local === '' || strlen($local) < 2) {
        $local = 'user';
    }
    return $local.'.t'.$ticketId.'@pelanggan.local';
}

$now = now();

foreach ($tickets as $i => $t) {
    $tid = (int)$t->id;
    $name = trim((string)$t->applicant_name);

    if ($name === '') {
        $stats['skipped']++;
        echo "  [skip] ticket#$tid nama kosong\n";
        continue;
    }

    try {
        // Coba cari user existing by nama (case-insensitive) role=pelanggan
        $existing = DB::table('users')
            ->where('role', 'pelanggan')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        if (! $existing) {
            // coba by nama dengan suffix (mis. "Sukidi Ld") dan tanpa suffix
            $parts = preg_split('/\s+/', $name);
            if (count($parts) > 1) {
                $first = $parts[0];
                $existing = DB::table('users')
                    ->where('role', 'pelanggan')
                    ->whereRaw('LOWER(TRIM(name)) LIKE ?', [strtolower($first).'%'])
                    ->orderBy('id')
                    ->first();
            }
        }

        if ($existing) {
            $userId = (int)$existing->id;
            $stats['linked_existing']++;
            if (! $isDryRun) {
                DB::table('installation_tickets')
                    ->where('id', $tid)
                    ->update(['user_id' => $userId, 'updated_at' => $now]);
            }
            echo "  [link-existing] ticket#$tid '{$name}' -> user#$userId '{$existing->name}'\n";
        } else {
            // Buat user baru
            $email = makeEmail($name, $tid);
            // pastikan unik (cek existing)
            $suffix = 0;
            $baseEmail = $email;
            while (DB::table('users')->where('email', $email)->exists()) {
                $suffix++;
                $email = preg_replace('/@/', $suffix.'@', $baseEmail, 1);
                if ($suffix > 50) {
                    throw new \RuntimeException("Email collision > 50 utk ticket#$tid");
                }
            }

            if (! $isDryRun) {
                $userId = DB::table('users')->insertGetId([
                    'name'       => $name,
                    'email'      => $email,
                    'password'   => Hash::make('password'),
                    'role'       => 'pelanggan',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('installation_tickets')
                    ->where('id', $tid)
                    ->update(['user_id' => $userId, 'updated_at' => $now]);
            } else {
                $userId = '(dry-run)';
            }
            $stats['linked_new']++;
            echo "  [link-new] ticket#$tid '{$name}' -> user#$userId email=$email\n";
        }
    } catch (\Throwable $e) {
        $stats['failed']++;
        echo "  [FAIL] ticket#$tid '{$name}' : {$e->getMessage()}\n";
    }

    if (($i + 1) % $chunkSize === 0) {
        echo sprintf("  -- progress %d/%d --\n", $i + 1, count($tickets));
    }
}

echo "\n=== Ringkasan ===\n";
echo "Total tiket NULL         : {$stats['tickets_total']}\n";
echo "Linked ke existing user   : {$stats['linked_existing']}\n";
echo "Linked ke user baru       : {$stats['linked_new']}\n";
echo "Skipped (nama kosong)     : {$stats['skipped']}\n";
echo "Failed                    : {$stats['failed']}\n";

$remaining = DB::table('installation_tickets')->whereNull('user_id')->count();
echo "\nSisa NULL setelah proses  : $remaining\n";
