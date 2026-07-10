<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->date('tgl_beli');
            $table->string('unit')->nullable();
            $table->string('harset')->nullable();
            $table->integer('umur_ekonomis')->nullable();
            $table->string('jenis')->nullable();
            $table->string('kategori')->nullable();
            $table->string('status')->nullable();
            $table->date('tgl_validasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};