<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Auto-assign jabatan_id ke user existing yang namanya match daftar legacy.
 * Idempotent: jalankan ulang aman, hanya update user yang belum punya jabatan_id.
 *
 * TIDAK membuat user baru & TIDAK menghapus user — hanya update jabatan_id.
 */
class LegacyUserJabatanSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping nama legacy (persis sesuai gambar) → jabatan_id
        $mapping = [
            'Iswanto'                  => 1, // Direktur
            'Mardi'                    => 5, // Caters
            'Wanto'                    => 5,
            'Andri'                    => 5,
            'Prapto'                   => 5,
            'Jarwo'                    => 5,
            'Parno'                    => 5,
            'Suryani'                  => 5,
            'Warsono'                  => 5,
            'Puput Wening Ngati, S.IP' => 3, // Bendahara
            'Nurul Nurjannah, A.Md.'   => 8, // Ketua
            'Sarji'                    => 5,
        ];

        $updated = 0;
        $skipped = 0;
        $missing = [];

        foreach ($mapping as $nama => $jabatanId) {
            $user = User::where('name', $nama)->first();

            if (! $user) {
                $missing[] = $nama;
                continue;
            }

            if ($user->jabatan_id === $jabatanId) {
                $skipped++;
                continue;
            }

            $user->jabatan_id = $jabatanId;
            $user->save();
            $updated++;
        }

        $this->command?->info("LegacyUserJabatanSeeder: updated={$updated}, skipped={$skipped}, missing=".count($missing));
        foreach ($missing as $n) {
            $this->command?->warn("  - '{$n}' tidak ditemukan di tabel users");
        }
    }
}
