<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WaterTariffBlock;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edit pemakaian harus ikut mengubah TAGIHAN.
 *
 * Bug yang diperbaiki: form "Edit Pemakaian Air" di halaman Data Pemakaian
 * Air menyimpan perubahan meter hanya di state frontend (tanpa panggilan API),
 * jadi tagihannya di `monthly_bills` tetap memakai tarif lama. Akibatnya
 * pemakaian berubah, nominal tagihan tidak.
 *
 * Endpoint `PUT /api/monthly-bills/usage` menutup keduanya: ubah meter, lalu
 * tagihan dihitung ulang dari tarif paket yang berlaku.
 */
class UpdatePemakaianAirTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teknisi;

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

        $this->teknisi = User::create([
            'name' => 'Teknisi Test',
            'email' => 'teknisi@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
        ]);

        $this->billing = new BillingService;
    }

    /**
     * Paket dengan tarif progresif: 0-20 m³ @ 2.000, 20+ m³ @ 3.000.
     */
    private function makePaket(int $abodemen = 15_000): InstallationPackage
    {
        $package = InstallationPackage::create([
            'name' => 'Paket Test',
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

        WaterTariffBlock::create([
            'package_id' => $package->id,
            'usage_min_m3' => 20,
            'usage_max_m3' => null,
            'price_per_m3' => 3_000,
        ]);

        return $package;
    }

    private function makeCustomer(InstallationPackage $package, int $meterAwal = 119): Customer
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

    private function catatMeter(Customer $customer, int $periode, int $nilai, ?int $tahun = null, ?User $oleh = null): MeterReading
    {
        return MeterReading::create([
            'customer_id' => $customer->id,
            'meter_value' => $nilai,
            'reading_month' => $periode,
            'reading_year' => $tahun ?? now()->year,
            'recorded_at' => now(),
            'recorded_by' => $oleh?->id ?? $this->admin->id,
        ]);
    }

    private function url(int $tahun, int $bulan): string
    {
        return '/api/monthly-bills/usage?month='.$bulan.'&year='.$tahun;
    }

    /**
     * INTI BUG: meter akhir diubah → pemakaian DAN tagihan harus ikut berubah.
     *
     * Nilai lama: meter 119 → 129, pemakaian 10 m³, tagihan 20.000 + 15.000.
     * Nilai baru: meter 119 → 139, pemakaian 20 m³, tagihan 40.000 + 15.000.
     *
     * Kalau tagihannya tidak ikut berubah, itu bug yang dilaporkan.
     */
    #[Test]
    public function edit_meter_akhir_ikut_mengubah_tagihan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->assertSame(10.0, (float) $bill->usage_m3);
        $this->assertSame(20_000.0, (float) $bill->usage_charge);
        $this->assertSame(35_000.0, (float) $bill->total_amount);

        // ── Edit meter akhir: 129 → 139 ──
        $res = $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertOk()->assertJson(['success' => true]);

        $bill->refresh();

        $this->assertSame(20.0, (float) $bill->usage_m3, 'Pemakaian harus ikut berubah.');
        $this->assertSame(40_000.0, (float) $bill->usage_charge, 'Biaya pemakaian harus dihitung ulang.');
        $this->assertSame(55_000.0, (float) $bill->total_amount, 'Total tagihan harus ikut berubah.');

        $this->assertSame(
            139,
            (int) $res->json('data.after.meter_reading_end'),
            'Response harus melaporkan angka tagihan terbaru.'
        );
    }

    /**
     * Menurunkan meter juga harus menurunkan tagihan (bukan hanya menaikkan).
     */
    #[Test]
    public function edit_meter_menurunkan_akhir_ikut_menurunkan_tagihan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 139);

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);
        $this->assertSame(55_000.0, (float) $bill->total_amount);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 129,
        ])->assertOk();

        $bill->refresh();

        $this->assertSame(10.0, (float) $bill->usage_m3);
        $this->assertSame(35_000.0, (float) $bill->total_amount, 'Tagihan harus turun mengikuti pemakaian.');
    }

    /**
     * After edit, the usage list must show the new value — the page reloads
     * from the server, so what is displayed matches what is stored.
     */
    #[Test]
    public function setelah_edit_daftar_pemakaian_menampilkan_nominal_terbaru(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);
        $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertOk();

        $res = $this->getJson($this->url(now()->year, now()->month))->assertOk();

        $row = collect($res->json('data'))->firstWhere('customer_code', $customer->customer_code);

        $this->assertNotNull($row);
        $this->assertSame(20.0, (float) $row['pemakaian'], 'Pemakaian di daftar harus terbaru.');
        $this->assertSame(55_000.0, (float) $row['tagihan'], 'Tagihan di daftar harus terbaru.');
        $this->assertSame(139.0, (float) $row['meter_akhir'], 'Meter akhir di daftar harus terbaru.');
    }
    /**
     * Tagihan yang sudah lunas TIDAK boleh diedit.
     */
    #[Test]
    public function tagihan_yang_sudah_lunas_tidak_bisa_diedit(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);
        $bill->update(['status' => 'paid']);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertStatus(409)->assertJson(['success' => false]);

        $bill->refresh();

        $this->assertSame('paid', $bill->status, 'Status tagihan tidak boleh berubah.');
        $this->assertSame(10.0, (float) $bill->usage_m3, 'Pemakaian tidak boleh berubah.');
        $this->assertSame(35_000.0, (float) $bill->total_amount, 'Tagihan tidak boleh berubah.');
    }

    /**
     * Tagihan yang sudah punya pembayaran (bill_payments) juga terkunci,
     * walau kolom status-nya masih `unpaid` — sumber kebenaran status PAID
     * di halaman pemakaian adalah presence of payment.
     */
    #[Test]
    public function tagihan_yang_sudah_ada_pembayarannya_tidak_bisa_diedit(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        // Bayar sebagian, status di DB masih `unpaid`.
        $bill->billPayments()->create([
            'amount_paid' => 10_000,
            'confirmed_by' => $this->admin->id,
            'paid_at' => now(),
        ]);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertStatus(409);

        $this->assertSame(10.0, (float) $bill->fresh()->usage_m3);
    }

    /**
     * Meter tidak boleh mundur ke angka lebih kecil dari bulan sebelumnya.
     */
    #[Test]
    public function meter_tidak_boleh_lebih_kecil_dari_bulan_sebelumnya(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $prevMonth = now()->month === 1 ? 12 : now()->month - 1;
        $prevYear = now()->month === 1 ? now()->year - 1 : now()->year;

        $this->catatMeter($customer, $prevMonth, 100, $prevYear);
        $this->catatMeter($customer, now()->month, 129);

        $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 50,
        ])->assertStatus(400)->assertJson(['success' => false]);

        $this->assertSame(129, (int) MeterReading::where('customer_id', $customer->id)
            ->where('reading_month', now()->month)
            ->where('reading_year', now()->year)
            ->first()->meter_value);
    }

    /**
     * Jurnal piutang tunggakan harus menyusul nominal tagihan yang baru.
     *
     * Kalau tidak, piutang yang tercatat tidak lagi sama dengan tagihan:
     * tagihan naik → piutang kurang; tagihan turun → piutang lebih.
     */
    #[Test]
    public function jurnal_piutang_disinkronkan_dengan_tagihan_terbaru(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);

        $bill = $this->billing->generateForCustomer($customer, now()->year, now()->month);

        // Jurnal piutang seperti yang dibuat command generate-overdue.
        Transaction::create([
            'tgl_transaksi' => now()->toDateString(),
            'account_debet' => '1.1.03.01',
            'account_kredit' => '4.1.01.02',
            'reverence_type' => 'overdue_bill',
            'reverence_id' => $bill->id,
            'keterangan_transaksi' => 'Piutang Abodemen',
            'relasi' => $customer->customer_code,
            'saldo' => 15_000,
            'id_user' => $this->admin->id,
        ]);

        Transaction::create([
            'tgl_transaksi' => now()->toDateString(),
            'account_debet' => '1.1.03.01',
            'account_kredit' => '4.1.01.03',
            'reverence_type' => 'overdue_bill',
            'reverence_id' => $bill->id,
            'keterangan_transaksi' => 'Piutang Denda',
            'relasi' => $customer->customer_code,
            'saldo' => 20_000,
            'id_user' => $this->admin->id,
        ]);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertOk();

        $pemakaian = Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->where('account_kredit', '4.1.01.03')
            ->first();

        $abodemen = Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->where('account_kredit', '4.1.01.02')
            ->first();

        $this->assertNotNull($pemakaian, 'Jurnal piutang pemakaian harus tetap ada.');
        $this->assertSame(40_000.0, (float) $pemakaian->saldo, 'Nominal piutang pemakaian harus ikut tagihan baru.');
        $this->assertSame(15_000.0, (float) $abodemen->saldo, 'Nominal piutang abodemen tidak berubah.');
    }

    /**
     * Teknisi hanya boleh mengedit pelanggan yang pernah ia tangani.
     */
    #[Test]
    public function teknisi_tidak_bisa_mengedit_pelanggan_milik_teknisi_lain(): void
    {
        Sanctum::actingAs($this->teknisi, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129);
        $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertStatus(403);
    }

    /**
     * Teknisi BOLEH mengedit pelanggan yang memang ia tangani.
     */
    #[Test]
    public function teknisi_bisa_mengedit_pelanggan_yang_ia_tangani(): void
    {
        Sanctum::actingAs($this->teknisi, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package);

        $this->catatMeter($customer, now()->month, 129, now()->year, $this->teknisi);
        $this->billing->generateForCustomer($customer, now()->year, now()->month);

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertOk()->assertJson(['success' => true]);
    }

    /**
     * Pelanggan tidak punya akses ke endpoint ini sama sekali.
     */
    #[Test]
    public function pelanggan_ditolak(): void
    {
        $pelanggan = User::create([
            'name' => 'Pelanggan Login',
            'email' => uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        Sanctum::actingAs($pelanggan, ['*'], 'sanctum');

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => 1,
            'month' => now()->month,
            'year' => now()->year,
            'meter_value' => 139,
        ])->assertStatus(403);
    }

    /**
     * Meter akhir bulan ini adalah METER AWAL bulan berikutnya, jadi
     * tagihan bulan depan juga harus ikut dihitung ulang.
     *
     * Kalau tidak, tagihan bulan depan memakai meter awal yang sudah tidak
     * berlaku — pemakaiannya jadi tidak sinkron dengan meter.
     */
    #[Test]
    public function edit_meter_juga_menghitung_ulang_tagihan_periode_berikutnya(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package, meterAwal: 119);

        $thisMonth = now()->month;
        $thisYear = now()->year;

        [$nextMonth, $nextYear] = $thisMonth === 12
            ? [1, $thisYear + 1]
            : [$thisMonth + 1, $thisYear];

        // Bulan ini: meter 119 → 129, pemakaian 10 m³.
        $this->catatMeter($customer, $thisMonth, 129, $thisYear);
        $billThis = $this->billing->generateForCustomer($customer, $thisYear, $thisMonth);

        // Bulan depan: meter 129 → 139, pemakaian 10 m³.
        $this->catatMeter($customer, $nextMonth, 139, $nextYear);
        $billNext = $this->billing->generateForCustomer($customer, $nextYear, $nextMonth);

        $this->assertSame(10.0, (float) $billThis->usage_m3);
        $this->assertSame(10.0, (float) $billNext->usage_m3);
        // 20.000 pemakaian + 15.000 abodemen + 10.000 denda (tagihan bulan
        // ini masih `unpaid`, jadi dendanya sudah tertempel).
        $this->assertSame(45_000.0, (float) $billNext->total_amount);

        // ── Edit meter bulan ini: 129 → 139 ──
        // Meter awal bulan depan jadi 139, sehingga pemakaiannya 139-139 = 0.
        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => $thisMonth,
            'year' => $thisYear,
            'meter_value' => 139,
        ])->assertOk();

        $billThis->refresh();
        $billNext->refresh();

        $this->assertSame(20.0, (float) $billThis->usage_m3, 'Pemakaian bulan ini harus ikut berubah.');
        $this->assertSame(55_000.0, (float) $billThis->total_amount, 'Tagihan bulan ini harus ikut berubah.');

        $this->assertSame(
            139,
            (int) $billNext->meter_reading_start,
            'Meter awal bulan depan harus ikut menyesuaikan.'
        );
        $this->assertSame(0.0, (float) $billNext->usage_m3, 'Pemakaian bulan depan dihitung dari meter baru.');
        $this->assertSame(
            25_000.0,
            (float) $billNext->total_amount,
            'Tagihan bulan depan harus ikut dihitung ulang (15.000 abodemen + 10.000 denda, tanpa biaya pemakaian).'
        );
    }

    /**
     * Tagihan bulan depan yang sudah LUNAS tidak boleh dibongkar karena
     * periode sebelumnya diedit.
     */
    #[Test]
    public function tagihan_periode_berikutnya_yang_lunas_tidak_ikut_dibongkar(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $package = $this->makePaket();
        $customer = $this->makeCustomer($package, meterAwal: 119);

        $thisMonth = now()->month;
        $thisYear = now()->year;

        [$nextMonth, $nextYear] = $thisMonth === 12
            ? [1, $thisYear + 1]
            : [$thisMonth + 1, $thisYear];

        $this->catatMeter($customer, $thisMonth, 129, $thisYear);
        $this->billing->generateForCustomer($customer, $thisYear, $thisMonth);

        $this->catatMeter($customer, $nextMonth, 139, $nextYear);
        $billNext = $this->billing->generateForCustomer($customer, $nextYear, $nextMonth);
        $billNext->update(['status' => 'paid']);

        $totalBefore = (float) $billNext->total_amount;

        $this->putJson('/api/monthly-bills/usage', [
            'customer_id' => $customer->id,
            'month' => $thisMonth,
            'year' => $thisYear,
            'meter_value' => 139,
        ])->assertOk();

        $billNext->refresh();

        $this->assertSame(
            $totalBefore,
            (float) $billNext->total_amount,
            'Tagihan yang sudah lunas tidak boleh ikut berubah.'
        );
        $this->assertSame('paid', $billNext->status);
    }
}
