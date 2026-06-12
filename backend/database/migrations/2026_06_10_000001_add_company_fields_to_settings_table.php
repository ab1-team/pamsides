<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('nama', 150)->nullable()->after('value');
            $table->text('alamat')->nullable()->after('nama');
            $table->string('email', 150)->nullable()->after('alamat');
            $table->string('telepon', 30)->nullable()->after('email');
            $table->string('domain')->nullable()->after('telepon');
            $table->boolean('status_pembayaran')->default(0)->after('domain');
            $table->unsignedTinyInteger('batas_tagihan')->default(10)->after('status_pembayaran');
            $table->unsignedTinyInteger('toleransi_tunggakan')->default(0)->after('batas_tagihan');
            $table->string('logo')->nullable()->after('toleransi_tunggakan');
            $table->text('pesan_tagihan')->nullable()->after('logo');
            $table->text('pesan_pembayaran')->nullable()->after('pesan_tagihan');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'nama',
                'alamat',
                'email',
                'telepon',
                'domain',
                'status_pembayaran',
                'batas_tagihan',
                'toleransi_tunggakan',
                'logo',
                'pesan_tagihan',
                'pesan_pembayaran',
            ]);
        });
    }
};
