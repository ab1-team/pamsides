<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('peraturan_desa', 100)->nullable()->after('domain');
            $table->string('sk_kemenkumham', 100)->nullable()->after('peraturan_desa');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['peraturan_desa', 'sk_kemenkumham']);
        });
    }
};