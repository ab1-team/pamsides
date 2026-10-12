<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hitung ulang `penalty_amount` SAJA, tanpa menyentuh status pembayaran.
 *
 * Kenapa command ini terpisah dari `legacy:sync-billing`:
 *
 * `legacy:sync-billing` menyamakan monthly_bills dengan `usages` legacy,
 * termasuk kolom `status`. Tapi status di legacy itu STALE — ada 195 tagihan
 * yang di lokal masih `unpaid` padahal sudah dibayar (legacy belum update),
 * dan 8 tagihan yang sudah lunas+pembayaran asli tapi legacy masih bilang
 * UNPAID. Menjalankan sync akan membalikkan pembayaran yang sah.
 *
 * Yang benar-benar salah hanya `penalty_amount`: kolom itu dihitung lokal
 * (tidak pernah ada di legacy), jadi tidak punya masalah staleness.
 *
 * Aturan: tagihan periode N kena `late_penalty` paket kalau tagihan N-1
 * berstatus `unpaid`. Sama dengan `BillingService::calculatePenalty()` yang
 * dipakai alur input meter harian.
 *
 * Command ini idempotent dan hanya menyentuh baris `unpaid`.
 */
class RecalcPenaltyCommand extends Command
{
    protected $signature = 'billing:recalc-penalty
                            {--dry-run : Simulasi, tidak ada perubahan}
                            {--force   : Terapkan perubahan}
                            {--business=5 : Hanya pelanggan yang ada di legacy business ini}
                            {--customer= : Batasi satu customer_id}';

    protected $description = 'Hitung ulang penalty_amount (hanya tagihan unpaid, status tidak diubah)';

