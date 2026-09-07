<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyTransactionsCommand extends Command
{
    protected $signature = 'import:transactions
                            {--dry-run : Simulasi}
                            {--force   : Insert beneran}
                            {--truncate : Truncate new.transactions dulu}
                            {--business= : Filter business_id (mis. 5)}
                            {--chunk=5000 : Rows per fetch batch}
                            {--batch=2000 : Rows per insert batch}
                            {--limit=0 : Batasi jumlah row (0=semua)}';

    protected $description = 'Import legacy transactions → new.transactions (bulk).';

    public function handle(): int
    {
        $isDryRun = ! $this->option('force');
        $chunk = max(100, (int) $this->option('chunk'));
        $batch = max(100, (int) $this->option('batch'));
        $limit = (int) $this->option('limit');
        $biz = $this->option('business');

        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        $this->line("chunk=$chunk batch=$batch limit=$limit");
        if ($biz) $this->info("Filter business_id=$biz");

        // ============ 1. Cache semua lookup di memory ============
        $this->info('Loading maps...');

        // accountId → kodeAkun
        $accCode = [];
        foreach (DB::connection('legacy')->table('accounts')->get(['id', 'kode_akun']) as $a) {
            $accCode[(int) $a->id] = (string) ($a->kode_akun ?? '');
        }
        // Tambahkan fallback untuk id parent header yang dipakai jurnal
        $accCode[113] = '1.1.03.01'; // parent Piutang
        $accCode[114] = '1.1.04.01'; // parent Cadangan Piutang

        $kasId = 594;          // 1.1.01.01
        $piutangId = 686;      // 1.1.03.01
        $pasangBaruId = 641;   // 4.1.01.01
        $pendapatanNonPasang = [642, 643, 644]; // abodemen/pemakaian/denda
        $feeKolektorId = 689;  // 5.1.02.04
        $utangSpsId = 685;     // 2.1.02.02
        $cadanganPiutangId = 605; // 1.1.04.01
        $inventarisId = 612;   // 1.2.01.04

        // userLegacyId → userNewId (by name+role)
        $newUsersByNameRole = [];
        foreach (DB::table('users')->get(['id', 'name', 'role']) as $u) {
            $k = strtolower(trim((string) $u->name)) . '|' . strtolower((string) $u->role);
            if (! isset($newUsersByNameRole[$k])) $newUsersByNameRole[$k] = [];
            $newUsersByNameRole[$k][] = (int) $u->id;
        }
        $userMap = [];
        foreach (DB::connection('legacy')->table('users')->get(['id', 'nama', 'jabatan']) as $lu) {
            $role = match ((int) ($lu->jabatan ?? 0)) {
                1, 2, 3, 4, 6, 8 => 'admin',
                5 => 'teknisi',
                7 => 'teknisi',
                default => 'admin',
            };
            $k = strtolower(trim((string) $lu->nama)) . '|' . strtolower($role);
            if (isset($newUsersByNameRole[$k]) && ! empty($newUsersByNameRole[$k])) {
                $userMap[(int) $lu->id] = (int) array_shift($newUsersByNameRole[$k]);
            } else {
                $userMap[(int) $lu->id] = null; // resolved later
            }
        }
        $fallbackUser = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // usageLegacyId → billNewId  (via instalasi legacy → ticket new → customer new → bill by year/month)
        $this->info('Building usage→bill map...');

        // package legacy → package new (plain name fallback)
        $newPkgs = DB::table('installation_packages')->get();
        $pkgMap = [];
        foreach (DB::connection('legacy')->table('packages')->get(['id', 'business_id', 'kelas']) as $lp) {
            $want = $lp->kelas . ' (B' . $lp->business_id . ')';
            $matched = null;
            foreach ($newPkgs as $np) {
                if (strcasecmp((string) $np->name, $want) === 0) { $matched = (int) $np->id; break; }
            }
            if ($matched === null) {
                foreach ($newPkgs as $np) {
                    if (strcasecmp(trim((string) $np->name), trim((string) $lp->kelas)) === 0) { $matched = (int) $np->id; break; }
                }
            }
            if ($matched !== null) $pkgMap[(int) $lp->id] = $matched;
        }

        // tickets new: name|package → [ticketId...]
        $ticketKey = [];
        foreach (DB::table('installation_tickets')->get(['id', 'applicant_name', 'package_id']) as $t) {
            $k = strtolower(trim((string) $t->applicant_name)) . '|' . $t->package_id;
            $ticketKey[$k][] = (int) $t->id;
        }
        // customer by ticket
        $custByTicket = [];
        foreach (DB::table('customers')->get(['id', 'ticket_id']) as $c) {
            $custByTicket[(int) $c->ticket_id] = (int) $c->id;
        }
        // instalasi legacy → ticket new (pilih ticket yang punya customer)
        $instToTicket = [];
        foreach (DB::connection('legacy')->table('installations')->join('customers', 'installations.customer_id', '=', 'customers.id')->get(['installations.id', 'installations.package_id', 'customers.nama']) as $li) {
            $pid = $pkgMap[(int) $li->package_id] ?? null;
            if (! $pid) continue;
            $keyA = strtolower(trim((string) $li->nama)) . '|' . $pid;
            $candidates = $ticketKey[$keyA] ?? [];
            if (! $candidates) continue;
            $picked = null;
            foreach ($candidates as $cid) {
                if (isset($custByTicket[(int) $cid])) { $picked = (int) $cid; break; }
            }
            if ($picked === null) $picked = (int) $candidates[0];
            $instToTicket[(int) $li->id] = $picked;
        }
        $this->line('  inst→ticket: ' . count($instToTicket));

        // bill cache: customerId|year|month → billId
        $billKey = [];
        foreach (DB::table('monthly_bills')->get(['id', 'customer_id', 'billing_period_year', 'billing_period_month']) as $b) {
            $billKey[(int) $b->customer_id . '|' . (int) $b->billing_period_year . '|' . (int) $b->billing_period_month] = (int) $b->id;
        }

        // bill_payments cache: billId → [{id, paid_at_day, amount_str}]
        $bpByBill = [];
        foreach (DB::table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
            if (! $bp->paid_at) continue;
            $day = date('Y-m-d', strtotime((string) $bp->paid_at));
            $amt = number_format((float) $bp->amount_paid, 2, '.', '');
            $bpByBill[(int) $bp->bill_id][] = ['id' => (int) $bp->id, 'day' => $day, 'amt' => $amt];
        }

        // payments cache: ticket → payment id (installation_fee)
        $payByTicket = [];
        foreach (DB::table('payments')->where('type', 'installation_fee')->get(['id', 'ticket_id']) as $p) {
            $payByTicket[(int) $p->ticket_id] = (int) $p->id;
        }

        // usage → bill
        $usageToBill = [];
        foreach (DB::connection('legacy')->table('usages')->whereNotNull('tgl_pemakaian')->get(['id', 'tgl_pemakaian', 'id_instalasi']) as $u) {
            $tgl = trim((string) $u->tgl_pemakaian);
            if (strlen($tgl) < 8) continue;
            $ts = strtotime($tgl);
            if ($ts === false) continue;
            $y = (int) date('Y', $ts);
            $m = (int) date('n', $ts);
            $tid = $instToTicket[(int) $u->id_instalasi] ?? null;
            if (! $tid) continue;
            $cid = $custByTicket[$tid] ?? null;
            if (! $cid) continue;
            $billId = $billKey[$cid . '|' . $y . '|' . $m] ?? null;
            if ($billId) $usageToBill[(int) $u->id] = $billId;
        }
        $this->line('  usage→bill: ' . count($usageToBill));

        // ============ 2. Truncate kalau diminta ============
        if (! $isDryRun && $this->option('truncate')) {
            $this->info('Truncate new.transactions...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transactions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ============ 3. Stream legacy rows & bulk insert ============
        $total = DB::connection('legacy')->table('transactions')
            ->when($biz, fn ($q) => $q->where('business_id', $biz))
            ->count();
        $this->line('  legacy total: ' . $total);

        $stats = ['created' => 0, 'skipped' => 0, 'orphan' => 0, 'zero' => 0,
                  'monthly' => 0, 'overdue' => 0, 'billpay' => 0, 'payment' => 0, 'other' => 0];
        $i = 0;
        $buffer = [];
        $lastId = 0;
        $maxRetries = 8;

        retry:
        $attempt = 0;
        while (true) {
            try {
                $cursor = DB::connection('legacy')->table('transactions')
                    ->when($biz, fn ($q) => $q->where('business_id', $biz))
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->select(['id', 'tgl_transaksi', 'rekening_debit', 'rekening_kredit', 'user_id', 'usage_id', 'installation_id', 'total', 'transaction_id', 'relasi', 'keterangan', 'urutan', 'created_at', 'updated_at'])
                    ->cursor();

                foreach ($cursor as $t) {
                    $lastId = (int) $t->id;
                    if ($limit > 0 && $i >= $limit) break 2;
                    $i++;

                    $tgl = trim((string) ($t->tgl_transaksi ?? ''));
                    if (strlen($tgl) < 8) { $stats['skipped']++; continue; }

                    $saldo = (float) ($t->total ?? 0);
                    if ($saldo <= 0) { $stats['zero']++; continue; }

                    $dId = (int) $t->rekening_debit;
                    $kId = (int) $t->rekening_kredit;
                    $debet = $accCode[$dId] ?? null;
                    $kredit = $accCode[$kId] ?? null;
                    if (! $debet || ! $kredit) {
                        $stats['orphan']++;
                        continue;
                    }

                    $uid = (int) ($t->user_id ?? 0);
                    $idUser = $userMap[$uid] ?? $fallbackUser;

                    $usageId = (int) $t->usage_id;
                    $instId  = (int) $t->installation_id;
                    $tglDay  = substr($tgl, 0, 10);
                    $saldoFmt = number_format($saldo, 2, '.', '');

                    // klasifikasi reverence
                    $revType = null;
                    $revId = null;
                    $penerimaKomisi = null;

                    if ($dId === $kasId && $kId === $pasangBaruId && $usageId === 0) {
                        // bayar instalasi
                        $revType = 'payment';
                        $stats['payment']++;
                        if ($instId > 0 && isset($instToTicket[$instId])) {
                            $revId = $payByTicket[$instToTicket[$instId]] ?? null;
                        }
                    } elseif ($dId === $kasId && in_array($kId, $pendapatanNonPasang, true)) {
                        // kas → pendapatan (lunas bayar bulan ini)
                        $revType = 'monthly_bill';
                        $stats['monthly']++;
                        $revId = $usageToBill[$usageId] ?? null;
                    } elseif ($dId === $piutangId && in_array($kId, $pendapatanNonPasang, true)) {
                        // piutang → pendapatan (tagihan diakui / tunggakan)
                        $revType = 'overdue_bill';
                        $stats['overdue']++;
                        $revId = $usageToBill[$usageId] ?? null;
                    } elseif ($dId === $kasId && $kId === $piutangId) {
                        // kas → piutang (pelunasan)
                        $revType = 'bill_payment';
                        $stats['billpay']++;
                        $billId = $usageToBill[$usageId] ?? null;
                        if ($billId && isset($bpByBill[$billId])) {
                            $found = null;
                            foreach ($bpByBill[$billId] as $cand) {
                                if ($cand['day'] === $tglDay && $cand['amt'] === $saldoFmt) { $found = $cand['id']; break; }
                            }
                            if ($found === null) {
                                // fallback: cari paid_at paling dekat dalam 35 hari
                                $best = null; $bestDiff = PHP_INT_MAX;
                                $targetTs = strtotime($tglDay);
                                foreach ($bpByBill[$billId] as $cand) {
                                    $cts = strtotime($cand['day']);
                                    if ($cts === false) continue;
                                    $diff = abs($cts - $targetTs);
                                    if ($diff < $bestDiff) { $bestDiff = $diff; $best = $cand['id']; }
                                }
                                if ($best !== null && $bestDiff <= 86400 * 35) $found = $best;
                            }
                            $revId = $found;
                        }
                    } elseif ($dId === $feeKolektorId && $kId === $utangSpsId) {
                        // komisi SPS — simpan tanpa reverence morph tapi dgn penerima_komisi_id
                        $revType = null;
                        $revId = null;
                        $penerimaKomisi = $idUser;
                        $stats['other']++;
                    } else {
                        // jurnal umum, penghapusan piutang, dll — tetap simpan
                        $revType = null;
                        $revId = null;
                        $stats['other']++;
                    }

                    $keterangan = (string) ($t->keterangan ?? '');
                    $relasi = trim((string) ($t->relasi ?? ''));
                    if ($relasi === '') $relasi = null;
                    $created = $t->created_at ?: now();
                    $updated = $t->updated_at ?: now();

                    $buffer[] = [
                        'tgl_transaksi'        => $tgl,
                        'account_debet'        => $debet,
                        'account_kredit'       => $kredit,
                        'transaction_group'    => $this->parseTransactionGroup((string) ($t->transaction_id ?? '')),
                        'reverence_type'       => $revType,
                        'reverence_id'         => $revId,
                        'penerima_komisi_id'   => $penerimaKomisi,
                        'keterangan_transaksi' => $keterangan !== '' ? $keterangan : null,
                        'relasi'               => $relasi,
                        'saldo'                => $saldo,
                        'urutan'               => (int) ($t->urutan ?? 0),
                        'id_user'              => $idUser,
                        'created_at'           => $created,
                        'updated_at'           => $updated,
                    ];

                    if (count($buffer) >= $batch) {
                        if (! $isDryRun) {
                            DB::table('transactions')->insert($buffer);
                            $stats['created'] += count($buffer);
                        } else {
                            $stats['created'] += count($buffer);
                        }
                        $buffer = [];
                    }

                    if ($i % 5000 === 0) {
                        $pct = round($i / max(1, $total) * 100, 1);
                        $this->line(sprintf('  [%5d/%5d] %5.1f%%  created=%d zero=%d orphan=%d monthly=%d overdue=%d billpay=%d payment=%d other=%d', $i, $total, $pct, $stats['created'], $stats['zero'], $stats['orphan'], $stats['monthly'], $stats['overdue'], $stats['billpay'], $stats['payment'], $stats['other']));
                    }
                }
                break; // selesai normal
            } catch (\Throwable $e) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    $this->error('Max retries: ' . $e->getMessage());
                    break;
                }
                $wait = min(60, 5 * $attempt);
                $this->warn("Legacy putus (try {$attempt}/{$maxRetries}): " . substr($e->getMessage(), 0, 80) . " — sleep {$wait}s, resume after id={$lastId}");
                DB::purge('legacy');
                sleep($wait);
            }
        }

        // Sisa buffer
        if (! empty($buffer)) {
            if (! $isDryRun) DB::table('transactions')->insert($buffer);
            $stats['created'] += count($buffer);
            $buffer = [];
        }

        $this->line('');
        $this->info('Selesai:');
        foreach ($stats as $k => $v) $this->line("  $k = $v");
        $this->line('  total in new.transactions: ' . DB::table('transactions')->count());

        return self::SUCCESS;
    }

    private function parseTransactionGroup(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '0') return null;
        if (preg_match('/^\d+\.\d+\.\d+$/', $raw)) {
            $p = explode('.', $raw);
            $u = (int) $p[1]; $i = (int) $p[2];
            if ($u === 0 && $i === 0) return null;
            return $u * 1000000 + $i;
        }
        if (ctype_digit($raw)) {
            $n = (int) $raw;
            return $n > 0 ? $n : null;
        }
        return null;
    }
}
