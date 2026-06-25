<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_transaksi');
            $table->string('account_debet', 10);
            $table->string('account_kredit', 10);
            $table->bigInteger('transaction_group')->nullable();
            $table->string('reverence_type', 255)->nullable();
            $table->unsignedBigInteger('reverence_id')->nullable();
            $table->text('keterangan_transaksi')->nullable();
            $table->string('relasi', 255)->nullable();
            $table->decimal('saldo', 15, 2);
            $table->integer('urutan')->nullable();
            $table->foreignId('id_user')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            // Index untuk pencarian umum
            $table->index('tgl_transaksi');
            $table->index('account_debet');
            $table->index('account_kredit');
            $table->index('transaction_group');
            $table->index(['reverence_type', 'reverence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
