<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_bills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->integer('billing_period_year');
            $table->integer('billing_period_month');

            $table->integer('meter_reading_start');
            $table->integer('meter_reading_end');
            $table->integer('usage_m3');

            $table->decimal('usage_charge', 12, 2);
            $table->decimal('abodemen', 12, 2);
            $table->decimal('penalty_amount', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2);

            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');

            $table->date('due_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_bills');
    }
};
