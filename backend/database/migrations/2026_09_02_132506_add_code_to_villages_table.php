<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `code` di villages (unique).
 *
 * Backfill: ambil dari legacy.villages.kode via mapping (nama+dusun).
 * Format legacy: "001.0001" (kec.desa) atau "005.0001.0001" (kec.desa.dusun).
 *
 * Village baru yg tidak ada di legacy: auto-generate dengan format "999.<urut>"
 * agar tetap unik & tidak bentrok dengan kode legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            // 30 karakter cukup untuk format hierarkis (mis. "005.0001.0001")
            $table->string('code', 30)->nullable()->after('id')->unique();
        });

        // Skip backfill legacy jika env testing atau legacy connection tidak tersedia.
        // Koneksi 'legacy' hanya ada di server produksi (data import dari server SIM-SPPG).
        if (app()->environment('testing') || ! $this->legacyConnectionAvailable()) {
            if (! app()->environment('testing')) {
                DB::statement("SELECT 'Migration add_code_to_villages: backfill skipped (testing/no legacy)' AS info");
            }
            return;
        }

        // Build map: legacy.villages.id -> new.villages.id
        // Match by lower(nama) + lower(dusun)
        $legacy = DB::connection('legacy')->table('villages')->orderBy('id')->get();
        $new    = DB::table('villages')->orderBy('id')->get();

        $matched = 0;
        $unmatched = [];

        foreach ($legacy as $lg) {
            $found = null;
            foreach ($new as $nw) {
                $sameName    = strtolower(trim($nw->village_name)) === strtolower(trim($lg->nama ?? ''));
                $sameHamlet  = strtolower(trim($nw->hamlet_name ?? '')) === strtolower(trim($lg->dusun ?? ''));
                if ($sameName && $sameHamlet) {
                    $found = $nw;
                    break;
                }
            }
            if ($found) {
                DB::table('villages')->where('id', $found->id)->update(['code' => $lg->kode]);
                $matched++;
            } else {
                $unmatched[] = ['legacy_id' => $lg->id, 'kode' => $lg->kode, 'nama' => $lg->nama, 'dusun' => $lg->dusun];
            }
        }

        // Untuk new.villages yang tidak punya padanan di legacy (imigrasi manual/dll),
        // generate kode sintetis "999.NNN" by id ASC.
        $fallbackIdx = 1;
        $new2 = DB::table('villages')->whereNull('code')->orderBy('id')->get();
        foreach ($new2 as $r) {
            $code = '999.'.str_pad((string) $fallbackIdx, 3, '0', STR_PAD_LEFT);
            // Pastikan unik (loop defensif)
            while (DB::table('villages')->where('code', $code)->where('id', '!=', $r->id)->exists()) {
                $fallbackIdx++;
                $code = '999.'.str_pad((string) $fallbackIdx, 3, '0', STR_PAD_LEFT);
            }
            DB::table('villages')->where('id', $r->id)->update(['code' => $code]);
            $fallbackIdx++;
        }

        // Log summary via DB::statement supaya tidak error jika info() tak tersedia
        DB::statement("SELECT 'Migration summary: matched_from_legacy={$matched}, unmatched=".count($unmatched).", fallback_generated=".count($new2)."' AS info");
    }

    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }

    private function legacyConnectionAvailable(): bool
    {
        $cfg = config('database.connections.legacy');
        if (! is_array($cfg)) {
            return false;
        }
        $host = $cfg['host'] ?? null;
        $user = $cfg['username'] ?? null;
        $db   = $cfg['database'] ?? null;
        if (empty($host) || empty($user) || empty($db)) {
            return false;
        }
        try {
            DB::connection('legacy')->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
