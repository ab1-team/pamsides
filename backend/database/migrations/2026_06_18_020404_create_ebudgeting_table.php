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
        Schema::create('ebudgeting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id'); // Menghubungkan ke id di tabel accounts
            $table->year('tahun');
            $table->integer('bulan');
            $table->decimal('jumlah', 15, 2)->default(0); // Menggunakan decimal agar presisi untuk angka besar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebudgeting');
    }
};
