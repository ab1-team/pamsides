<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE installation_tickets MODIFY nik VARCHAR(20) NULL');
    }

    public function down(): void
    {
        // Kembalikan ke NOT NULL — isi NULL dulu dengan string kosong.
        DB::statement("UPDATE installation_tickets SET nik = '' WHERE nik IS NULL");
        DB::statement('ALTER TABLE installation_tickets MODIFY nik VARCHAR(20) NOT NULL');
    }
};
