<?php
/**
 * Fix: monthly_bills untuk customer-customer yang baru dibuat oleh
 *      fix_customers_missing.php (162 customer baru business_id=5).
 *
 * Strategi:
 * 1. Ambil customer lokal yang customer_code-nya persis sama dengan kode_instalasi legacy
 *    (tidak ada suffix _cNNN — ini produk import fix_customers_missing.php).
 * 2. Untuk tiap customer, ambil legacy.usages by kode_instalasi.
 * 3. Insert ke monthly_bills.
 *
 * Abodemen: pakai monthly_abodemen dari ticket.package_id di local.
 * Penalty: dihitung dari tagihan 2 bulan sebelum unpaid (sesuai docs).
 *
 * Mode:
 *   --dry-run : simulasi
 *   --force   : eksekusi
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\InstallationPackage;

$opts = getopt('', ['dry-run', 'force', 'business::']);
$isDry = isset($opts['dry-run']);
$isForce = isset($opts['force']);
$businessId = isset($opts['business']) ? (int) $opts['business'] : 5;
if (! $isDry && ! $isForce) {
    fwrite(STDERR, "ERROR: pakai --dry-run atau --force\n");
    exit(1);
}

echo $isDry ? ">>> DRY-RUN <<<\n" : ">>> FORCE <<<\n";
echo "Filter business_id=$businessId\n\n";

// 1. Ambil customer local yang punya ticket user_id teknisi
$custRows = DB::connection('mysql')->table('customers as c')
    ->join('installation_tickets as t', 't.id', '=', 'c.ticket_id')
    ->join('users as u', 'u.id', '=', 't.user_id')
    ->where('u.role', 'teknisi')
    ->select('c.id as cust_id', 'c.customer_code', 't.id as ticket_id', 't.package_id')
    ->get();

echo "Total customer lokal dengan teknisi: " . count($custRows) . PHP_EOL;

// 2. Index local tickets by customer
$ticketToCust = [];
foreach ($custRows as $r) {
    $ticketToCust[(int) $r->ticket_id] = (int) $r->cust_id;
}

// 3. Map abodemen by package_id
$abodemenByPkg = [];
foreach (DB::connection('mysql')->table('installation_packages')->get(['id', 'monthly_abodemen', 'late_penalty']) as $p) {
    $abodemenByPkg[(int) $p->id] = [
        'abodemen' => (float) $p->monthly_abodemen,
        'penalty'  => (float) ($p->late_penalty ?? 0),
    ];
}

// 4. Existing monthly_bills index by customer|year|month
$existingMB = [];
foreach (DB::connection('mysql')->table('monthly_bills')->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month', 'status']) as $mb) {
    $existingMB[(int) $mb->customer_id . '|' . $mb->billing_period_year . '|' . $mb->billing_period_month] = (int) $mb->id;
}

// 5. Ambil semua legacy usages untuk business_id=$businessId, kelompokkan by kode_instalasi
echo "Loading legacy usages...\n";
$usagesByCode = [];
$rows = DB::connection('legacy')->table('usages')
    ->where('business_id', $businessId)
    ->orderBy('tgl_pemakaian')->get();
foreach ($rows as $u) {
    $code = (string) $u->kode_instalasi;
    if (! isset($usagesByCode[$code])) $usagesByCode[$code] = [];
    $usagesByCode[$code][] = $u;
}
echo "Total usages loaded: " . count($rows) . " (codes: " . count($usagesByCode) . ")\n\n";

$stats = [
    'candidates'      => 0,
    'usages_found'    => 0,
    'usages_inserted' => 0,
    'usages_updated'  => 0,
    'usages_skipped'  => 0,
    'failed'          => 0,
];
$samples = [];
$now = now();

foreach ($custRows as $cr) {
    $code = (string) $cr->customer_code;
    if (! isset($usagesByCode[$code])) {
        continue;
    }
    $stats['candidates']++;
    $stats['usages_found'] += count($usagesByCode[$code]);

    $pkg = $abodemenByPkg[(int) $cr->package_id] ?? ['abodemen' => 0, 'penalty' => 0];

    foreach ($usagesByCode[$code] as $u) {
        $tgl = trim((string) ($u->tgl_pemakaian ?? ''));
        if ($tgl === '' || strlen($tgl) < 8) {
            $stats['usages_skipped']++;
            continue;
        }
        try {
            $date = \Carbon\Carbon::parse($tgl);
        } catch (\Throwable) {
            $stats['usages_skipped']++;
            continue;
        }
        $year = (int) $date->year;
        $month = (int) $date->month;

        $start = (int) filter_var((string) ($u->awal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
        $end   = (int) filter_var((string) ($u->akhir ?? '0'), FILTER_SANITIZE_NUMBER_INT);
        if ($start < 0) $start = 0;
        if ($end < 0)   $end = 0;
        $usageM3 = max(0, $end - $start);

        $nominal = (int) filter_var((string) ($u->nominal ?? '0'), FILTER_SANITIZE_NUMBER_INT);
        if ($nominal < 0) $nominal = 0;

        $statusMap = ['PAID' => 'paid', 'UNPAID' => 'unpaid', 'NON' => 'unpaid'];
        $status = $statusMap[strtoupper(trim((string) ($u->status ?? 'UNPAID')))] ?? 'unpaid';

        $dueDate = null;
        $tglAkhir = trim((string) ($u->tgl_akhir ?? ''));
        if ($tglAkhir !== '' && strlen($tglAkhir) >= 8) {
            try {
                $dueDate = \Carbon\Carbon::parse($tglAkhir)->toDateString();
            } catch (\Throwable) {
                $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
            }
        } else {
            $dueDate = $date->copy()->addMonth()->day(20)->toDateString();
        }

        // Penalty: cek 2 bulan sebelum unpaid → pakai late_penalty paket
        $penalty = 0;
        $checkDate = $date->copy()->subMonths(2);
        $checkKey = (int) $cr->cust_id . '|' . $checkDate->year . '|' . $checkDate->month;
        if (isset($existingMB[$checkKey])) {
            $checkBill = DB::connection('mysql')->table('monthly_bills')->where('id', $existingMB[$checkKey])->first();
            if ($checkBill && $checkBill->status === 'unpaid') {
                $penalty = $pkg['penalty'];
            }
        }

        $total = $nominal + $pkg['abodemen'] + $penalty;

        $key = (int) $cr->cust_id . '|' . $year . '|' . $month;
        $data = [
            'customer_id'           => (int) $cr->cust_id,
            'billing_period_year'   => $year,
            'billing_period_month'  => $month,
            'meter_reading_start'   => $start,
            'meter_reading_end'     => $end,
            'usage_m3'              => $usageM3,
            'usage_charge'          => $nominal,
            'abodemen'              => $pkg['abodemen'],
            'penalty_amount'        => $penalty,
            'total_amount'          => $total,
            'status'                => $status,
            'due_date'              => $dueDate,
            'updated_at'            => $now,
        ];

        if (count($samples) < 20) {
            $samples[] = sprintf("cust=%s ym=%d-%d usage=%d abodemen=%.0f penalty=%.0f total=%.0f status=%s",
                $code, $year, $month, $nominal, $pkg['abodemen'], $penalty, $total, $status);
        }

        if (isset($existingMB[$key])) {
            if ($isForce) {
                DB::connection('mysql')->table('monthly_bills')->where('id', $existingMB[$key])->update($data);
            }
            $stats['usages_updated']++;
        } else {
            $data['created_at'] = $now;
            if ($isForce) {
                DB::connection('mysql')->table('monthly_bills')->insert($data);
            }
            $existingMB[$key] = 0;
            $stats['usages_inserted']++;
        }
    }
}

echo "=== Ringkasan ===\n";
echo "Customer diproses                       : {$stats['candidates']}\n";
echo "Usages ditemukan                        : {$stats['usages_found']}\n";
echo "Usages di-insert                        : {$stats['usages_inserted']}\n";
echo "Usages di-update                        : {$stats['usages_updated']}\n";
echo "Usages di-skip                          : {$stats['usages_skipped']}\n";
echo "Failed                                  : {$stats['failed']}\n\n";

echo "=== Sample (20) ===\n";
foreach ($samples as $s) echo "  $s\n";

if ($isDry && $stats['usages_found'] > 0) {
    echo "\n*** DRY-RUN. Jalankan dengan --force untuk mengeksekusi. ***\n";
}