<?php

namespace Tests\Unit;

use App\Services\BillingService;
use App\Models\InstallationPackage;
use App\Models\WaterTariffBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BillingService();
    }

    private function createPackageWithBlocks(): InstallationPackage
    {
        $package = InstallationPackage::create([
            'name'             => 'Paket Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty'     => 10_000,
        ]);

        WaterTariffBlock::insert([
            ['package_id' => $package->id, 'usage_min_m3' => 0,  'usage_max_m3' => 10,   'price_per_m3' => 1_500, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $package->id, 'usage_min_m3' => 10, 'usage_max_m3' => 20,   'price_per_m3' => 2_000, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $package->id, 'usage_min_m3' => 20, 'usage_max_m3' => null, 'price_per_m3' => 2_500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return $package;
    }

    #[Test]
    public function kalkulasi_tagihan_blok_pertama(): void
    {
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 5);

        $this->assertEquals(7_500, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_blok_kedua(): void
    {
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 15);

        $this->assertEquals(30_000, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_blok_ketiga(): void
    {
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 25);

        $this->assertEquals(62_500, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_tepat_di_batas_blok(): void
    {
        // 10 m³ → masuk blok 0-10 → 10 × 1.500 = 15.000
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 10);

        $this->assertEquals(15_000, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_desimal(): void
    {
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 10.5);

        $this->assertEquals(21_000, $result);
    }
}
