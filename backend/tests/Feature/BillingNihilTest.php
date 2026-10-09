<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\User;
use App\Models\WaterTariffBlock;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Auto-paid tagihan Rp 0 hanya sah kalau nolnya bisa dibenarkan.
 *
 * Yang diuji di sini adalah pemisahan dua penyebab total_amount == 0 yang
 * sifatnya sama sekali berbeda:
 *
 *   - pelanggan benar-benar tidak memakai air, dan
 *   - pelanggan memakai air tapi paketnya belum punya blok tarif.
 *
 * Yang kedua TIDAK boleh jadi `paid`. Kalau iya, pelanggan yang benar-benar
 * mengonsumsi air tidak ditagih sama sekali dan konfigurasi paket yang rusak
 * ikut tersembunyi permanen.
 */
class BillingNihilTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->billing = new BillingService;
    }

    /**
     * Paket dengan tarif configured.
     */
    private function makePaketDenganTarif(int $abodemen = 15_000): InstallationPackage
    {
        $package = InstallationPackage::create([
            'name' => 'Paket Ber Tarif',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => $abodemen,
            'late_penalty' => 10_000,
        ]);

        WaterTariffBlock::create([
            'package_id' => $package->id,
            'usage_min_m3' => 0,
            'usage_max_m3' => 20,
            'price_per_m3' => 2_000,
        ]);

        return $package;
    }

    /**
     * Paket tanpa blok tarif sama sekali — persis kondisi yang bisa tersimpan
     * karena paket dan blok tarif dibuat lewat endpoint terpisah tanpa
     * coupling apa pun. Abodemen 0 legal (`min:0` di validasinya).
     */
    private function makePaketTanpaTarif(): InstallationPackage
    {
        return InstallationPackage::create([
            'name' => 'Paket Tanpa Tarif',
            'installation_fee' => 0,
            'monthly_abodemen' => 0,
            'late_penalty' => 0,
        ]);
    }

    private function makeCustomer(InstallationPackage $package, int $meterAwal = 0): Customer
    {
        $user = User::create([
            'name' => 'Pelanggan '.uniqid(),
            'email' => uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'applicant_name' => $user->name,
            'nik' => '330000000000'.random_int(1000, 9999),
            'address' => 'Jl. Test No. 1',
            'lat' => -7.797068,
            'lng' => 110.370529,
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        return Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'customer_code' => 'PDAM-'.uniqid(),
            'initial_meter_reading' => $meterAwal,
            'meter_photo_url' => '/storage/test.jpg',
            'activated_at' => now()->subMonth(),
        ]);
    }

    /**
     * Catat meter bulan ini sehingga `generateForCustomer` punya data.
     */
    private function catatMeter(Customer $customer, int $periode, int $nilai): MeterReading
    {
        return MeterReading::create([
            'customer_id' => $customer->id,
            'meter_value' => $nilai,
            'reading_month' => $periode,
            'reading_year' => now()->year,
            'recorded_at' => now(),
            'recorded_by' => $this->admin->id,
        ]);
    }

    /**
     * SANITY: pelanggan tidak pakai air dan paketnya tidak menagih apa pun →
     * tagihan sah Rp 0 → harus `paid`.
     *
     * Ini satu-satunya kasus yang boleh auto-paid.
     */
    #[Test]
    public function pelanggan_tidak_pakai_air_dan_paket_nihil_otomatis_paid(): void
    {
        $package = $this->makePaketTanpaTarif();
        $customer = $this->makeCustomer($package, meterAwal: 100);

        $this->catatMeter($customer, now()->month, 100); // meter tidak naik → usage 0

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->assertSame(0.0, (float) $bill->usage_m3, 'Pelanggan ini harus ber-usage 0 m³.');
        $this->assertSame(0.0, (float) $bill->total_amount, 'Tagihan harus benar-benar Rp 0.');
        $this->assertSame('paid', $bill->status, 'Tagihan nihil yang sah harus otomatis lunas.');
    }

    /**
     * KASUS BAHAYA — inti concern ini.
     *
     * Pelanggan memakai 10 m³, tapi paketnya belum punya blok tarif sehingga
     * `usage_charge` = 0. Total tagihan Rp 0.
     *
     * Ini TIDAK boleh jadi `paid`:
     *   - pelanggan benar-benar mengonsumsi air, jadi tidak seharusnya gratis;
     *   - membiarkan `paid` menyembunyikan paket yang belum dikonfigurasi.
     *
     * Harus tetap `unpaid` supaya terlihat di daftar tagihan dan bisa
     * dibetulkan admin.
     */
    #[Test]
    public function pelanggan_memakai_air_tapi_tagihan_nihil_tidak_otomatis_paid(): void
    {
        $package = $this->makePaketTanpaTarif();
        $customer = $this->makeCustomer($package, meterAwal: 100);

        $this->catatMeter($customer, now()->month, 110); // naik 10 m³

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->assertSame(10.0, (float) $bill->usage_m3, 'Pelanggan ini harus ber-usage 10 m³.');
        $this->assertSame(0.0, (float) $bill->total_amount, 'Tanpa blok tarif, tagihannya jadi nol.');
        $this->assertSame(
            'unpaid',
            $bill->status,
            'Pemakaian > 0 dengan tagihan Rp 0 berarti paket bermasalah — harus tetap unpaid agar terlihat, bukan auto-paid.'
        );
    }

    /**
     * Pelanggan tidak pakai air, tapi paketnya tetap menagih abodemen.
     * Nolnya TIDAK berasal dari pemakaian — tagihannya bukan Rp 0 sama sekali.
     * Tetap `unpaid`, seperti biasa.
     */
    #[Test]
    public function pelanggan_tidak_pakai_air_tapi_paket_ada_abodemen_tetap_unpaid(): void
    {
        $package = $this->makePaketDenganTarif(abodemen: 15_000);
        $customer = $this->makeCustomer($package, meterAwal: 100);

        $this->catatMeter($customer, now()->month, 100); // usage 0

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->assertSame(0.0, (float) $bill->usage_m3, 'Pelanggan ini harus ber-usage 0 m³.');
        $this->assertSame(15_000.0, (float) $bill->total_amount, 'Abodemen tetap ditagih walau pemakaian 0.');
        $this->assertSame('unpaid', $bill->status, 'Pakai air 0 bukan berarti gratisan.');
    }

    /**
     * Pelanggan memakai air dan paketnya punya tarif normal → tagihan normal.
     * Ini memastikan logika baru tidak mengganggu jalur tagihan yang wajar.
     */
    #[Test]
    public function pemakaian_normal_dengan_tarif_normal_tetap_unpaid(): void
    {
        $package = $this->makePaketDenganTarif(abodemen: 15_000);
        $customer = $this->makeCustomer($package, meterAwal: 100);

        $this->catatMeter($customer, now()->month, 110); // 10 m³ × 2.000

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->assertSame(10.0, (float) $bill->usage_m3);
        $this->assertSame(20_000.0, (float) $bill->usage_charge);
        $this->assertSame(35_000.0, (float) $bill->total_amount);
        $this->assertSame('unpaid', $bill->status);
    }

    /**
     * Halaman Pemakaian Air harus konsisten dengan Daftar Tagihan.
     *
     * Tagihan Rp 0 berstatus `paid` (paket nihil, pelanggan tidak pakai air)
     * harus tampil PAID — bukan UNPAID hanya karena tidak ada nominal bayar.
     */
    #[Test]
    public function tagihan_nihil_berstatus_paid_tampil_pada_di_halaman_pemakaian(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaketTanpaTarif();
        $customer = $this->makeCustomer($package, meterAwal: 100);
        $this->catatMeter($customer, now()->month, 100); // usage 0

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);
        $this->assertSame('paid', $bill->status);

        $res = $this->getJson('/api/monthly-bills/usage?month='.now()->month.'&year='.now()->year)
            ->assertOk();

        $row = collect($res->json('data'))->firstWhere('customer_code', $customer->customer_code);

        $this->assertNotNull($row, 'Baris pelanggan harus ditemukan.');
        $this->assertSame('PAID', $row['status'], 'Tagihan Rp 0 yang sah harus tampil PAID.');
    }

    /**
     * Dan ke arah sebaliknya: tagihan Rp 0 yang SENGAJA dibiarkan `unpaid`
     * (karena pelanggan memakai air tapi paketnya belum bertarif) harus tetap
     * tampil UNPAID — jangan sampai ikut-safe jadi PAID hanya karena totalnya
     * nol, karena itu akan menyembunyikan paket yang rusak.
     */
    #[Test]
    public function tagihan_nihil_berstatus_unpaid_tetap_tampil_unpaid_di_halaman_pemakaian(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaketTanpaTarif();
        $customer = $this->makeCustomer($package, meterAwal: 100);
        $this->catatMeter($customer, now()->month, 110); // 10 m³, tanpa tarif → Rp 0

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);
        $this->assertSame('unpaid', $bill->status);

        $res = $this->getJson('/api/monthly-bills/usage?month='.now()->month.'&year='.now()->year)
            ->assertOk();

        $row = collect($res->json('data'))->firstWhere('customer_code', $customer->customer_code);

        $this->assertNotNull($row, 'Baris pelanggan harus ditemukan.');
        $this->assertSame(
            'UNPAID',
            $row['status'],
            'Tagihan Rp 0 berstatus unpaid harus tetap UNPAID supaya paket yang rusak tetap kelihatan.'
        );
    }

    /**
     * Tagihan Rp 0 yang sah (sudah `paid`) tidak punya pembayaran, jadi tidak
     * ada yang perlu di-rollback.Menolaknya lebih jujur daripada memberi hasil
     * yang menipu: statusnya akan otomatis kembali `paid` begitu generator
     * jalan lagi.
     */
    #[Test]
    public function tagihan_nihil_yang_sah_tidak_bisa_di_rollback(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaketTanpaTarif();
        $customer = $this->makeCustomer($package, meterAwal: 100);
        $this->catatMeter($customer, now()->month, 100); // usage 0

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->deleteJson("/api/monthly-bills/{$bill->id}")
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertSame('paid', $bill->fresh()->status, 'Status tidak boleh berubah.');
    }
}
