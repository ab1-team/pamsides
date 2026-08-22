<?php

namespace App\Console\Commands;

use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateOverdueTransactions extends Command
{
    protected $signature = 'billing:generate-overdue-transactions
                            {--tanggal= : Tanggal acuan (Y-m-d); default: hari ini}
                            {--force : Hapus jurnal tunggakan lama untuk bill yang sama sebelum insert}';

    protected $description = 'Generate jurnal piutang untuk tagihan menunggak (otomatis jam 00:01)';

    public function handle()
    {
        $today = $this->option('tanggal') ?: now()->toDateString();
        $today = date('Y-m-d', strtotime($today));
        $force = (bool) $this->option('force');

        $toleransi = (int) (Setting::first()?->toleransi_tunggakan ?? 0);
        $thresholdDate = date('Y-m-d', strtotime("$today -$toleransi days"));

        $overdueBills = MonthlyBill::where('status', 'unpaid')
            ->where('due_date', '<', $thresholdDate)
            ->with('customer')
            ->get();

        if ($overdueBills->isEmpty()) {
            $this->info('Tidak ada tagihan menunggak.');
            $this->storeNotification('success', 'Tidak ada tagihan menunggak yang perlu diproses.', 0);
            return self::SUCCESS;
        }

        $systemUser = User::where('role', 'admin')->first();
        if (!$systemUser) {
            $this->error('User admin tidak ditemukan.');
            $this->storeNotification('error', 'Generate gagal: user admin tidak ditemukan. Sistem akan mencoba ulang otomatis.', 0);
            return self::FAILURE;
        }

        $processed = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($overdueBills as $bill) {
                $existing = Transaction::where('reverence_type', 'overdue_bill')
                    ->where('reverence_id', $bill->id);

                if ($existing->exists() && ! $force) {
                    $skipped++;
                    continue;
                }

                if ($force) {
                    $existing->delete();
                }

                $relasi = $bill->customer?->customer_code ?? 'Bill #' . $bill->id;
                $abodemen = (float) ($bill->abodemen ?? 0);
                $usageCharge = (float) ($bill->usage_charge ?? 0);

                $tglTransaksi = $today;

                if ($abodemen > 0) {
                    $trx = Transaction::create([
                        'tgl_transaksi'        => $tglTransaksi,
                        'account_debet'        => '1.1.03.01',
                        'account_kredit'       => '4.1.01.02',
                        'transaction_group'    => null,
                        'reverence_type'       => 'overdue_bill',
                        'reverence_id'         => $bill->id,
                        'keterangan_transaksi' => 'Tunggakan Abodemen - ' . $relasi . ' (' . $this->periodLabel($bill) . ')',
                        'relasi'               => $relasi,
                        'saldo'                => $abodemen,
                        'id_user'              => $systemUser->id,
                    ]);
                    $trx->update(['urutan' => $trx->id]);
                }

                if ($usageCharge > 0) {
                    $trx = Transaction::create([
                        'tgl_transaksi'        => $tglTransaksi,
                        'account_debet'        => '1.1.03.01',
                        'account_kredit'       => '4.1.01.03',
                        'transaction_group'    => null,
                        'reverence_type'       => 'overdue_bill',
                        'reverence_id'         => $bill->id,
                        'keterangan_transaksi' => 'Tunggakan Pemakaian - ' . $relasi . ' (' . $this->periodLabel($bill) . ')',
                        'relasi'               => $relasi,
                        'saldo'                => $usageCharge,
                        'id_user'              => $systemUser->id,
                    ]);
                    $trx->update(['urutan' => $trx->id]);
                }

                $processed++;
            }

            DB::commit();

            $this->info("Berhasil memproses {$processed} tagihan menunggak (skip {$skipped}).");
            $this->storeNotification(
                'success',
                "Generate tagihan menunggak berhasil! {$processed} tagihan diproses ke jurnal umum (skip {$skipped}).",
                $processed
            );
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GenerateOverdueTransactions failed', ['error' => $e->getMessage()]);
            $this->error('Gagal: ' . $e->getMessage());
            $this->storeNotification('error', 'Generate tagihan menunggak gagal. Sistem akan mencoba ulang otomatis besok.', 0);
            return self::FAILURE;
        }
    }

    private function periodLabel($bill)
    {
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        return ($months[$bill->billing_period_month] ?? '') . ' ' . $bill->billing_period_year;
    }

    private function storeNotification(string $type, string $message, int $count)
    {
        Cache::put('overdue_gen_notification', [
            'type'      => $type,
            'message'   => $message,
            'count'     => $count,
            'timestamp' => now()->toISOString(),
        ], now()->addDays(7));
    }
}