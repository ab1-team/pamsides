<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->decimal('meter_value', 10, 2)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('initial_meter_reading', 10, 2)->change();
        });

        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->decimal('meter_reading_start', 10, 2)->change();
            $table->decimal('meter_reading_end', 10, 2)->change();
            $table->decimal('usage_m3', 10, 2)->change();
        });

        Schema::table('water_tariff_blocks', function (Blueprint $table) {
            $table->decimal('usage_min_m3', 10, 2)->change();
            $table->decimal('usage_max_m3', 10, 2)->nullable()->change();
        });

        Schema::table('survey_results', function (Blueprint $table) {
            $table->decimal('distance_to_pipe_m', 10, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->integer('meter_value')->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->integer('initial_meter_reading')->change();
        });

        Schema::table('monthly_bills', function (Blueprint $table) {
            $table->integer('meter_reading_start')->change();
            $table->integer('meter_reading_end')->change();
            $table->integer('usage_m3')->change();
        });

        Schema::table('water_tariff_blocks', function (Blueprint $table) {
            $table->integer('usage_min_m3')->change();
            $table->integer('usage_max_m3')->nullable()->change();
        });

        Schema::table('survey_results', function (Blueprint $table) {
            $table->integer('distance_to_pipe_m')->change();
        });
    }
};
