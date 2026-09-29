<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('installation_tickets', 'rt')) {
                $table->string('rt', 10)->nullable()->after('nik');
            }
            if (! Schema::hasColumn('installation_tickets', 'rw')) {
                $table->string('rw', 10)->nullable()->after('rt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('installation_tickets', 'rt')) {
                $table->dropColumn('rt');
            }
            if (Schema::hasColumn('installation_tickets', 'rw')) {
                $table->dropColumn('rw');
            }
        });
    }
};
