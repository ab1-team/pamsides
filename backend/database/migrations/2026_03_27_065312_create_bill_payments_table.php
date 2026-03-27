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
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                  ->constrained('monthly_bills')
                  ->cascadeOnDelete();

            $table->decimal('amount_paid', 12, 2);

            $table->foreignId('confirmed_by')
                  ->constrained('users');

            $table->timestamp('paid_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
