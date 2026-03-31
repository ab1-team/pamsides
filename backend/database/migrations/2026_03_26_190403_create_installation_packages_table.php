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
        Schema::create('installation_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('installation_fee', 12, 2);
            $table->decimal('monthly_abodemen', 12, 2);
            $table->decimal('late_penalty', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_packages');
    }
};
