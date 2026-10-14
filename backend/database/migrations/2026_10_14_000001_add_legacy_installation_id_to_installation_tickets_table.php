<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan identitas instalasi LEGACY (`installations.id` di DB lama) di tiket baru.
 *
 * Kenapa perlu:
 *   Tabel `installation_tickets` sebelumnya tidak menyimpan identitas instalasi
 *   lama. Importer pelanggan (`import:customers`) harus menebak tiket baru dengan
 *   heuristik nama/desa/paket — dan heuristik itu KEHILANGAN pelanggan yang
 *   namanya sama (mis. 3 "Sunarto" di desa berbeda dipetakan ke 1 tiket).
 *
 *   Dengan `legacy_installation_id` sebagai kunci utama idempotensi + tautan pasti
 *   tiket↔instalasi legacy, setiap instalasi aktif menghasilkan tepat satu tiket
 *   dan satu baris pelanggan.
 *
 * Nullable: tiket NON-migrasi (dibuat lewat form aplikasi baru) memang tidak
 * punya pasangan legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_installation_id')
                ->nullable()
                ->after('id');
            $table->index('legacy_installation_id', 'idx_tickets_legacy_installation_id');
        });
    }

    public function down(): void
    {
        Schema::table('installation_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_legacy_installation_id');
            $table->dropColumn('legacy_installation_id');
        });
    }
};