    private const BACKUP_TABLE = 'monthly_bills_penalty_backup';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $this->option('force')) {
            $this->error('Wajib pakai --dry-run atau --force');

            return self::FAILURE;
        }

        $biz = (int) $this->option('business');
        $onlyCustomer = $this->option('customer') ? (int) $this->option('customer') : null;

        $this->warn($dry ? 'MODE DRY-RUN: tidak ada perubahan.' : 'MODE FORCE: penalty_amount akan ditulis.');
        $this->info("Kolom yang disentuh: penalty_amount, total_amount. Status TIDAK diubah.");

        $N = DB::connection();

        // late_penalty per package_id
        $penaltyByPkg = [];
        foreach ($N->table('installation_packages')->get(['id', 'late_penalty']) as $p) {
            $penaltyByPkg[(int) $p->id] = (float) $p->late_penalty;
        }

        // package_id per customer
        $pkgByCustomer = [];
        foreach ($N->table('customers')
            ->join('installation_tickets', 'installation_tickets.id', '=', 'customers.ticket_id')
            ->get(['customers.id', 'installation_tickets.package_id']) as $r) {
            $pkgByCustomer[(int) $r->id] = (int) $r->package_id;
        }

        // Batasi ke pelanggan yang ada di legacy business tersebut, supaya
        // pelanggan lokal yang bukan hasil migrasi tidak ikut berubah.
        $scope = $this->legacyCustomerIds($biz, $onlyCustomer);

        $rows = $N->table('monthly_bills')
            ->whereNull('deleted_at')
            ->whereIn('customer_id', $scope)
            ->orderBy('customer_id')
            ->orderBy('billing_period_year')
            ->orderBy('billing_period_month')
            ->get([
                'id', 'customer_id', 'billing_period_year', 'billing_period_month',
                'usage_charge', 'abodemen', 'penalty_amount', 'total_amount', 'status',
            ]);

        $this->info('Tagihan dalam cakupan: '.$rows->count());

        // Kelompokkan per pelanggan, urut kronologis.
        $byCustomer = [];
        foreach ($rows as $r) {
            $byCustomer[(int) $r->customer_id][] = $r;
        }

        $updates = [];
        $skippedPaid = 0;
        $touchedCustomers = [];

        foreach ($byCustomer as $customerId => $bills) {
            $latePenalty = $penaltyByPkg[$pkgByCustomer[$customerId] ?? 0] ?? 0.0;
            $changedForCustomer = false;

            foreach ($bills as $i => $bill) {
                $prev = $i > 0 ? $bills[$i - 1] : null;

                // Aturan N-1: denda kalau tagihan sebelumnya masih menunggak.
                $want = ($prev && $prev->status === 'unpaid') ? $latePenalty : 0.0;

                if ($bill->status !== 'unpaid') {
                    // Jangan sentuh tagihan lunas — pembayarannya sudah sah
                    // dan tercatat di bill_payments, meski statusnya beda
                    // dengan legacy yang stale.
                    if (abs((float) $bill->penalty_amount - $want) >= 0.005) {
                        $skippedPaid++;
                    }
                    continue;
                }

                if (abs((float) $bill->penalty_amount - $want) < 0.005) {
                    continue;
                }

                $newTotal = round((float) $bill->usage_charge + (float) $bill->abodemen + $want, 2);

                $updates[] = [
                    'id' => (int) $bill->id,
                    'customer_id' => $customerId,
                    'periode' => $bill->billing_period_year.'-'.str_pad((string) $bill->billing_period_month, 2, '0', STR_PAD_LEFT),
                    'denda_lama' => (float) $bill->penalty_amount,
                    'denda_baru' => $want,
                    'total_lama' => (float) $bill->total_amount,
                    'total_baru' => $newTotal,
                ];
                $changedForCustomer = true;
            }

            if ($changedForCustomer) {
                $touchedCustomers[$customerId] = true;
            }
        }

        $delta = 0.0;
        foreach ($updates as $u) {
            $delta += $u['total_baru'] - $u['total_lama'];
        }

        $this->line('');
        $this->info('Tagihan yang perlu diperbarui : '.count($updates));
        $this->info('Pelanggan terdampak         : '.count($touchedCustomers));
        $this->line('  Total tagihan naik/turun    : '.number_format($delta, 0, ',', '.'));
        $this->line('  Denda pada tagihan LUNAS tidak disentuh (dicewati) : '.$skippedPaid);

        $this->line('');
        $this->info('Contoh 15 perubahan pertama:');
        $this->line(sprintf(
            '  %-10s %-9s %12s -> %-12s %14s -> %s',
            'customer', 'periode', 'denda', 'denda_baru', 'total', 'total_baru'
        ));
        foreach (array_slice($updates, 0, 15) as $u) {
            $this->line(sprintf(
                '  %-10d %-9s %12s -> %-12s %14s -> %s',
                $u['customer_id'],
                $u['periode'],
                number_format($u['denda_lama'], 0, ',', '.'),
                number_format($u['denda_baru'], 0, ',', '.'),
                number_format($u['total_lama'], 0, ',', '.'),
                number_format($u['total_baru'], 0, ',', '.')
            ));
        }

        if ($dry) {
            $this->line('');
            $this->warn('DRY-RUN selesai. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        // Backup dulu — kolom asli, supaya bisa dikembalikan.
        $this->line('');
        $this->info('Mencadangkan '.count($updates).' baris ke `'.self::BACKUP_TABLE.'` ...');
        $this->makeBackup($updates);

        $changed = 0;
        foreach ($updates as $u) {
            $N->table('monthly_bills')->where('id', $u['id'])->update([
                'penalty_amount' => $u['denda_baru'],
                'total_amount' => $u['total_baru'],
                'updated_at' => now(),
            ]);
            $changed++;
        }

        $this->info("Perbarui: {$changed} baris.");
        $this->info('Status pembayaran tidak diubah sama sekali.');

        return self::SUCCESS;
    }

    /**
     * Daftar customer_id lokal yang punya pasangan instalasi di legacy
     * business tersebut. Pelanggan yang tidak ada di legacy tidak ikut
     * disentuh — aturan dendanya murni aturan app baru.
     */
    private function legacyCustomerIds(int $biz, ?int $onlyCustomer): array
    {
        $L = DB::connection('legacy');

        $codeByInst = $L->table('installations')
            ->where('business_id', $biz)
            ->pluck('kode_instalasi', 'id')
            ->all();

        $codes = [];
        foreach ($codeByInst as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $codes[$code] = true;
            }
        }

        $q = DB::connection()->table('customers');
        if ($onlyCustomer) {
            $q->where('id', $onlyCustomer);
        }

        $ids = [];
        foreach ($q->get(['id', 'customer_code']) as $c) {
            if (isset($codes[trim((string) $c->customer_code)])) {
                $ids[] = (int) $c->id;
            }
        }

        return $ids;
    }

    private function makeBackup(array $updates): void
    {
        $N = DB::connection();

        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            $N->statement('CREATE TABLE '.self::BACKUP_TABLE.' (
                id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NOT NULL,
                billing_period_year INT NOT NULL,
                billing_period_month INT NOT NULL,
                penalty_amount DECIMAL(12,2) NOT NULL,
                total_amount DECIMAL(12,2) NOT NULL,
                status VARCHAR(20) NOT NULL,
                backed_up_at TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            )');
        }

        $ids = array_column($updates, 'id');
        $existing = $N->table(self::BACKUP_TABLE)->whereIn('id', $ids)->pluck('id')->all();

        $insert = [];
        foreach ($N->table('monthly_bills')->whereIn('id', $ids)->get() as $b) {
            if (in_array((int) $b->id, array_map('intval', $existing), true)) {
                continue; // jangan timpa backup lama
            }
            $insert[] = [
                'id' => (int) $b->id,
                'customer_id' => (int) $b->customer_id,
                'billing_period_year' => (int) $b->billing_period_year,
                'billing_period_month' => (int) $b->billing_period_month,
                'penalty_amount' => $b->penalty_amount,
                'total_amount' => $b->total_amount,
                'status' => $b->status,
                'backed_up_at' => now(),
            ];
        }

        if ($insert) {
            foreach (array_chunk($insert, 500) as $chunk) {
                $N->table(self::BACKUP_TABLE)->insert($chunk);
            }
        }
    }
}