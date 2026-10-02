<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index komposit untuk mempercepat query finance di DashboardController:
 *
 *   - transactions(account_kredit, tgl_transaksi) — untuk filter LIKE '4.%'
 *     pada kolom pendapatan bulanan & chart per bulan.
 *   - transactions(account_debet, tgl_transaksi)  — untuk filter LIKE '5.%'
 *     pada kolom beban bulanan & chart per bulan.
 *
 * Index existing (lihat migration 2026_06_22_020213_create_transactions_table):
 *   - transactions_tgl_transaksi_index
 *   - transactions_account_debet_index
 *   - transactions_account_kredit_index
 *
 * Dengan whereBetween(tgl_transaksi, [...]) + WHERE account_kredit LIKE '4.%',
 * InnoDB bisa pakai composite index (account_kredit, tgl_transaksi) untuk
 * range scan jauh lebih kecil. Sebelumnya hanya ada index tunggal di tiap kolom
 * sehingga MySQL pilih satu index saja dan scan sisanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['account_kredit', 'tgl_transaksi'],
                'trx_kredit_tgl_idx'
            );

            $table->index(
                ['account_debet', 'tgl_transaksi'],
                'trx_debet_tgl_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('trx_kredit_tgl_idx');
            $table->dropIndex('trx_debet_tgl_idx');
        });
    }
};
