<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('installation_tickets', 'gender')) {
                $table->enum('gender', ['male', 'female'])->nullable()->after('nik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
