<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `installation_tickets` MODIFY COLUMN `status` ENUM('draft', 'pending', 'surveyed', 'unpaid', 'processing', 'completed', 'suspended', 'terminated') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `installation_tickets` MODIFY COLUMN `status` ENUM('pending', 'surveyed', 'unpaid', 'processing', 'completed', 'suspended', 'terminated') DEFAULT 'pending'");
    }
};
