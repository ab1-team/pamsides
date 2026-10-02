<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field CALK ke tabel settings.
     *
     * - calk (JSON): konfigurasi persentase bagian & laba ditahan.
     *   Struktur:
     *   {
     *     "peraturan_desa": "01 TAHUN 2022",
     *     "D": {
     *       "1": { "d": { "1": 25, "2": 25, "3": 50 } },  // persentase (%)
     *       "2": { "a": 0, "b": 0, "c": 0 }                // laba ditahan (nominal)
     *     }
     *   }
     *   Key 1.d.1 = bantuan rumah tangga
     *   Key 1.d.2 = pengembangan kapasitas
     *   Key 1.d.3 = pelatihan masyarakat
     *   Key 2.a   = peningkatan modal DBM
     *   Key 2.b   = penambahan investasi usaha
     *   Key 2.c   = pendirian unit usaha
     *
     * - calk_point_a (longText): narasi kustom WYSIWYG Point A "Gambaran Umum".
     *   Bisa memuat HTML sehingga admin bisa menata narasi CALK sendiri.
     *
     * Model konsep sidbm (app/Models/Calk.php + tabel `calk` JSON di kecamatan).
     * Diadaptasi ke pamsides-v2 dengan cara: konfigurasi tersimpan di tabel settings
     * (single-tenant) sedangkan catatan tambahan per periode tetap di tabel `calks`.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('calk')->nullable()->after('sk_kemenkumham');
            $table->longText('calk_point_a')->nullable()->after('calk');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['calk', 'calk_point_a']);
        });
    }
};