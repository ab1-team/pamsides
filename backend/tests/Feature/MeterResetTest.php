<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MeterReading;
use App\Models\MeterReset;
use App\Models\MonthlyBill;
use App\Models\User;
use App\Models\WaterTariffBlock;
use App\Services\BillingService;
use App\Services\MeterResetService;
use App\Services\TariffCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regresi fitur reset / ganti meteran.
 *
 * Fokus:
 *   - baseline `startMeterForPeriod()` (3 cabang),
 *   - idempotensi tagihan per segmen di BillingService,
 *   - konversi tagihan normal -> lama di MeterResetService.
 */
class MeterResetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teknisi;

    private InstallationPackage $package;

    private InstallationTicket $ticket;

    private Customer $customer;

    private BillingService $billing;

    private MeterResetService $resets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-reset@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->teknisi = User::create([
            'name' => 'Teknisi Test',
            'email' => 'teknisi-reset@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'teknisi',
        ]);

        $this->package = InstallationPackage::create([
            'name' => 'Warga Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 10_000,
        ]);

        // Blok inklusif seperti data produksi: 1-10 @ 3.500, 11-.. @ 4.000.
        WaterTariffBlock::insert([
            ['package_id' => $this->package->id, 'usage_min_m3' => 1, 'usage_max_m3' => 10, 'price_per_m3' => 3_500, 'created_at' => now(), 'updated_at' => now()],
            ['package_id' => $this->package->id, 'usage_min_m3' => 11, 'usage_max_m3' => 100_000, 'price_per_m3' => 4_000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->ticket = InstallationTicket::create([
            'package_id' => $this->package->id,
            'user_id' => $this->teknisi->id,
            'applicant_name' => 'Pelanggan Reset',
            'nik' => '3300000000000099',
            'address' => 'Jl. Test Reset',
            'lat' => -7.797068,
            'lng' => 110.370529,
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        $this->customer = Customer::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->teknisi->id,
            'customer_code' => 'PDAM-TEST-RESET-01',
            'initial_meter_reading' => 0,
            'activated_at' => now(),
        ]);

        $this->billing = new BillingService;
        $this->resets = new MeterResetService(new TariffCalculator);
    }

    private function recordReading(int $year, int $month, float $value): MeterReading
    {
        return MeterReading::create([
            'customer_id' => $this->customer->id,
            'recorded_by' => $this->teknisi->id,
            'reading_year' => $year,
            'reading_month' => $month,
            'meter_value' => $value,
            'photo_url' => null,
            'recorded_at' => Carbon::create($year, $month, 3, 10, 0, 0),
        ]);
    }

    private function createBill(int $year, int $month, array $attributes = []): MonthlyBill
    {
        return MonthlyBill::create(array_merge([
            'customer_id' => $this->customer->id,
            'billing_period_year' => $year,
            'billing_period_month' => $month,
            'meter_segment' => 'normal',
            'meter_reading_start' => 0,
            'meter_reading_end' => 0,
            'usage_m3' => 0,
            'usage_charge' => 0,
            'abodemen' => 0,
            'penalty_amount' => 0,
            'total_amount' => 0,
            'status' => 'paid',
            'due_date' => '2026-01-27',
        ], $attributes));
    }

    private function createReset(int $year, int $month, float $previous, float $oldFinal, float $newStart): MeterReset
    {
        return MeterReset::create([
            'customer_id' => $this->customer->id,
            'period_year' => $year,
            'period_month' => $month,
            'previous_meter_value' => $previous,
            'old_meter_final' => $oldFinal,
            'new_meter_start' => $newStart,
            'reason' => 'Meter rusak',
            'created_by' => $this->admin->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // startMeterForPeriod(): 3 cabang baseline
    // ---------------------------------------------------------------------

    #[Test]
    public function start_meter_memakai_new_meter_start_saat_reset_di_periode_yang_sama(): void
    {
        $this->createReset(2026, 10, previous: 100, oldFinal: 121, newStart: 5);

        $this->assertEqualsWithDelta(
            5.0,
            $this->resets->startMeterForPeriod($this->customer, 2026, 10),
            0.0001
        );
    }

    #[Test]
    public function start_meter_memakai_new_meter_start_saat_reset_periode_lalu_belum_ada_bacaan_baru(): void
    {
        // Reset September, bacaan September = angka akhir meter lama (belum
        // ada bacaan meter baru sesudahnya).
        $this->createReset(2026, 9, previous: 84, oldFinal: 100, newStart: 0);
        $this->recordReading(2026, 9, 100);

        $this->assertEqualsWithDelta(
            0.0,
            $this->resets->startMeterForPeriod($this->customer, 2026, 10),
            0.0001
        );
    }

    #[Test]
    public function start_meter_memakai_meter_terakhir_saat_tidak_ada_reset(): void
    {
        $this->createBill(2026, 9, [
            'meter_reading_start' => 84,
            'meter_reading_end' => 100,
            'usage_m3' => 16,
            'usage_charge' => 64_000,
            'abodemen' => 10_000,
            'total_amount' => 74_000,
        ]);
        $this->recordReading(2026, 9, 100);

        $this->assertEqualsWithDelta(
            100.0,
            $this->resets->startMeterForPeriod($this->customer, 2026, 10),
            0.0001
        );
    }

    #[Test]
    public function start_meter_memakai_bacaan_terakhir_saat_reset_periode_lalu_sudah_ada_bacaan_baru(): void
    {
        $this->createReset(2026, 8, previous: 84, oldFinal: 100, newStart: 0);
        $this->recordReading(2026, 8, 100); // penutup meter lama
        $this->recordReading(2026, 9, 15);  // bacaan meter baru

        $this->assertEqualsWithDelta(
            15.0,
            $this->resets->startMeterForPeriod($this->customer, 2026, 10),
            0.0001
        );
    }

    // ---------------------------------------------------------------------
    // performReset(): konversi tagihan normal -> lama
    // ---------------------------------------------------------------------

    #[Test]
    public function perform_reset_mengubah_tagihan_normal_menjadi_lama_tanpa_dobel(): void
    {
        // Meter Agustus berakhir di 100 (jadi baseline September = 100).
        $this->createBill(2026, 8, [
            'meter_reading_start' => 84,
            'meter_reading_end' => 100,
            'usage_m3' => 16,
            'usage_charge' => 64_000,
            'abodemen' => 10_000,
            'total_amount' => 74_000,
        ]);
        $this->recordReading(2026, 9, 121);

        // Tagihan normal September sudah terlanjur dibuat sebelum reset.
        $normal = $this->createBill(2026, 9, [
            'meter_reading_start' => 100,
            'meter_reading_end' => 121,
            'usage_m3' => 21,
            'usage_charge' => 84_000,
            'abodemen' => 10_000,
            'total_amount' => 94_000,
            'status' => 'unpaid',
            'due_date' => '2026-10-27',
        ]);

        $result = $this->resets->performReset(
            $this->customer,
            2026,
            9,
            null,
            0,
            'Meter rusak',
            $this->admin->id,
        );

        $reset = $result['reset'];
        $bill = $result['bill'];

        $this->assertEqualsWithDelta(100.0, (float) $reset->previous_meter_value, 0.0001);
        $this->assertEqualsWithDelta(121.0, (float) $reset->old_meter_final, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $reset->new_meter_start, 0.0001);

        // Satu tagihan saja untuk periode ini: baris normal tadi DIKONVERSI.
        $bills = MonthlyBill::where('customer_id', $this->customer->id)
            ->where('billing_period_year', 2026)
            ->where('billing_period_month', 9)
            ->get();

        $this->assertCount(1, $bills);
        $this->assertSame($normal->id, $bill->id);

        $this->assertSame('lama', $bill->meter_segment);
        $this->assertSame($reset->id, $bill->meter_reset_id);
        $this->assertEqualsWithDelta(100.0, (float) $bill->meter_reading_start, 0.0001);
        $this->assertEqualsWithDelta(121.0, (float) $bill->meter_reading_end, 0.0001);
        $this->assertEqualsWithDelta(21.0, (float) $bill->usage_m3, 0.0001);
        $this->assertEqualsWithDelta(84_000.0, (float) $bill->usage_charge, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $bill->abodemen, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $bill->penalty_amount, 0.0001);
        $this->assertEqualsWithDelta(84_000.0, (float) $bill->total_amount, 0.0001);
    }

    #[Test]
    public function perform_reset_menolak_reset_kedua_pada_periode_yang_sama(): void
    {
        $this->recordReading(2026, 9, 121);
        $this->createReset(2026, 9, previous: 100, oldFinal: 121, newStart: 0);

        $this->expectException(\RuntimeException::class);

        $this->resets->performReset($this->customer, 2026, 9, null, 0, 'Rusak lagi', $this->admin->id);
    }

    // ---------------------------------------------------------------------
    // BillingService: idempotensi per segmen
    // ---------------------------------------------------------------------

    #[Test]
    public function generate_tagihan_idempoten_per_segmen_dan_tidak_dobel(): void
    {
        // Bulan sebelumnya lunas, jadi tidak ada denda.
        $this->createBill(2026, 8, [
            'meter_reading_start' => 84,
            'meter_reading_end' => 100,
            'usage_m3' => 16,
            'usage_charge' => 64_000,
            'abodemen' => 10_000,
            'total_amount' => 74_000,
        ]);
        $this->recordReading(2026, 9, 121);

        // Panggil dua kali: tetap satu tagihan `normal`, nilai sama.
        $first = $this->billing->generateForCustomer($this->customer, 2026, 9);
        $second = $this->billing->generateForCustomer($this->customer, 2026, 9);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MonthlyBill::where('customer_id', $this->customer->id)
            ->where('billing_period_year', 2026)
            ->where('billing_period_month', 9)
            ->where('meter_segment', 'normal')
            ->count());

        $this->assertEqualsWithDelta(100.0, (float) $second->meter_reading_start, 0.0001);
        $this->assertEqualsWithDelta(121.0, (float) $second->meter_reading_end, 0.0001);
        $this->assertEqualsWithDelta(84_000.0, (float) $second->usage_charge, 0.0001);
        $this->assertEqualsWithDelta(94_000.0, (float) $second->total_amount, 0.0001);

        // Reset -> tagihan normal tadi menjadi `lama`.
        $resetResult = $this->resets->performReset(
            $this->customer,
            2026,
            9,
            null,
            0,
            'Meter rusak',
            $this->admin->id,
        );
        $lamaId = $resetResult['bill']->id;

        // Bacaan meter baru masuk, lalu generate tagihan `baru` dua kali.
        MeterReading::where('customer_id', $this->customer->id)
            ->where('reading_year', 2026)
            ->where('reading_month', 9)
            ->update(['meter_value' => 32]);

        $baru1 = $this->billing->generateForCustomer($this->customer, 2026, 9);
        $baru2 = $this->billing->generateForCustomer($this->customer, 2026, 9);

        $this->assertSame($baru1->id, $baru2->id);
        $this->assertSame('baru', $baru2->meter_segment);
        $this->assertEqualsWithDelta(0.0, (float) $baru2->meter_reading_start, 0.0001);
        $this->assertEqualsWithDelta(32.0, (float) $baru2->meter_reading_end, 0.0001);
        $this->assertEqualsWithDelta(128_000.0, (float) $baru2->usage_charge, 0.0001);
        $this->assertEqualsWithDelta(138_000.0, (float) $baru2->total_amount, 0.0001);
        $this->assertSame($resetResult['reset']->id, $baru2->meter_reset_id);

        // Periode yang sama: tepat dua tagihan (lama + baru), tidak dobel.
        $this->assertSame(1, MonthlyBill::where('customer_id', $this->customer->id)
            ->where('billing_period_year', 2026)
            ->where('billing_period_month', 9)
            ->where('meter_segment', 'lama')
            ->count());
        $this->assertSame(1, MonthlyBill::where('customer_id', $this->customer->id)
            ->where('billing_period_year', 2026)
            ->where('billing_period_month', 9)
            ->where('meter_segment', 'baru')
            ->count());

        // Tagihan meter lama tidak ikut berubah saat tagihan baru dibuat.
        $lama = MonthlyBill::find($lamaId);
        $this->assertSame('lama', $lama->meter_segment);
        $this->assertEqualsWithDelta(84_000.0, (float) $lama->total_amount, 0.0001);
    }
}
