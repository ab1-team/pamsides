<?php

namespace Database\Seeders;

use App\Models\InstallationPackage;
use Illuminate\Database\Seeder;

class InstallationPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'             => 'Paket Reguler',
                'installation_fee' => 1_500_000,
                'monthly_abodemen' => 15_000,
                'late_penalty'     => 10_000,
                'tariff_blocks'    => [
                    ['usage_min_m3' => 0,  'usage_max_m3' => 10,   'price_per_m3' => 1_500],
                    ['usage_min_m3' => 11, 'usage_max_m3' => 20,   'price_per_m3' => 2_000],
                    ['usage_min_m3' => 21, 'usage_max_m3' => null, 'price_per_m3' => 2_500],
                ],
            ],
            [
                'name'             => 'Paket Sosial',
                'installation_fee' => 800_000,
                'monthly_abodemen' => 10_000,
                'late_penalty'     => 5_000,
                'tariff_blocks'    => [
                    ['usage_min_m3' => 0,  'usage_max_m3' => 10,   'price_per_m3' => 1_000],
                    ['usage_min_m3' => 11, 'usage_max_m3' => null, 'price_per_m3' => 1_500],
                ],
            ],
        ];

        foreach ($packages as $data) {
            $blocks = $data['tariff_blocks'];
            unset($data['tariff_blocks']);

            $package = InstallationPackage::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            $package->waterTariffBlocks()->delete();
            $package->waterTariffBlocks()->createMany($blocks);
        }
    }
}
