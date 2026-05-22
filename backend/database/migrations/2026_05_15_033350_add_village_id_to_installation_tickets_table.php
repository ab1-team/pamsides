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
        Schema::table('installation_tickets', function (Blueprint $table) {

            $table->foreignId('village_id')
                  ->nullable()
                  ->after('address')
                  ->constrained('villages');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {

            $table->dropForeign(['village_id']);
            $table->dropColumn('village_id');

        });
    }
};
