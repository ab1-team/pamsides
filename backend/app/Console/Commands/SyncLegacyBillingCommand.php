<?php

namespace App\Console\Commands;

use App\Services\LegacyUsageSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyBillingCommand extends Command
{
    protected $signature = 'legacy:sync-billing
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : business_id legacy yang jadi acuan}
                            {--only=both : readings | bills | both}
                            {--no-penalty=0 : Hitung denda tunggakan seperti app baru}
                            {--keep-orphan=0 : Hanya perbaiki baris yang sudah ada, jangan buat baru}';

    protected $description = 'Selaraskan meter_readings + monthly_bills dengan legacy usages (relasi lewat kode instalasi, bukan nama)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }
        $biz = (int) $this->option('business');
        $only = (string) $this->option('only');
        $applyPenalty = ! $this->option('no-penalty');
        $keepOrphan = (bool) $this->option('keep-orphan');

        $this->info('=== Selaraskan meter_readings + monthly_bills dengan legacy usages ===');
        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: data akan diubah.');
        $this->info("business_id={$biz} | only={$only} | denda=".($applyPenalty ? 'hitung' : 'jangan').' | orphan='.($keepOrphan ? 'biarkan' : 'lengkapi'));
        $this->line('');

        $N = DB::connection();

        $this->info('Mengumpulkan legacy usages...');
        $src = LegacyUsageSource::collect($biz);
        $readings = $src['readings'];
        $bills = LegacyUsageSource::buildBills($readings, $applyPenalty);

        $this->line(sprintf(
            '  usage legacy biz%s : %d baris unik (pelanggan+bulan)',
            $biz, $src['total']
        ));
        $this->line(sprintf('  tanpa pasangan customer : %d', $src['noCustomer']));
        $this->line(sprintf('  tanggal tidak valid      : %d', $src['noDate']));
        if ($src['duplicates']) {
            $this->line(sprintf('  baris duplikat (dipilih yg meter-nya valid): %d', count($src['duplicates'])));
        }
        $this->line('');

        $total = ['mr_new' => 0, 'mr_update' => 0, 'mr_same' => 0, 'mb_new' => 0, 'mb_update' => 0, 'mb_same' => 0];

        // ---------- meter_readings ----------
        if ($only === 'readings' || $only === 'both') {
            $this->info('--- meter_readings ---');
            $existing = [];
            foreach ($N->table('meter_readings')->get(['id', 'customer_id', 'reading_year', 'reading_month', 'meter_value', 'recorded_at']) as $r) {
                $existing[$r->customer_id.'|'.$r->reading_year.'|'.$r->reading_month] = $r;
            }
            $this->line('  DB baru sekarang : '.count($existing));

            $staffMap = self::staffMap($biz);
            $fallbackUser = (int) ($N->table('users')->whereIn('role', ['teknisi', 'admin'])->orderBy('id')->value('id') ?? 1);

            $inserts = [];
            $updates = [];
            $swapped = 0;
            $wrongCustomer = 0;

            foreach ($readings as $key => $r) {
                $cur = $existing[$key] ?? null;
                if ($cur === null) {
                    if ($keepOrphan) {
                        continue;
                    }
                    $inserts[] = [
                        'customer_id' => $r['customer_id'],
                        'recorded_by' => $staffMap[$r['cater']] ?? $fallbackUser,
                        'reading_year' => $r['year'],
                        'reading_month' => $r['month'],
                        'meter_value' => $r['reading_end'],
                        'photo_url' => null,
                        'recorded_at' => $r['recorded_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $total['mr_new']++;
                    continue;
                }

                $curValue = (float) $cur->meter_value;
                $expValue = (float) $r['reading_end'];
                if (abs($curValue - $expValue) < 0.001) {
                    $total['mr_same']++;
                    continue;
                }

                // Bedakan "nilai tertukar dari customer lain" vs "salah input".
                if (self::valueExistsElsewhere($N, $key, $expValue)) {
                    $swapped++;
                }
                $updates[] = ['id' => (int) $cur->id, 'key' => $key, 'meter_value' => $expValue, 'recorded_at' => $r['recorded_at']];
                $total['mr_update']++;
            }

            // Baris DB baru yang tidak ada padanannya di legacy
            $orphanRows = 0;
            foreach ($existing as $key => $cur) {
                if (! isset($readings[$key])) {
                    $orphanRows++;
                }
            }

            $this->line('  perlu dibuat   : '.$total['mr_new']);
            $this->line('  perlu diupdate : '.$total['mr_update'].' (di antaranya '.$swapped.' nilainya tertukar dari pelanggan lain)');
            $this->line('  sudah benar    : '.$total['mr_same']);
            $this->line('  baris DB baru tanpa pasangan legacy : '.$orphanRows);
            if ($swapped > 0) {
                $this->line('');
                $this->warn("  >> {$swapped} baris terdeteksi TER tukar. Contoh:");
                foreach (array_slice($updates, 0, 6) as $u) {
                    $parts = explode('|', $u['key']);
                    $cur = $existing[$u['key']];
                    echo sprintf(
                        "     cust=%s %d-%02d  meter_value: %s -> %s\n",
                        $parts[0], $parts[1], $parts[2], $cur->meter_value, $u['meter_value']
                    );
                }
                $this->line('');

                if (! $dry) {
                    // Update per baris: CASE batching rawan gagal diam-diam
                    // bila salah satu nilai tidak serializable.
                    foreach (array_chunk($updates, 300) as $chunk) {
                        foreach ($chunk as $u) {
                            $N->table('meter_readings')->where('id', $u['id'])
                                ->update(['meter_value' => $u['meter_value'], 'updated_at' => now()]);
                        }
                    }
                    $this->info('  meter_readings di-update: '.count($updates));
                }
            } elseif (! $dry && $updates) {
                foreach (array_chunk($updates, 400) as $chunk) {
                    foreach ($chunk as $u) {
                        $N->table('meter_readings')->where('id', $u['id'])
                            ->update(['meter_value' => $u['meter_value'], 'updated_at' => now()]);
                    }
                }
                $this->info('  meter_readings di-update: '.count($updates));
            }

            if ($inserts) {
                if (! $dry) {
                    foreach (array_chunk($inserts, 500) as $chunk) {
                        $N->table('meter_readings')->insert($chunk);
                    }
                }
                $this->info('  meter_readings dibuat: '.count($inserts));
            }
            $this->line('');
        }

        // ---------- monthly_bills ----------
        if ($only === 'bills' || $only === 'both') {
            $this->info('--- monthly_bills ---');
            $existingB = [];
            foreach ($N->table('monthly_bills')->get([
                'id', 'customer_id', 'billing_period_year', 'billing_period_month',
                'meter_reading_start', 'meter_reading_end', 'usage_m3',
                'usage_charge', 'abodemen', 'penalty_amount', 'total_amount', 'status', 'due_date',
            ]) as $r) {
                $existingB[$r->customer_id.'|'.$r->billing_period_year.'|'.$r->billing_period_month] = $r;
            }
            $this->line('  DB baru sekarang : '.count($existingB));

            $billInserts = [];
            $billUpdates = [];
            $statDiff = ['status' => 0, 'total' => 0, 'abodemen' => 0, 'usage_charge' => 0, 'm_start' => 0, 'm_end' => 0, 'usage_m3' => 0, 'due_date' => 0];
            $samples = [];

            foreach ($bills as $key => $b) {
                $cur = $existingB[$key] ?? null;
                if ($cur === null) {
                    if ($keepOrphan) {
                        continue;
                    }
                    $billInserts[] = $b + ['created_at' => now(), 'updated_at' => now()];
                    $total['mb_new']++;
                    continue;
                }

                $set = [];
                foreach ([
                    'meter_reading_start' => 'm_start',
                    'meter_reading_end' => 'm_end',
                    'usage_m3' => 'usage_m3',
                    'usage_charge' => 'usage_charge',
                    'abodemen' => 'abodemen',
                    'total_amount' => 'total',
                    'status' => 'status',
                    'due_date' => 'due_date',
                ] as $col => $tag) {
                    $want = $b[$col];
                    $have = $cur->$col;
                    $equal = is_string($want) || $want === null
                        ? (string) $have === (string) $want
                        : abs((float) $have - (float) $want) < 0.005;
                    if (! $equal) {
                        $set[$col] = $want;
                        $statDiff[$tag]++;
                        if (count($samples) < 8 && $col === 'status') {
                            $samples[] = "cust={$b['customer_id']} {$b['billing_period_year']}-".str_pad((string) $b['billing_period_month'], 2, '0', STR_PAD_LEFT)." status: {$have} -> {$want}";
                        }
                        if (count($samples) < 16 && $col !== 'status') {
                            $samples[] = "cust={$b['customer_id']} {$b['billing_period_year']}-".str_pad((string) $b['billing_period_month'], 2, '0', STR_PAD_LEFT)." $col: {$have} -> {$want}";
                        }
                    }
                }
                // penalty: ikut legacy? recalc hanya jika beda & tidak ada payment.
                if (abs((float) $cur->penalty_amount - (float) $b['penalty_amount']) >= 0.005) {
                    $hasPayment = $N->table('bill_payments')->where('bill_id', $cur->id)->exists();
                    if (! $hasPayment) {
                        $set['penalty_amount'] = $b['penalty_amount'];
                        $statDiff['total']++;
                    }
                }

                if ($set) {
                    $billUpdates[] = ['id' => (int) $cur->id, 'set' => $set];
                    $total['mb_update']++;
                } else {
                    $total['mb_same']++;
                }
            }

            $billOrphan = 0;
            foreach ($existingB as $key => $cur) {
                if (! isset($bills[$key])) {
                    $billOrphan++;
                }
            }

            $this->line('  perlu dibuat   : '.$total['mb_new']);
            $this->line('  perlu diupdate : '.$total['mb_update']);
            foreach ($statDiff as $k => $v) {
                if ($v) {
                    $this->line("    $k berubah : $v");
                }
            }
            $this->line('  sudah benar    : '.$total['mb_same']);
            $this->line('  baris DB baru tanpa pasangan legacy : '.$billOrphan);
            foreach ($samples as $s) {
                $this->line('    '.$s);
            }
            $this->line('');

            if (! $dry) {
                foreach ($billUpdates as $u) {
                    $N->table('monthly_bills')->where('id', $u['id'])->update($u['set'] + ['updated_at' => now()]);
                }
                if ($billUpdates) {
                    $this->info('  monthly_bills di-update: '.count($billUpdates));
                }
                if ($billInserts) {
                    foreach (array_chunk($billInserts, 400) as $chunk) {
                        $N->table('monthly_bills')->insert($chunk);
                    }
                    $this->info('  monthly_bills dibuat: '.count($billInserts));
                }
            }
        }

        $this->line('');
        if ($dry) {
            $this->warn('DRY-RUN selesai. Jalankan dengan --force untuk menerapkan.');

            return self::SUCCESS;
        }
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    /** Apakah nilai ini milik meter_readings pelanggan lain di periode sama? */
    private static function valueExistsElsewhere($N, string $key, float $value): bool
    {
        static $byPeriod = null;
        if ($byPeriod === null) {
            $byPeriod = [];
            foreach ($N->table('meter_readings')->get(['reading_year', 'reading_month', 'meter_value']) as $r) {
                $byPeriod[$r->reading_year.'-'.$r->reading_month][] = (float) $r->meter_value;
            }
        }
        $parts = explode('|', $key);

        return in_array($value, $byPeriod[$parts[1].'-'.$parts[2]] ?? [], true);
    }

    /** legacy users.id → users.id DB baru (untuk recorded_by) */
    private static function staffMap(int $biz): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        $newStaff = [];
        foreach ($N->table('users')->whereIn('role', ['admin', 'teknisi', 'surveyor'])->get(['id', 'name']) as $u) {
            $newStaff[strtolower(trim((string) $u->name))] = (int) $u->id;
        }

        $map = [];
        foreach ($L->table('users')->where('business_id', $biz)->get(['id', 'nama']) as $lu) {
            $key = strtolower(trim((string) $lu->nama));
            if ($key !== '' && isset($newStaff[$key])) {
                $map[(int) $lu->id] = $newStaff[$key];
            }
        }

        return $map;
    }
}