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

        $this->assertEquals(25_000, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_blok_ketiga(): void
    {
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 25);

        $this->assertEquals(47_500, $result);
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

        $this->assertEquals(16_000, $result);
    }

    /**
     * Paket dengan penulisan rentang INKLUSIF — format yang dipakai data
     * produksi (paket "Warga": `1-10 m3` @ 3.500 lalu `11-100000 m3` @ 4.000).
     *
     * Ini yang menyebabkan tagihan Rp 500 terlalu mahal di versi lama:
     * `1-10` dihitung jadi 9 m3 (10-1), sehingga 1 m3 terakhir terlewat dan
     * masuk blok 4.000 yang lebih mahal.
     */
    private function createPackageWithInclusiveBlocks(): InstallationPackage
    {
        $package = InstallationPackage::create([
            'name'             => 'Paket Rentang Inklusif',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 10_000,
            'late_penalty'     => 10_000,
        ]);

        WaterTariffBlock::insert([
            ['package_id' => $package->id, 'usage_min_m3' => 1,  'usage_max_m3' => 10,      'price_per_m3' => 3_500, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $package->id, 'usage_min_m3' => 11, 'usage_max_m3' => 100_000, 'price_per_m3' => 4_000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        return $package;
    }

    #[Test]
    public function rentang_inklusif_tepat_di_batas_blok_pertama(): void
    {
        // 1-10 m3 = 10 m3, semuanya @ 3.500 → 35.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 10);

        $this->assertEquals(35_000, $result);
    }

    #[Test]
    public function rentang_inklusif_satu_m3_di_bawah_batas_blok_pertama(): void
    {
        // 9 m3 → semuanya masih di blok pertama → 31.500
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 9);

        $this->assertEquals(31_500, $result);
    }

    #[Test]
    public function rentang_inklusif_satu_m3_di_atas_batas_blok_pertama(): void
    {
        // 11 m3 → 10 x 3.500 + 1 x 4.000 = 39.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 11);

        $this->assertEquals(39_000, $result);
    }

    #[Test]
    public function rentang_inklusif_pemakaian_19_m3(): void
    {
        // Kasus nyata Arjo Suwito September: 19 m3 → 10 x 3.500 + 9 x 4.000 = 71.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 19);

        $this->assertEquals(71_000, $result);
    }

    #[Test]
    public function rentang_inklusif_pemakaian_20_m3(): void
    {
        // 20 m3 → 10 x 3.500 + 10 x 4.000 = 75.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 20);

        $this->assertEquals(75_000, $result);
    }

    #[Test]
    public function tagihan_selalu_naik_bertambah_saat_pemakaian_naik(): void
    {
        // Penjaga: pemakaian naik TIDAK BOLEH bikin tagihan turun. Ini yang
        // dikeluhan admin (18 m3 → 92.000, lalu 19 m3 → 91.500).
        $package = $this->createPackageWithInclusiveBlocks();

        $sebelum = $this->service->calculateChargeForTesting($package, 18);
        $sesudah = $this->service->calculateChargeForTesting($package, 19);

        $this->assertGreaterThan($sebelum, $sesudah);
    }
}
