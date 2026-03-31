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
        Schema::create('survey_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                  ->constrained('installation_tickets')
                  ->cascadeOnDelete();

            $table->foreignId('surveyor_id')
                  ->constrained('users');

            $table->integer('distance_to_pipe_m');
            $table->text('material_notes')->nullable();
            $table->string('photo_url')->nullable();

            $table->timestamp('surveyed_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_results');
    }
};
