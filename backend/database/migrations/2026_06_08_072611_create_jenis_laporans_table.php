<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('jenis_laporans', function (Blueprint $table) {
            $table->id(); 
            $table->integer('urut');
            $table->string('nama_laporan', 50);
            $table->string('file', 20);
            $table->char('awal_tahun', 1)->default('0');
            $table->timestamps(); 
        });

        DB::table('jenis_laporans')->insert([
            ['id' => 1, 'urut' => 1, 'nama_laporan' => 'Cover', 'file' => 'cover', 'awal_tahun' => '0'],
            ['id' => 2, 'urut' => 2, 'nama_laporan' => 'Surat Pengantar', 'file' => 'surat_pengantar', 'awal_tahun' => '0'],
            ['id' => 3, 'urut' => 3, 'nama_laporan' => 'Jurnal Transaksi', 'file' => 'jurnal_transaksi', 'awal_tahun' => '0'],
            ['id' => 4, 'urut' => 4, 'nama_laporan' => 'Buku Besar', 'file' => 'buku_besar', 'awal_tahun' => '0'],
            ['id' => 5, 'urut' => 5, 'nama_laporan' => 'Neraca Saldo', 'file' => 'neraca_saldo', 'awal_tahun' => '0'],
            ['id' => 7, 'urut' => 7, 'nama_laporan' => 'Laba / Rugi', 'file' => 'laba_rugi', 'awal_tahun' => '0'],
            ['id' => 8, 'urut' => 8, 'nama_laporan' => 'Laporan Arus Kas', 'file' => 'arus_kas', 'awal_tahun' => '0'],
            ['id' => 6, 'urut' => 6, 'nama_laporan' => 'Neraca', 'file' => 'neraca', 'awal_tahun' => '0'],
            ['id' => 15, 'urut' => 15, 'nama_laporan' => 'Daftar Aset Tak Berwujud', 'file' => 'atb', 'awal_tahun' => '0'],
            ['id' => 9, 'urut' => 9, 'nama_laporan' => 'Laporan Perubahan Modal', 'file' => 'LPM', 'awal_tahun' => '0'],
            ['id' => 10, 'urut' => 10, 'nama_laporan' => 'Catatan Atas Laporan Keuangan', 'file' => 'calkk', 'awal_tahun' => '0'],
            ['id' => 14, 'urut' => 14, 'nama_laporan' => 'Daftar Aset Tetap dan Investasi', 'file' => 'ati', 'awal_tahun' => '0'],
            ['id' => 11, 'urut' => 11, 'nama_laporan' => 'Daftar Pelanggan', 'file' => 'daftar_pelanggan', 'awal_tahun' => '0'],
            ['id' => 16, 'urut' => 16, 'nama_laporan' => 'E-Budgeting', 'file' => 'e_budgeting', 'awal_tahun' => '0'],
            ['id' => 12, 'urut' => 12, 'nama_laporan' => 'Daftar Tagihan Pelanggan', 'file' => 'tagihan_pelanggan', 'awal_tahun' => '0'],
            ['id' => 13, 'urut' => 13, 'nama_laporan' => 'Daftar Piutang Pelanggan', 'file' => 'piutang_pelanggan', 'awal_tahun' => '0'],
            ['id' => 36, 'urut' => 17, 'nama_laporan' => 'Awal Tahun', 'file' => 'tutup_buku', 'awal_tahun' => '0'],
            ['id' => 37, 'urut' => 13, 'nama_laporan' => 'Daftar Utang Komisi SPS', 'file' => 'piutang_komisi', 'awal_tahun' => '0'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_laporans');
    }
};
