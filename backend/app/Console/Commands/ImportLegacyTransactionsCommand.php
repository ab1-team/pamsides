<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyTransactionsCommand extends Command
{
    protected $signature = 'import:transactions
                            {--dry-run : Simulasi}
                            {--force   : Truncate + insert beneran}
                            {--business= : Filter business_id tertentu di DB lama (mis. 5)}
                            {--chunk=1000 : Chunk progress log}
                            {--batch=500 : Insert batch size}
                            {--limit=0 : Batasi jumlah row (0=semua)}';

    protected $description = 'Import legacy transactions → new.transactions (jurnal akuntansi). reverence_type pakai short string (payment/monthly_bill/bill_payment/overdue_bill).';

    public function handle(): int
    {
        $isDryRun = ! $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $batchSize = max(1, (int) $this->option('batch'));
        $limit = (int) $this->option('limit');
        $bizFilter = $this->option('business');

        $this->warn($isDryRun ? 'DRY-RUN' : 'FORCE MODE');
        $this->line('chunk='.$chunkSize.' batch='.$batchSize.' limit='.$limit);
        if ($bizFilter) $this->info("Filter: hanya business_id={$bizFilter} di DB lama.");

        // 1. accounts
        $this->info('Loading accounts...');
        $accountCode = [];
        $codeToId = [];
        foreach (DB::connection('legacy')->table('accounts')->get(['id', 'kode_akun']) as $a) {
            $accountCode[(int) $a->id] = (string) $a->kode_akun;
            $codeToId[(string) $a->kode_akun] = (int) $a->id;
        }
        $this->line('  accounts loaded: '.count($accountCode));

        // ID akun di legacy yang dipakai transaksi (cek legacy:probe-tx-accounts):
        //   594=Kas, 686=Piutang, 641=Pasang Baru, 642=Abodemen, 643=Pemakaian, 644=Denda
        // accounts.id legacy ada duplikat (id 1 + 594 untuk 1.1.01.01, dst) → ambil id yang TERAKHIR
        //   agar sesuai dengan yang dipakai transaksi.
        $accountLast = [];
        foreach (DB::connection('legacy')->table('accounts')->orderBy('id','desc')->get(['id','kode_akun']) as $a) {
            $k = (string) $a->kode_akun;
            if (! isset($accountLast[$k])) $accountLast[$k] = (int) $a->id;
        }
        $kasId        = $accountLast['1.1.01.01'] ?? null; // 594
        $piutangId    = $accountLast['1.1.03.01'] ?? null; // 686
        $pasangBaruId = $accountLast['4.1.01.01'] ?? null; // 641
        $abodemenId   = $accountLast['4.1.01.02'] ?? null; // 642
        $tagihanId    = $accountLast['4.1.01.03'] ?? null; // 643
        $dendaId      = $accountLast['4.1.01.04'] ?? null; // 644
        $pendapatanIds = array_filter([$pasangBaruId, $abodemenId, $tagihanId, $dendaId]);
        // Pendapatan BUKAN pasang baru (untuk klasifikasi monthly_bill vs payment)
        $pendapatanNonPasang = array_filter([$abodemenId, $tagihanId, $dendaId]);

        $this->line("  kas={$kasId} piutang={$piutangId} pendapatan=".implode(',', $pendapatanIds));

        // 2. User map
        $existingMax = DB::table('users')->max('id') ?? 0;
        $legacyUsers = DB::connection('legacy')->table('users')->orderBy('id')->get(['id', 'nama', 'jabatan']);
        $newUsersAll = DB::table('users')->get(['id', 'name', 'role']);
        $byNameRole = [];
        foreach ($newUsersAll as $u) {
            $key = strtolower(trim((string) $u->name)).'|'.strtolower((string) $u->role);
            if (! isset($byNameRole[$key])) $byNameRole[$key][] = (int) $u->id;
        }
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
                $userMap[(int) $lu->id] = $existingMax + (int) $lu->id;
            }
        }
        $fallbackUser = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // 3. usage_id → monthly_bill_id (DB baru)
        $this->info('Building usage→bill map...');
        $usages = DB::connection('legacy')
            ->table('usages')
            ->whereNotNull('tgl_pemakaian')
            ->whereRaw('LENGTH(CAST(tgl_pemakaian AS CHAR)) >= 8')
            ->get(['id', 'tgl_pemakaian', 'id_instalasi', 'customer']);

        $packageMap = [];
        $legacyPkgs = DB::connection('legacy')->table('packages')->get(['id', 'business_id', 'kelas']);
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

        $newTickets = DB::table('installation_tickets')->get(['id', 'applicant_name', 'package_id']);
        $ticketKey = [];
        foreach ($newTickets as $t) {
            $ticketKey[strtolower(trim((string) $t->applicant_name)).'|'.$t->package_id] = (int) $t->id;
        }

        $legacyInst = DB::connection('legacy')
            ->table('installations')
            ->join('customers', 'installations.customer_id', '=', 'customers.id')
            ->get(['installations.id', 'installations.package_id', 'installations.desa', 'customers.nama']);

        $instToTicket = [];
        foreach ($legacyInst as $li) {
            $pid = $packageMap[(int) $li->package_id] ?? null;
            if (! $pid) continue;
            $keyA = strtolower(trim((string) $li->nama)).'|'.$pid;
            $tid = $ticketKey[$keyA] ?? null;
            if ($tid) $instToTicket[(int) $li->id] = $tid;
        }

        $customerByTicket = [];
        foreach (DB::table('customers')->get(['id', 'ticket_id']) as $c) {
            $customerByTicket[(int) $c->ticket_id] = (int) $c->id;
        }

        $usageToBill = [];
        foreach ($usages as $u) {
            $tgl = trim((string) $u->tgl_pemakaian);
            if (strlen($tgl) < 8) continue;
            try {
                $dt = \Carbon\Carbon::parse($tgl);
                $year = (int) $dt->year;
                $month = (int) $dt->month;
            } catch (\Throwable) { continue; }
            $tid = $instToTicket[(int) $u->id_instalasi] ?? null;
            if (! $tid) continue;
            $cid = $customerByTicket[$tid] ?? null;
            if (! $cid) continue;
            $billId = DB::table('monthly_bills')
                ->where('customer_id', $cid)
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->value('id');
            if ($billId) $usageToBill[(int) $u->id] = (int) $billId;
        }
        $this->line('  usage→bill: '.count($usageToBill));

        $legacyInstToCust = [];
        foreach ($legacyInst as $li) {
            $tid = $instToTicket[(int) $li->id] ?? null;
            if ($tid && isset($customerByTicket[$tid])) {
                $legacyInstToCust[(int) $li->id] = $customerByTicket[$tid];
            }
        }

        // ticket_id → payments.id (type=installation_fee, biasanya 1 row per ticket)
        $ticketToPayment = [];
        foreach (DB::table('payments')->where('type', 'installation_fee')->get(['id', 'ticket_id']) as $p) {
            $ticketToPayment[(int) $p->ticket_id] = (int) $p->id;
        }
        $this->line('  ticket→payment (installation_fee): '.count($ticketToPayment));

        // bill_id+amount+paid_at_date → bill_payments.id (lookup akurat saat import transaksi)
        $bpKey = []; // key = "billId|YYYY-MM-DD|amount" → bp.id
        foreach (DB::table('bill_payments')->get(['id', 'bill_id', 'amount_paid', 'paid_at']) as $bp) {
            if (! $bp->paid_at) continue;
            $day = date('Y-m-d', strtotime((string) $bp->paid_at));
            $amt = number_format((float) $bp->amount_paid, 2, '.', '');
            $bpKey["{$bp->bill_id}|{$day}|{$amt}"] = (int) $bp->id;
        }
        $this->line('  bill_payments key (bill|day|amount): '.count($bpKey));

        // fallback: bill_id saja → [bp.id] (dipakai kalau key persis tidak ketemu)
        $billToPayments = [];
        foreach (DB::table('bill_payments')->orderBy('id')->get(['id', 'bill_id', 'paid_at']) as $bp) {
            $billToPayments[(int) $bp->bill_id][] = ['id' => (int) $bp->id, 'paid_at' => (string) $bp->paid_at];
        }

        // 4. Existing new.transactions untuk urutkan id baru mulai MAX+1
        $existingNew = DB::table('transactions')->max('id') ?? 0;
        $nextId = $existingNew + 1;

        // 5. Truncate kalau force — HANYA transactions (bill_payments & payments
        //    tidak di-truncate, sudah dipisah/direstore manual)
        if (! $isDryRun) {
            $this->info('Truncate new.transactions...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transactions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $nextId = 1;
        }

        // 6. Loop legacy transactions
        $total = DB::connection('legacy')->table('transactions')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->count();
        $this->line('  legacy transactions total: '.$total);

        $stats = [
            'created'        => 0,
            'skipped'        => 0,
            'failed'         => 0,
            'orphan_acct'    => 0,
            'type_monthly'   => 0,
            'type_overdue'   => 0,
            'type_billpay'   => 0,
            'type_payment'   => 0,
            'type_other'     => 0,
        ];
        $skipped = [];
        $failed  = [];
        $i = 0;
        $buffer = [];

        DB::connection('legacy')->table('transactions')
            ->when($bizFilter, fn ($q) => $q->where('business_id', $bizFilter))
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$i, &$buffer, &$stats, &$skipped, &$failed, $accountCode, $userMap, $usageToBill, $legacyInstToCust, $fallbackUser, $batchSize, $isDryRun, $chunkSize, $total, $limit, $kasId, $piutangId, $pendapatanIds, $pendapatanNonPasang, $pasangBaruId, &$nextId, $ticketToPayment, $bpKey, $billToPayments) {
            foreach ($rows as $t) {
                if ($limit > 0 && $i >= $limit) return false;
                $i++;
                try {
                    $tgl = trim((string) ($t->tgl_transaksi ?? ''));
                    if (strlen($tgl) < 8) {
                        $stats['skipped']++;
                        continue;
                    }
                                $debetId  = (int) $t->rekening_debit;
                    $kreditId = (int) $t->rekening_kredit;
                    $debet  = $accountCode[$debetId] ?? null;
                    $kredit = $accountCode[$kreditId] ?? null;
                    // Fallback untuk akun parent (id 113 = parent Piutang → 1.1.03.01,
                    //                                  id 114 = parent Cadangan Piutang → 1.1.04.01)
                    // yang dipakai jurnal Penghapusan Piutang tanpa row child di new.
                    if (! $debet) {
                        $debet = $this->resolveParentAccountCode($debetId, $accountCode);
                    }
                    if (! $kredit) {
                        $kredit = $this->resolveParentAccountCode($kreditId, $accountCode);
                    }
                    // STATIC FALLBACK: akun 113/114 parent header tidak ada di tabel legacy accounts,
                    // tapi dipakai jurnal Penghapusan Piutang. Mapping ke child business 5:
                    //   113 → kode 1.1.03.01 (Piutang Usaha child di biz 5 = id 686)
                    //   114 → kode 1.1.04.01 (Cadangan Piutang child di biz 5 = id 605)
                    if (! $debet) {
                        $debet = match ($debetId) {
                            113 => '1.1.03.01',
                            114 => '1.1.04.01',
                            default => null,
                        };
                    }
                    if (! $kredit) {
                        $kredit = match ($kreditId) {
                            113 => '1.1.03.01',
                            114 => '1.1.04.01',
                            default => null,
                        };
                    }
                    if (! $debet || ! $kredit) {
                        $stats['orphan_acct']++;
                        if (count($skipped) < 50) $skipped[] = ['id' => $t->id, 'reason' => 'orphan account', 'debet' => $debetId, 'kredit' => $kreditId];
                        continue;
                    }

                    $uid = (int) ($t->user_id ?? 0);
                    $idUser = $userMap[$uid] ?? null;
                    if (! $idUser || ! DB::table('users')->where('id', $idUser)->exists()) {
                        $idUser = $fallbackUser;
                    }

                    // klasifikasi reverence_type + reverence_id
                    $revType = null;
                    $revId = null;
                    $usageId = (int) $t->usage_id;
                    $instId  = (int) $t->installation_id;
                    $tglDay  = strlen($tgl) >= 10 ? substr($tgl, 0, 10) : null;
                    $saldo = (float) ($t->total ?? 0);
                    $saldoFmt = number_format($saldo, 2, '.', '');

                    if ($debetId === $kasId && $kreditId === $pasangBaruId && $usageId === 0) {
                        // kas → pasang baru: payment (pembayaran instalasi)
                        // reverence_id harus payments.id (type=installation_fee), BUKAN customer.id
                        $revType = 'payment';
                        $stats['type_payment']++;
                        if ($instId > 0 && isset($instToTicket[$instId])) {
                            $ticketId = $instToTicket[$instId];
                            if (isset($ticketToPayment[$ticketId])) {
                                $revId = $ticketToPayment[$ticketId];
                            }
                        }
                    } elseif ($debetId === $kasId && in_array($kreditId, $pendapatanNonPasang, true)) {
                        // kas → pendapatan (abodemen/pemakaian/denda): monthly_bill
                        // reverence_id = monthly_bills.id (tagihan yg dibuat/dikenakan pendapatan)
                        $revType = 'monthly_bill';
                        $stats['type_monthly']++;
                        if ($usageId > 0 && isset($usageToBill[$usageId])) {
                            $revId = $usageToBill[$usageId];
                        }
                    } elseif ($debetId === $piutangId && in_array($kreditId, $pendapatanNonPasang, true)) {
                        // piutang → pendapatan (abodemen/pemakaian/denda): overdue_bill
                        // reverence_id = monthly_bills.id (tunggakan)
                        $revType = 'overdue_bill';
                        $stats['type_overdue']++;
                        if ($usageId > 0 && isset($usageToBill[$usageId])) {
                            $revId = $usageToBill[$usageId];
                        }
                    } elseif ($debetId === $kasId && $kreditId === $piutangId) {
                        // kas → piutang: bill_payment (pelunasan oleh pelanggan)
                        // reverence_id HARUS bill_payments.id, bukan monthly_bills.id
                        $revType = 'bill_payment';
                        $stats['type_billpay']++;
                        if ($usageId > 0 && isset($usageToBill[$usageId])) {
                            $billId = $usageToBill[$usageId];
                            // lookup akurat: bill_id + paid_at date + amount
                            if ($tglDay) {
                                $key = "{$billId}|{$tglDay}|{$saldoFmt}";
                                if (isset($bpKey[$key])) {
                                    $revId = $bpKey[$key];
                                }
                            }
                            // fallback: kalau tidak ketemu persis, cari bp dalam bill_id yang paid_at-nya paling dekat dengan tgl
                            if (! $revId && isset($billToPayments[$billId])) {
                                $best = null;
                                $bestDiff = PHP_INT_MAX;
                                $targetTs = $tglDay ? strtotime($tglDay) : null;
                                foreach ($billToPayments[$billId] as $cand) {
                                    $candTs = strtotime(substr((string) $cand['paid_at'], 0, 10));
                                    if ($candTs === false || $targetTs === null) continue;
                                    $diff = abs($candTs - $targetTs);
                                    if ($diff < $bestDiff) { $bestDiff = $diff; $best = $cand['id']; }
                                }
                                if ($best !== null && $bestDiff <= 86400 * 35) { // max 35 hari beda
                                    $revId = $best;
                                }
                            }
                        }
                    } else {
                        // other: fee kolektor, utang, jurnal umum, dll → null
                        $revType = null;
                        $revId = null;
                        $stats['type_other']++;
                    }

                    $keterangan = (string) ($t->keterangan ?? '');
                    $urutan = (int) ($t->urutan ?? 0);
                    $relasi = trim((string) ($t->relasi ?? ''));
                    if ($relasi === '') $relasi = null;

                    $created = isset($t->created_at) ? (string) $t->created_at : null;
                    $updated = isset($t->updated_at) ? (string) $t->updated_at : null;

                    $row = [
                        'tgl_transaksi'        => $tgl,
                        'account_debet'        => $debet,
                        'account_kredit'       => $kredit,
                        'transaction_group'    => $this->parseTransactionGroup((string) ($t->transaction_id ?? '')),
                        'reverence_type'       => $revType,
                        'reverence_id'         => $revId,
                        'penerima_komisi_id'   => ($debetId === 689) ? $idUser : null,
                        'keterangan_transaksi' => $keterangan !== '' ? $keterangan : null,
                        'relasi'               => $relasi,
                        'saldo'                => $saldo,
                        'urutan'               => $urutan,
                        'id_user'              => $idUser,
                        'created_at'           => $created ?: now(),
                        'updated_at'           => $updated ?: now(),
                    ];
                    if ($saldo <= 0) {
                        $stats['skipped']++;
                        continue;
                    }
                    $buffer[] = $row;

                    if (count($buffer) >= $batchSize) {
                        if (! $isDryRun) {
                            DB::table('transactions')->insert($buffer);
                            $stats['created'] += count($buffer);
                        } else {
                            $stats['created'] += count($buffer);
                        }
                        $buffer = [];
                    }

                    if ($i % $chunkSize === 0) {
                        $pct = round($i / max(1, $total) * 100, 1);
                        $this->line(sprintf('  [%5d/%5d] %5.1f%%  created=%d skipped=%d orphan=%d monthly=%d overdue=%d billpay=%d payment=%d other=%d', $i, $total, $pct, $stats['created'], $stats['skipped'], $stats['orphan_acct'], $stats['type_monthly'], $stats['type_overdue'], $stats['type_billpay'], $stats['type_payment'], $stats['type_other']));
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    if (count($failed) < 50) $failed[] = ['id' => $t->id, 'error' => $e->getMessage()];
                }
            }
            return true;
        });

        if (! empty($buffer)) {
            if (! $isDryRun) {
                DB::table('transactions')->insert($buffer);
                $stats['created'] += count($buffer);
            } else {
                $stats['created'] += count($buffer);
            }
            $buffer = [];
        }

        $this->line('');
        $this->info('Ringkasan:');
        foreach ($stats as $k => $v) $this->line("  $k : $v");
        if (! empty($skipped)) {
            $this->line('');
            $this->warn('Skipped (sample):');
            foreach (array_slice($skipped, 0, 10) as $s) $this->line('  '.json_encode($s));
        }
        if (! empty($failed)) {
            $this->line('');
            $this->error('Failed (sample):');
            foreach (array_slice($failed, 0, 10) as $f) $this->line('  '.json_encode($f));
        }
        $this->line('');
        $this->line('Verifikasi:');
        $this->line('  new.transactions count: '.DB::table('transactions')->count());

        return self::SUCCESS;
    }

    /**
     * Parse legacy transaction_id (varchar) → bigint group identifier.
     * Format: "akun.usage_id.installation_id" (e.g. "594.7658.20419") → kembalikan usage_id*100000+installation_id sebagai hash bigint.
     * "0" atau kosong → null (single-row entry, no group).
     */
    private function parseTransactionGroup(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '0') {
            return null;
        }
        // Format "A.B.C" → A=akun, B=usage_id, C=installation_id
        if (preg_match('/^\d+\.\d+\.\d+$/', $raw)) {
            $parts = explode('.', $raw);
            $usage = (int) $parts[1];
            $inst  = (int) $parts[2];
            if ($usage === 0 && $inst === 0) return null;
            // Pakai hash 64-bit: usage * 1.000.000 + inst (cukup utk range installation_id legacy < 20000)
            return $usage * 1000000 + $inst;
        }
        // Numeric murni → langsung pakai
        if (ctype_digit($raw)) {
            $n = (int) $raw;
            return $n > 0 ? $n : null;
        }
        return null;
    }

    /**
     * Fallback: kalau id akun legacy adalah akun PARENT (header tanpa child rows di DB baru),
     * cari parent_id di legacy → ambil kode_akun dari header child pertama yang punya kode_akun tsb.
     * Digunakan agar jurnal Penghapusan Piutang (d=605 k=113, dimana 113=parent Piutang) tidak hilang.
     */
    private function resolveParentAccountCode(int $legacyId, array $accountCode): ?string
    {
        static $parentCache = [];
        if (isset($parentCache[$legacyId])) {
            return $parentCache[$legacyId];
        }
        $row = DB::connection('legacy')->table('accounts')->where('id', $legacyId)->first();
        if (! $row || ! $row->parent_id) {
            $parentCache[$legacyId] = null;
            return null;
        }
        // Ambil kode_akun dari parent (header) — di legacy, parent_id adalah akun header tanpa kode_akun sendiri.
        // Parent header di legacy punya kode_akun null; grand-parent (root) punya kode_akun.
        // Untuk konsistensi: cari ke atas sampai ketemu yang punya kode_akun.
        $current = $legacyId;
        while ($current) {
            $acc = DB::connection('legacy')->table('accounts')->where('id', $current)->first();
            if (! $acc) break;
            if (! empty($acc->kode_akun)) {
                $parentCache[$legacyId] = (string) $acc->kode_akun;
                return $parentCache[$legacyId];
            }
            $current = (int) ($acc->parent_id ?? 0);
            if ($current === 0) break;
        }
        $parentCache[$legacyId] = null;
        return null;
    }
}
