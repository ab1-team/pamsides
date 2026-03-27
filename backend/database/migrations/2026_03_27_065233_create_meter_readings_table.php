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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->cascadeOnDelete();

            $table->foreignId('recorded_by')
                  ->constrained('users');

            $table->integer('reading_year');
            $table->integer('reading_month');

            $table->integer('meter_value');
            $table->string('photo_url')->nullable();

            $table->timestamp('recorded_at');

            $table->timestamps();

            $table->unique(['customer_id', 'reading_year', 'reading_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
