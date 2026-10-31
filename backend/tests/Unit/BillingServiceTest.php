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
        // 15 m3 -> blok 10-20 @ 2.000 -> 15 x 2.000 = 30.000
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 15);

        $this->assertEquals(30_000, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_blok_ketiga(): void
    {
        // 25 m3 -> blok 20+ @ 2.500 -> 25 x 2.500 = 62.500
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 25);

        $this->assertEquals(62_500, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_tepat_di_batas_blok(): void
    {
        // 10 m³ -> masih di blok 0-10 -> 10 × 1.500 = 15.000
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 10);

        $this->assertEquals(15_000, $result);
    }

    #[Test]
    public function kalkulasi_tagihan_desimal(): void
    {
        // 10,5 m³ -> blok 10-20 -> 10,5 × 2.000 = 21.000
        $package = $this->createPackageWithBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 10.5);

        $this->assertEquals(21_000, $result);
    }

    /**
     * Paket dengan penulisan rentang INKLUSIF — format yang dipakai data
     * produksi (paket "Warga": `1-10 m3` @ 3.500 lalu `11-100000 m3` @ 4.000).
     *
     * Seluruh pemakaian dibebankan dengan harga blok TEMPAT pemakaian itu
     * berada (bukan dijumlahkan per blok). Ini aturan yang dipakai aplikasi
     * lama dan seluruh tagihan yang sudah tercatat di `monthly_bills`.
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
        // 11 m3 -> blok 11-100000 @ 4.000 -> 11 x 4.000 = 44.000
        //
        // BUKAN 39.000 (10 x 3.500 + 1 x 4.000). Rumus progresif tidak pernah
        // dipakai di produksi, dan memakainya bikin tagihan bulan berjalan
        // berbeda dari tagihan bulan-bulan sebelumnya untuk pemakaian yang sama.
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 11);

        $this->assertEquals(44_000, $result);
    }

    #[Test]
    public function rentang_inklusif_pemakaian_19_m3(): void
    {
        // 19 m3 -> blok 11-100000 @ 4.000 -> 19 x 4.000 = 76.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 19);

        $this->assertEquals(76_000, $result);
    }

    #[Test]
    public function rentang_inklusif_pemakaian_20_m3(): void
    {
        // 20 m3 -> blok 11-100000 @ 4.000 -> 20 x 4.000 = 80.000
        $package = $this->createPackageWithInclusiveBlocks();
        $result  = $this->service->calculateChargeForTesting($package, 20);

        $this->assertEquals(80_000, $result);
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

    /**
     * Batas antar blok harus INKLUSIF di kedua sisi: pemakaian tepat di
     * `usage_max_m3` masih milik blok itu, bukan lompat ke blok berikutnya.
     *
     * Ini yang membuat tagihan naik monotonik di batas blok (10 -> 11 m3:
     * 35.000 -> 44.000, tidak turun).
     */
    #[Test]
    public function batas_blok_inklusif_di_kedua_sisi(): void
    {
        $package = $this->createPackageWithInclusiveBlocks();

        $this->assertEquals(35_000, $this->service->calculateChargeForTesting($package, 10));
        $this->assertEquals(44_000, $this->service->calculateChargeForTesting($package, 11));
    }
}
