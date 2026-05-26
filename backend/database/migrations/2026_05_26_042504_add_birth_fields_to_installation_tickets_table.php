<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'birth_date']);
        });
    }
};
