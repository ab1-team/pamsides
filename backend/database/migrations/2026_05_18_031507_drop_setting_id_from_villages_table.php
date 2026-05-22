<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('villages', function (Blueprint $table) {

            // hapus kolom hanya kalau ada
            if (Schema::hasColumn('villages', 'setting_id')) {
                $table->dropColumn('setting_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->foreignId('setting_id')
                  ->nullable()
                  ->constrained('settings')
                  ->cascadeOnDelete();
        });
    }
};
