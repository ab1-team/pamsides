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
        Schema::create('akun_level_3', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id'); // Menghubungkan ke id di akun_level_2
            $table->integer('lev1');
            $table->integer('lev2');
            $table->integer('lev3');
            $table->integer('lev4')->default(0);
            $table->string('kode_akun', 10);
            $table->string('nama_akun', 100);
            $table->integer('posisi')->default(1); 
            $table->string('jenis_mutasi', 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_level_3');
    }
};