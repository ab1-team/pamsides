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
        Schema::create('water_tariff_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('installation_packages')->cascadeOnDelete();
            $table->integer('usage_min_m3');
            $table->integer('usage_max_m3')->nullable();
            $table->decimal('price_per_m3', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_tariff_blocks');
    }
};
