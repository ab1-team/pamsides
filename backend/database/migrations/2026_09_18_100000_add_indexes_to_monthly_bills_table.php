<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index komposit tambahan untuk mempercepat pagination + sorting
     * default endpoint GET /monthly-bills (orderBy year DESC, month DESC).
     *
     * Index yang sudah ada di monthly_bills (lihat migration
     * 2026_04_07_080633_add_indexes_to_tables):
     *   - idx_bills_customer_period (customer_id, year, month)
     *   - idx_bills_status (status)
     *
     * Index yang ditambahkan di sini:
     *   - mb_status_period        : status + year + month  (filter status + sort default)
     *   - mb_period               : year + month            (sort default tanpa filter)
     *   - users_name              : bantu LIKE prefix pada customer search
     */
    public function up(): void
    {
        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->index(
                ['status', 'billing_period_year', 'billing_period_month'],
                'mb_status_period_idx'
            );

            $table->index(
                ['billing_period_year', 'billing_period_month'],
                'mb_period_idx'
            );
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name', 'idx_users_name');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->dropIndex('mb_status_period_idx');
            $table->dropIndex('mb_period_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
        });
    }
};
