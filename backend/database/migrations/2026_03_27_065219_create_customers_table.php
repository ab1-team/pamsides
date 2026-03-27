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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                  ->constrained('installation_tickets')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users');

            $table->string('customer_code')->unique();

            $table->integer('initial_meter_reading');
            $table->string('meter_photo_url')->nullable();

            $table->timestamp('activated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
