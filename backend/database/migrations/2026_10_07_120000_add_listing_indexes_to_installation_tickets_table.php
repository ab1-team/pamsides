<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk halaman /app/data-pelanggan (CustomerController::index).
 *
 * Dua index lama (idx_tickets_status, idx_tickets_nik) tidak punya kolom
 * created_at, padahal daftar selalu diurutkan `created_at DESC, id DESC`
 * bersama paginasi offset — hasilnya filesort seluruh tabel tiap kali user
 * pindah halaman.
 *
 * Catatan: `it_created_idx` (created_at) dan `it_status_created_idx`
 * (status, created_at) sudah ada di database pengembangan, tapi dibuat manual
 * dan tidak punya migration. Keduanya tidak dicoba dihapus di sini supaya
 * `migrate` di server yang sudah terlanjur memakainya tidak merusak apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        // (created_at, id) melayani daftar tanpa filter. Menyertakan `id`
        // penting: sort memakai dua kolom, dan index yang hanya created_at
        // membuat MySQL tetap filesort saat offset sudah besar.
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'idx_tickets_listing');
        });

        // (status, created_at, id) untuk daftar yang disaring status
        // (mis. hanya pelanggan draft hasil form Tambah).
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->index(['status', 'created_at', 'id'], 'idx_tickets_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_listing');
            $table->dropIndex('idx_tickets_status_created');
        });
    }
};