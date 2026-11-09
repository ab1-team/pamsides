<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom penanda segmen tagihan saat ada reset/ganti meteran.
     *
     * - `normal` : tagihan biasa (tanpa reset).
     * - `lama`   : tagihan penutup meter lama (hanya biaya pemakaian,
     *              tanpa abodemen/denda karena meter sudah dihentikan).
     * - `baru`   : tagihan meter baru (tagihan normal: pemakaian + abodemen + denda).
     *
     * Tidak ada unique constraint di (customer, period) sehingga dua tagihan
     * untuk periode yang sama (lama & baru) tetap boleh ada.
     */
    public function up(): void
    {
        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->foreignId('meter_reset_id')
                  ->nullable()
                  ->after('customer_id')
                  ->constrained('meter_resets')
                  ->nullOnDelete();

            $table->enum('meter_segment', ['normal', 'lama', 'baru'])
                  ->default('normal')
                  ->after('meter_reset_id');

            $table->index(
                ['customer_id', 'billing_period_year', 'billing_period_month', 'meter_segment'],
                'mb_customer_segment_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->dropIndex('mb_customer_segment_idx');
            $table->dropConstrainedForeignId('meter_reset_id');
            $table->dropColumn('meter_segment');
        });
    }
};
