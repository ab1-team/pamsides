<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Endpoint ringkasan tagihan untuk panel lonceng di navbar.
 *
 * Yang paling rawan di endpoint ini adalah kebocoran data antar role: satu
 * query dipakai admin, teknisi, dan pelanggan dengan cakupan berbeda. Test di
 * bawah menjaga setiap role hanya melihat miliknya.
 */
class NotifikasiTagihanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role).' Uji',
            'email' => $email,
            'password' => Hash::make('rahasia123'),
            'role' => $role,
        ]);
    }

    /**
     * Pelanggan aktif + record customers (satu baris di tabel customers).
     *
     * User vertebrae ikut dikembalikan lewat `-&gt;user` supaya test yang
     * berpura-pura jadi pelanggan itu bisa login sebagai pemilik record
     * tersebut. Endpoint mencari tagihan lewat `customers.user_id`, bukan
     * lewat email, jadi user yang dipakai harus yang sama.
     */
    private function pelanggan(string $nama, string $nik): Customer
    {
        $package = InstallationPackage::create([
            'name' => 'Paket '.$nik,
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        $owner = User::create([
            'name' => $nama,
            'email' => strtolower(str_replace(' ', '-', $nama)).'@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $owner->id,
            'applicant_name' => $nama,
            'nik' => $nik,
            'address' => 'Jl. Uji',
            'phone' => '081200000000',
            'lat' => 0,
            'lng' => 0,
            'status' => 'completed',
            'created_by' => $owner->id,
        ]);

        return Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => '005.0001.100.'.$ticket->id,
            'initial_meter_reading' => 0,
        ]);
    }

    private function tagihan(Customer $customer, string $status, int $amount, string $dueDate): void
    {
        MonthlyBill::create([
            'customer_id' => $customer->id,
            'billing_period_year' => 2026,
            'billing_period_month' => 7,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => $amount - 10_000,
            'abodemen' => 10_000,
            'penalty_amount' => 0,
            'total_amount' => $amount,
            'status' => $status,
            'due_date' => $dueDate,
        ]);
    }

    /**
     * Ringkasan wajib selalu punya keempat angka. Badge lonceng membacanya
     * tanpa pengecekan null, jadi salah satu hilang akan tampil "NaN".
     */
    #[Test]
    public function summary_berisi_semua_angka(): void
    {
        Sanctum::actingAs($this->user('admin', 'admin-notif@pdam.test'), ['*']);

        $this->getJson('/api/monthly-bills/unpaid-summary')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'summary' => ['unpaid_count', 'unpaid_total', 'overdue_count', 'customer_count'],
            ]);
    }

    /**
     * Admin melihat seluruh pelanggan yang menunggak, dengan nama dan kode.
     */
    #[Test]
    public function admin_melihat_seluruh_pelanggan_menunggak(): void
    {
        Sanctum::actingAs($this->user('admin', 'admin-notif2@pdam.test'), ['*']);

        $a = $this->pelanggan('Warga Alfa', '3273010101010011');
        $b = $this->pelanggan('Warga Beta', '3273010101010012');

        $this->tagihan($a, 'unpaid', 50_000, now()->addDays(5)->toDateString());
        $this->tagihan($b, 'unpaid', 70_000, now()->subDays(5)->toDateString());

        $res = $this->getJson('/api/monthly-bills/unpaid-summary')->assertOk();

        $this->assertSame(2, $res->json('summary.customer_count'));
        $this->assertSame(2, $res->json('summary.unpaid_count'));
        $this->assertSame(120000.0, (float) $res->json('summary.unpaid_total'));
        $this->assertSame(1, $res->json('summary.overdue_count'), 'Tagihan lewat jatuh tempo harus dihitung.');

        // Urutan total unpaid terbesar dulu, supaya yang paling mendesak terlihat.
        $this->assertSame('Warga Beta', $res->json('data.0.name'));
    }

    /**
     * Tagihan yang sudah lunas tidak boleh ikut terhitung. Kalau tidak,
     * badge lonceng akan menunjukkan pelanggan yang sebenarnya sudah bayar.
     */
    #[Test]
    public function tagihan_lunas_dikecualikan(): void
    {
        Sanctum::actingAs($this->user('admin', 'admin-notif3@pdam.test'), ['*']);

        $c = $this->pelanggan('Warga Gamma', '3273010101010013');
        $this->tagihan($c, 'unpaid', 50_000, now()->addDays(5)->toDateString());
        $this->tagihan($c, 'paid', 90_000, now()->subDays(5)->toDateString());

        $res = $this->getJson('/api/monthly-bills/unpaid-summary')->assertOk();

        $this->assertSame(1, $res->json('summary.unpaid_count'));
        $this->assertSame(50000.0, (float) $res->json('summary.unpaid_total'));
    }

    /**
     * Pelanggan hanya boleh melihat tagihannya sendiri. Inilah kebocoran yang
     * paling merusak kalau endpoint ini tanpa filter per role.
     */
    #[Test]
    public function pelanggan_hanya_melihat_tagihan_sendiri(): void
    {
        $milik = $this->pelanggan('Warga Sendiri', '3273010101010014');
        $orangLain = $this->pelanggan('Warga Orang', '3273010101010015');

        $this->tagihan($milik, 'unpaid', 40_000, now()->addDays(3)->toDateString());
        $this->tagihan($orangLain, 'unpaid', 999_000, now()->subDays(3)->toDateString());

        // Login sebagai PENGGUNA yang benar-benarilik record customers,
        // bukan user pelanggan baru — endpoint mencari lewat customers.user_id.
        Sanctum::actingAs($milik->user, ['*']);

        $res = $this->getJson('/api/monthly-bills/unpaid-summary')->assertOk();

        $this->assertSame(1, $res->json('summary.unpaid_count'));
        $this->assertSame(40000.0, (float) $res->json('summary.unpaid_total'));
        $this->assertCount(1, $res->json('data'));
    }

    /**
     * Pelanggan tanpa record Customer belum punya tagihan sama sekali.
     * Endpoint harus balas 200 dengan ringkasan nol supaya badge tidak error.
     */
    #[Test]
    public function pelanggan_tanpa_record_tetap_dapat_ringkasan_kosong(): void
    {
        Sanctum::actingAs($this->user('pelanggan', 'pelanggan-kosong@pdam.test'), ['*']);

        $this->getJson('/api/monthly-bills/unpaid-summary')
            ->assertOk()
            ->assertJsonPath('summary.unpaid_count', 0)
            ->assertJsonPath('summary.customer_count', 0)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Surveyor tidak punya halaman tagihan, jadi role ini ditolak 403.
     * Tanpa guard, ia akan melihat ringkasan seluruh pelanggan.
     */
    #[Test]
    public function surveyor_ditolak(): void
    {
        Sanctum::actingAs($this->user('surveyor', 'surveyor-notif@pdam.test'), ['*']);

        $this->getJson('/api/monthly-bills/unpaid-summary')->assertForbidden();
    }

    /**
     * `due_date` harus di-cast jadi objek Carbon. Tanpa cast, kode di
     * controller yang memanggil ->toDateString() melempar Error 500.
     */
    #[Test]
    public function due_date_terbaca_sebagai_carbon(): void
    {
        $bill = MonthlyBill::create([
            'customer_id' => $this->pelanggan('Warga Carbon', '3273010101010016')->id,
            'billing_period_year' => 2026,
            'billing_period_month' => 7,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 10_000,
            'abodemen' => 10_000,
            'penalty_amount' => 0,
            'total_amount' => 20_000,
            'status' => 'unpaid',
            'due_date' => '2026-07-27',
        ]);

        $this->assertInstanceOf(Carbon::class, $bill->fresh()->due_date);
    }
}
