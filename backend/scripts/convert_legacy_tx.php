<?php
/**
 * Convert legacy transactions → multi-row INSERT untuk di-load via mysql CLI.
 * Sumber: DB legacy (server 103.177.95.92).
 * Output: storage/app/backup/db_legacy_tx_<ts>.sql
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dir = storage_path('app/backup');
$ts = date('Ymd_His');
$dst = $dir . "/db_legacy_tx_{$ts}.sql";
echo "Output: {$dst}\n";

$outFh = fopen($dst, 'w');
fwrite($outFh, "-- Converted legacy transactions → multi-row INSERT at " . date('c') . "\n");
fwrite($outFh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

$cols = '`id`,`tgl_transaksi`,`account_debet`,`account_kredit`,`reverence_type`,`reverence_id`,`keterangan_transaksi`,`relasi`,`saldo`,`urutan`,`id_user`,`penerima_komisi_id`,`transaction_group`,`created_at`,`updated_at`,`deleted_at`';

// Pre-load akun map (id → kode_akun)
$accountCode = [];
$idToCode = [];
foreach (DB::connection('legacy')->table('accounts')->orderBy('id','desc')->get(['id','kode_akun']) as $a) {
    $k = (string) $a->kode_akun;
    if (! isset($accountCode[$k])) $accountCode[$k] = (int) $a->id;
    $idToCode[(int) $a->id] = $k;
}
$kasId        = $accountCode['1.1.01.01'] ?? null; // 594
$piutangId    = $accountCode['1.1.03.01'] ?? null; // 686
$pasangBaruId = $accountCode['4.1.01.01'] ?? null; // 641
$abodemenId   = $accountCode['4.1.01.02'] ?? null; // 642
$tagihanId    = $accountCode['4.1.01.03'] ?? null; // 643
$dendaId      = $accountCode['4.1.01.04'] ?? null; // 644
$pendapatanNonPas = array_filter([$abodemenId, $tagihanId, $dendaId]);

// User map: legacy user → new user id
$newUsersAll = DB::table('users')->get(['id','name','role']);
$byNameRole = [];
foreach ($newUsersAll as $u) {
    $k = strtolower(trim((string) $u->name)).'|'.strtolower((string) $u->role);
    if (! isset($byNameRole[$k])) $byNameRole[$k] = [];
    $byNameRole[$k][] = (int) $u->id;
}
$legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id','nama','jabatan']);
$userMap = [];
foreach ($legacyUsers as $lu) {
    $role = match ((int) ($lu->jabatan ?? 0)) {
        1, 2, 3, 4, 6, 8 => 'admin',
        5 => 'surveyor',
        7 => 'teknisi',
        default => 'admin',
    };
    $key = strtolower(trim((string) $lu->nama)).'|'.strtolower($role);
    if (isset($byNameRole[$key]) && ! empty($byNameRole[$key])) {
        $userMap[(int) $lu->id] = (int) array_shift($byNameRole[$key]);
    } else {
        $userMap[(int) $lu->id] = null; // tidak ditemukan
    }
}
$fallbackUser = DB::table('users')->where('role','admin')->value('id') ?? 1;

// usage → bill map
echo "Building usage→bill map...\n";
$usages = DB::connection('legacy')->table('usages')
    ->whereNotNull('tgl_pemakaian')
    ->whereRaw('LENGTH(CAST(tgl_pemakaian AS CHAR)) >= 8')
    ->get(['id','tgl_pemakaian','id_instalasi']);

$packageMap = [];
$legacyPkgs = DB::connection('legacy')->table('packages')->get(['id','business_id','kelas']);
$newPkgs = DB::table('installation_packages')->get();
foreach ($legacyPkgs as $lp) {
    $want = "{$lp->kelas} (B{$lp->business_id})";
    foreach ($newPkgs as $np) {
        if (strcasecmp((string) $np->name, $want) === 0) {
            $packageMap[(int) $lp->id] = (int) $np->id;
            break;
        }
    }
}

$newTickets = DB::table('installation_tickets')->get(['id','applicant_name','package_id']);
$ticketKey = [];
foreach ($newTickets as $t) {
    $ticketKey[strtolower(trim((string) $t->applicant_name)).'|'.$t->package_id] = (int) $t->id;
}

$legacyInst = DB::connection('legacy')
    ->table('installations')
    ->join('customers','installations.customer_id','=','customers.id')
    ->get(['installations.id','installations.package_id','customers.nama']);

$instToTicket = [];
foreach ($legacyInst as $li) {
    $pid = $packageMap[(int) $li->package_id] ?? null;
    if (! $pid) continue;
    $k = strtolower(trim((string) $li->nama)).'|'.$pid;
    $tid = $ticketKey[$k] ?? null;
    if ($tid) $instToTicket[(int) $li->id] = $tid;
}

$customerByTicket = [];
foreach (DB::table('customers')->get(['id', 'ticket_id']) as $c) {
    $customerByTicket[(int) $c->ticket_id] = (int) $c->id;
}

$usageToBill = [];
foreach ($usages as $u) {
    try { $dt = \Carbon\Carbon::parse((string) $u->tgl_pemakaian); } catch (\Throwable) { continue; }
    $tid = $instToTicket[(int) $u->id_instalasi] ?? null;
    if (! $tid) continue;
    $cid = $customerByTicket[$tid] ?? null;
    if (! $cid) continue;
    $billId = DB::table('monthly_bills')
        ->where('customer_id', $cid)
        ->where('billing_period_year', (int) $dt->year)
        ->where('billing_period_month', (int) $dt->month)
        ->value('id');
    if ($billId) $usageToBill[(int) $u->id] = (int) $billId;
}
echo "usage→bill: " . count($usageToBill) . "\n";

// ticket_id → payments.id (type=installation_fee)
$ticketToPayment = [];
foreach (DB::table('payments')->where('type', 'installation_fee')->get(['id', 'ticket_id']) as $p) {
    $ticketToPayment[(int) $p->ticket_id] = (int) $p->id;
}
echo "ticket→payment: " . count($ticketToPayment) . "\n";

// bill_id+amount+paid_at_date → bill_payments.id (lookup akurat)
$bpKey = [];
foreach (DB::table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
    if (! $bp->paid_at) continue;
    $day = date('Y-m-d', strtotime((string) $bp->paid_at));
    $amt = number_format((float) $bp->amount_paid, 2, '.', '');
    $bpKey["{$bp->bill_id}|{$day}|{$amt}"] = (int) $bp->id;
}
echo "bill_payments key: " . count($bpKey) . "\n";

// fallback: bill_id → list bp ordered by paid_at (cocokkan yang paling dekat)
$billToBps = [];
foreach (DB::table('bill_payments')->orderBy('paid_at')->get(['id', 'bill_id', 'paid_at']) as $bp) {
    $billToBps[(int) $bp->bill_id][] = ['id' => (int) $bp->id, 'paid_at' => (string) $bp->paid_at];
}

// Write batches
$rowsPerStmt = 50;
$batch = [];
$total = 0;
$wrote = 0;

$flush = function() use (&$batch, &$wrote, $outFh, $cols) {
    if (empty($batch)) return;
    $values = '(' . implode('),(', $batch) . ')';
    fwrite($outFh, "INSERT INTO `transactions` ({$cols}) VALUES {$values};\n");
    $wrote += count($batch);
    $batch = [];
};

$skippedNoDate = 0;
$skippedNoCode = 0;
DB::connection('legacy')->table('transactions')->orderBy('id')->chunkById(2000, function($rows) use (&$batch, &$total, $flush, $kasId, $piutangId, $pendapatanNonPas, $pasangBaruId, $usageToBill, $instToTicket, $customerByTicket, $userMap, $fallbackUser, &$skippedNoDate, &$skippedNoCode, $idToCode, $rowsPerStmt) {
    foreach ($rows as $t) {
        $total++;
        $tgl = trim((string) ($t->tgl_transaksi ?? ''));
        if (strlen($tgl) < 8) { $skippedNoDate++; continue; }
        $debetId = (int) $t->rekening_debit;
        $kreditId = (int) $t->rekening_kredit;
        $uid = (int) ($t->user_id ?? 0);
        $idUser = $userMap[$uid] ?? $fallbackUser;
        if (! $idUser) $idUser = $fallbackUser;

        // klasifikasi
        $revType = 'NULL';
        $revId = 'NULL';
        $usageId = (int) $t->usage_id;
        $instId = (int) $t->installation_id;

        if ($debetId === $kasId && $kreditId === $pasangBaruId && $usageId === 0) {
            $revType = "'payment'";
            // reverence_id = payments.id (type=installation_fee)
            if ($instId > 0 && isset($instToTicket[$instId])) {
                $tid = $instToTicket[$instId];
                if (isset($ticketToPayment[$tid])) $revId = $ticketToPayment[$tid];
            }
        } elseif ($debetId === $kasId && in_array($kreditId, $pendapatanNonPas, true)) {
            $revType = "'monthly_bill'";
            if ($usageId > 0 && isset($usageToBill[$usageId])) $revId = $usageToBill[$usageId];
        } elseif ($debetId === $piutangId && in_array($kreditId, $pendapatanNonPas, true)) {
            $revType = "'overdue_bill'";
            if ($usageId > 0 && isset($usageToBill[$usageId])) $revId = $usageToBill[$usageId];
        } elseif ($debetId === $kasId && $kreditId === $piutangId) {
            $revType = "'bill_payment'";
            // reverence_id = bill_payments.id (lookup akurat bill_id+date+amount, fallback ke paid_at terdekat)
            if ($usageId > 0 && isset($usageToBill[$usageId])) {
                $billId = $usageToBill[$usageId];
                $tglDay = substr((string) $t->tgl_transaksi, 0, 10);
                $amt = number_format((float) $t->total, 2, '.', '');
                if ($tglDay !== '' && isset($bpKey["{$billId}|{$tglDay}|{$amt}"])) {
                    $revId = $bpKey["{$billId}|{$tglDay}|{$amt}"];
                } elseif (isset($billToBps[$billId])) {
                    $targetTs = strtotime($tglDay);
                    $best = null; $bestDiff = PHP_INT_MAX;
                    foreach ($billToBps[$billId] as $cand) {
                        $ts = strtotime(substr($cand['paid_at'], 0, 10));
                        if (! $ts || ! $targetTs) continue;
                        $diff = abs($ts - $targetTs);
                        if ($diff < $bestDiff) { $bestDiff = $diff; $best = $cand['id']; }
                    }
                    if ($best !== null && $bestDiff <= 86400 * 35) $revId = $best;
                }
            }
        }

        $keterangan = addslashes((string) ($t->keterangan ?? ''));
        $relasi = trim((string) ($t->relasi ?? ''));
        if ($relasi === '') $relasi = 'NULL';
        else { $relasi = "'" . addslashes($relasi) . "'"; }

        $saldo = (float) $t->total;
        $urutan = (int) ($t->urutan ?? 0);
        $created = isset($t->created_at) && trim((string) $t->created_at) !== '' && trim((string) $t->created_at) !== '0000-00-00 00:00:00' ? "'" . addslashes((string) $t->created_at) . "'" : 'NULL';
        $updated = isset($t->updated_at) && trim((string) $t->updated_at) !== '' && trim((string) $t->updated_at) !== '0000-00-00 00:00:00' ? "'" . addslashes((string) $t->updated_at) . "'" : 'NULL';
        $tglQ = "'" . addslashes($tgl) . "'";

        // Skip columns: account_debet/kredit via JOIN dengan new.accounts by kode_akun
        $debetCode = $idToCode[$debetId] ?? null;
        $kreditCode = $idToCode[$kreditId] ?? null;
        if (! $debetCode || ! $kreditCode) { $skippedNoCode++; continue; }
        $debet = "'" . addslashes($debetCode) . "'";
        $kredit = "'" . addslashes($kreditCode) . "'";

        $row = "NULL,{$tglQ},{$debet},{$kredit},{$revType}," . ($revId === 'NULL' ? 'NULL' : $revId) . ",'{$keterangan}',{$relasi},{$saldo},{$urutan},{$idUser},NULL,NULL,{$created},{$updated},NULL";
        $batch[] = $row;
        if (count($batch) >= $rowsPerStmt) {
            $flush();
        }
    }
});
$flush();

fwrite($outFh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($outFh);

echo "Total legacy processed: {$total}\n";
echo "Skipped no date: {$skippedNoDate}\n";
echo "Skipped no code: {$skippedNoCode}\n";
echo "Wrote to file: {$wrote}\n";
echo "File size: " . number_format(filesize($dst)) . " bytes\n";