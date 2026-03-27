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
        Schema::create('installation_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')
                  ->constrained('installation_packages')
                  ->cascadeOnDelete();

            $table->string('applicant_name');
            $table->string('nik', 20);
            $table->text('address');

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->enum('status', [
                'pending',
                'surveyed',
                'unpaid',
                'processing',
                'completed'
            ])->default('pending');

            $table->foreignId('created_by')
                  ->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_tickets');
    }
};
