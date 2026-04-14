<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->index('status', 'idx_tickets_status');
            $table->index('nik', 'idx_tickets_nik');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('customer_code', 'idx_customers_code');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(['customer_id', 'reading_year', 'reading_month'], 'idx_meter_customer_year_month');
        });

        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->index(['customer_id', 'billing_period_year', 'billing_period_month'], 'idx_bills_customer_period');
            $table->index('status', 'idx_bills_status');
        });

        Schema::table('water_tariff_blocks', function (Blueprint $table) {
            $table->index('package_id', 'idx_tariff_package');
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_status');
            $table->dropIndex('idx_tickets_nik');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_code');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('idx_meter_customer_year_month');
        });

        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->dropIndex('idx_bills_customer_period');
            $table->dropIndex('idx_bills_status');
        });

        Schema::table('water_tariff_blocks', function (Blueprint $table) {
            $table->dropIndex('idx_tariff_package');
        });
    }
};
