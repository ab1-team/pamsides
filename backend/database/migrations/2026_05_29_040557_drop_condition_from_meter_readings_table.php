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
        Schema::table('meter_readings', function (Blueprint $table) {
            // Menghapus kolom condition dari tabel meter_readings
            if (Schema::hasColumn('meter_readings', 'condition')) {
                $table->dropColumn('condition');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            // Mengembalikan kolom jika rollback dilakukan
            $table->string('condition')->nullable();
        });
    }
};
