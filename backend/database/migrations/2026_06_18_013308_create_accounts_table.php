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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); 
            $table->integer('parent_id');
            $table->integer('lev1')->default(0)->nullable();
            $table->integer('lev2')->default(0)->nullable();
            $table->integer('lev3')->default(0)->nullable();
            $table->integer('lev4')->default(0)->nullable();
            $table->string('kode_akun', 10);
            $table->string('nama_akun', 100)->default('0')->nullable();
            $table->string('jenis_mutasi', 6)->default('0')->nullable();
            $table->date('tgl_nonaktif')->nullable();
            $table->timestamps(); // otomatis membuat created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
