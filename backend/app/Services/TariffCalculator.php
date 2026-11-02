<?php

namespace App\Services;

use App\Models\WaterTariffBlock;
use Illuminate\Support\Collection;

/**
 * Kalkulator tarif pemakaian air — SATU-SATUNYA sumber kebenaran.
 *
 * ATURAN MAIN
 * -----------
 * Seluruh pemakaian dibebankan memakai harga SATU blok tarif, yaitu blok
 * tempat pemakaian itu berada.
 *
 * Contoh paket "Warga" (blok 1: `1-10 m3` @ 3.500, blok 2: `11-100000 m3` @ 4.000):
 *
 *   5 m3  -> blok 1 ->  5 x 3.500 =  17.500
 *   10 m3 -> blok 1 -> 10 x 3.500 =  35.000
 *   11 m3 -> blok 2 -> 11 x 4.000 =  44.000
 *   19 m3 -> blok 2 -> 19 x 4.000 =  76.000
 *
 * Ini aturan yang sama dengan aplikasi lama (SIM SPPG) yang menagih
 * `usages.nominal` per blok, dan dipakai seluruh tagihan yang sudah
 * tercatat di `monthly_bills`.
 *
 * Kenapa BUKAN rumus progresif (`10 x 3.500 + 1 x 4.000 = 39.000` untuk 11 m3)?
 * Rumus progresif tidak pernah dipakai di produksi. Kalau dipakai, tagihan
 * bulan berjalan jadi berbeda dari tagihan bulan-bulan sebelumnya untuk
 * pemakaian yang sama — pelanggan yang memakai 11 m3 selalu ditagih
 * Rp 44.000 di data yang sudah terbit, bukan Rp 39.000.
 */
class TariffCalculator
{
    /**
     * Biaya pemakaian untuk satu pelanggan pada satu periode.
     *
     * @param  \App\Models\InstallationPackage|int  $package  Paket atau id paket.
     */
    public function charge($package, float $usageM3): float
    {
        if ($usageM3 <= 0) {
            return 0;
        }

        $packageId = is_object($package) ? $package->id : $package;

        $blocks = WaterTariffBlock::where('package_id', $packageId)
            ->orderBy('usage_min_m3')
            ->get();

        if ($blocks->isEmpty()) {
            return 0;
        }

        $block = $this->findBlockFor($blocks, $usageM3);

        if (! $block) {
            return 0;
        }

        return round($usageM3 * (float) $block->price_per_m3);
    }

    /**
     * Blok tarif tempat pemakaian berada.
     *
     * `usage_min_m3` dan `usage_max_m3` sama-sama INKLUSIF, jadi pemakaian
     * tepat di batas blok (mis. 10 m3 pada blok `1-10`) masuk blok itu dan
     * tidak lompat ke blok berikutnya.
     */
    private function findBlockFor(Collection $blocks, float $usageM3): ?WaterTariffBlock
    {
        foreach ($blocks as $block) {
            $min = (float) $block->usage_min_m3;
            $max = $block->usage_max_m3 === null ? null : (float) $block->usage_max_m3;

            if ($usageM3 >= $min && ($max === null || $usageM3 <= $max)) {
                return $block;
            }
        }

        // Pemakaian jatuh di celah antar blok (mis. blok `1-10` lalu
        // `20-...`): pakai blok berikutnya — pemakaian yang tidak tertampung
        // dibebankan ke tarif yang lebih tinggi, bukan dibebaskan.
        foreach ($blocks as $block) {
            if ((float) $block->usage_min_m3 > $usageM3) {
                return $block;
            }
        }

        return null;
    }
}
