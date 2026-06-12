<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('sub_laporans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_laporan', 100);
            $table->string('file', 20);
            $table->string('file_kab', 20)->default('0');
            $table->integer('urut')->default(0)->nullable();
            $table->integer('id_lap')->default(0)->nullable();
            $table->timestamps(); 
        });

        DB::table('sub_laporans')->insert([
            ['id' => 5, 'nama_laporan' => 'Daftar Piutang Detail', 'file' => 'SMPN', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 7, 'nama_laporan' => 'Daftar Piutang Per Kecamatan', 'file' => 'SMPN', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 1, 'nama_laporan' => 'Daftar Pelanggan Aktif', 'file' => 'DRP', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 2, 'nama_laporan' => 'Daftar Pelanggan Diblokir', 'file' => 'DRPL', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 3, 'nama_laporan' => 'Daftar Pelanggan Dicabut', 'file' => 'DRT', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 4, 'nama_laporan' => 'Daftar Pemasangan Baru', 'file' => 'DRPY', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 8, 'nama_laporan' => 'Daftar Tagihan Detail', 'file' => 'KBP', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 9, 'nama_laporan' => 'Daftar Tagihan Per Desa', 'file' => 'PCPP', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 10, 'nama_laporan' => 'Daftar Tagihan Per Kecamatan', 'file' => 'SMPN', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
            ['id' => 6, 'nama_laporan' => 'Daftar Piutang Per Desa', 'file' => 'SMPN', 'file_kab' => '0', 'urut' => 0, 'id_lap' => 0],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_laporans');
    }
};
