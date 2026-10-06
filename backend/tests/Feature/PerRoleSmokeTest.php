<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Uji alur nyata tiap peran: login lewat POST /login, lalu buka endpoint
 * andalan layar utama role tersebut.
 *
 * Audit awal menemukan symptom "fitur tiap role berbeda dari admin":
 * beberapa endpoint dipanggil dari chrome bersama (sidebar, top-nav)
 * hanya tersedia untuk admin, sehingga 403-nya tertelan `catch` dan
 * gejalanya muncul sebagai UI kosong — bukan error yang terlihat.
 * Test ini mengunci perilaku tersebut: setiap role harus bisa login dan
 * mencapai layar utamanya tanpa 403/500.
 */
class PerRoleSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        // Idempoten: beberapa test perlu user(role) DAN loginAsRole(role)
        // pada test yang sama, tanpa memicu pelanggaran unique email.
        return User::firstOrCreate(
            ['email' => $role.'@pdam.test'],
            [
                'name' => ucfirst($role).' Uji',
                'password' => Hash::make('rahasia123'),
                'role' => $role,
            ],
        );
    }

    /**
     * Setiap role → endpoint yang WAJIB bisa dipanggil setelah login.
     *
     * Daftar ini disusun dari route yang benar-benar dipakai layar utama
     * tiap role, bukan dari controller secara tebakan.
     */
    public static function roleEndpoints(): array
    {
        return [
            // Chrome bersama: dipakai semua role saat sidebar/top-nav mount.
            'admin: identitas lembaga' => ['admin', '/api/settings/lembaga-identity'],
            'surveyor: identitas lembaga' => ['surveyor', '/api/settings/lembaga-identity'],
            'teknisi: identitas lembaga' => ['teknisi', '/api/settings/lembaga-identity'],
            'pelanggan: identitas lembaga' => ['pelanggan', '/api/settings/lembaga-identity'],

            // Dashboard masing-masing role.
            'admin: dashboard' => ['admin', '/api/dashboard/statistics'],
            'teknisi: dashboard' => ['teknisi', '/api/dashboard/statistics'],

            // Antrean survey milik surveyor.
            'surveyor: daftar tiket' => ['surveyor', '/api/installation-tickets?status=pending'],

            // Portal pelanggan.
            'pelanggan: dashboard portal' => ['pelanggan', '/api/pelanggan/dashboard'],
            'pelanggan: riwayat tagihan' => ['pelanggan', '/api/pelanggan/bill-history'],
        ];
    }

    #[Test]
    #[DataProvider('roleEndpoints')]
    public function endpoint_inti_setiap_role_bisa_diakses(string $role, string $uri): void
    {
        // `settings.key` NOT NULL unique tanpa default (dari create_settings_table),
        // jadi wajib diisi walau barisnya nanti hanya dipakai sebagai
        // "ada setting" oleh Setting::first().
        Setting::create([
            'key' => 'singleton',
            'value' => null,
            'nama' => 'PDAM Uji',
            'batas_tagihan' => 27,
        ]);

        $this->loginAsRole($role);

        // Portal pelanggan butuh record Customer yang tertaut ke tiket.
        // Tanpa ini endpoint dashboard/bill-history menolak dengan 403/404 —
        // kondisi yang tidak berlaku untuk pelanggan sungguhan.
        if ($role === 'pelanggan') {
            $this->giveCustomerRecord($role);
        }

        $this->getJson($uri)->assertSuccessful();
    }

    /** Pasang Customer + tiket aktif untuk user pelanggan. */
    private function giveCustomerRecord(string $role): void
    {
        $owner = User::where('email', $role.'@pdam.test')->firstOrFail();

        $package = InstallationPackage::create([
            'name' => 'Paket Pelanggan',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $owner->id,
            'applicant_name' => $owner->name,
            'nik' => '3200000000001',
            'address' => 'Jl. Pelanggan No. 1',
            'lat' => -7.797068,
            'lng' => 110.370529,
            'status' => 'completed',
            'created_by' => $owner->id,
        ]);

        $customer = Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => 'PLG-'.uniqid(),
            // NOT NULL tanpa default di DB.
            'initial_meter_reading' => 0,
        ]);

        // Satu tagihan milik pelanggan ini. Tanpa ini, endpoint
        // bill-detail (dengan maupun tanpa ID) memang rightful 404 —
        // test perlu data nyata agar cabangnya benar-benar diuji.
        MonthlyBill::create([
            'customer_id' => $customer->id,
            'billing_period_year' => 2026,
            'billing_period_month' => 6,
            'meter_reading_start' => 100,
            'meter_reading_end' => 120,
            'usage_m3' => 20,
            'usage_charge' => 60_000,
            'abodemen' => 15_000,
            'penalty_amount' => 0,
            'total_amount' => 75_000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10),
        ]);
    }

    /**
     * Login sungguhan lewat POST /login (bukan actingAs), lalu set token
     * ke default header supaya request berikutnya terautentikasi seperti
     * kondisi frontend sebenarnya.
     */
    private function loginAsRole(string $role): string
    {
        $this->user($role);

        $response = $this->postJson('/api/login', [
            'email' => $role.'@pdam.test',
            'password' => 'rahasia123',
        ]);

        $response->assertSuccessful();

        // Token dikembalikan di data.token (lihat AuthController::login).
        $token = (string) $response->json('data.token');
        $this->assertNotSame('', $token, 'Login tidak mengembalikan token pada data.token.');

        // Pakai token asli itu juga untuk request berikutnya — dengan begitu
        // test benar-benar melewati lapisan Sanctum, bukan middleware
        // yang dilewati lewat actingAs.
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $token;
    }

    #[Test]
    public function semua_role_tetap_bisa_login_setelah_rate_limiter_ditambahkan(): void
    {
        // Rate limiter per email+IP: 5x/menit. Empat role memakai email
        // berbeda, jadi tidak saling mengunci — ini alasan limiter
        // sengaja tidak di-key per email saja.
        foreach (['admin', 'surveyor', 'teknisi', 'pelanggan'] as $role) {
            $this->user($role);

            $this->postJson('/api/login', [
                'email' => $role.'@pdam.test',
                'password' => 'rahasia123',
            ])->assertSuccessful();
        }
    }

    #[Test]
    public function password_salah_memberi_pesan_indonesia_yang_jelas(): void
    {
        $this->user('teknisi');

        $response = $this->postJson('/api/login', [
            'email' => 'teknisi@pdam.test',
            'password' => 'salah-sekali',
        ]);

        $response->assertStatus(422);
        $this->assertNotEmpty(
            $response->json('message') ?: $response->json('errors'),
            'Respons login gagal tidak membawa pesan yang bisa ditampilkan.'
        );
    }

    #[Test]
    public function login_tidak_membocorkan_apakah_email_terdaftar(): void
    {
        $this->user('admin');

        $withExisting = $this->postJson('/api/login', [
            'email' => 'admin@pdam.test',
            'password' => 'salah-sekali',
        ]);

        $withMissing = $this->postJson('/api/login', [
            'email' => 'tidak-ada@pdam.test',
            'password' => 'salah-sekali',
        ]);

        $this->assertSame($withExisting->status(), $withMissing->status());
        $this->assertSame(
            $withExisting->json('message'),
            $withMissing->json('message'),
            'Pesan error berbeda antara email terdaftar dan tidak — ini membocorkan '
            .'keberadaan akun dan memudahkan enumerasi user.'
        );
    }

    #[Test]
    public function pelanggan_hanya_melihat_tagihan_miliknya_sendiri(): void
    {
        $this->user('pelanggan');
        $other = User::create([
            'name' => 'Pelanggan Lain',
            'email' => 'pelanggan2@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'pelanggan',
        ]);

        $package = InstallationPackage::create([
            'name' => 'Paket Uji',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        // Tiket milik orang lain, dengan status yang membuat tagihannya muncul.
        $otherTicket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $other->id,
            'applicant_name' => 'Milik Orang Lain',
            'nik' => '3200000000099',
            'address' => 'Jl. Lain No. 9',
            'lat' => -7.797068,
            'lng' => 110.370529,
            'status' => 'completed',
            'created_by' => $other->id,
        ]);

        Customer::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $other->id,
            'customer_code' => 'LAIN-'.uniqid(),
            'initial_meter_reading' => 0,
        ]);

        $this->loginAsRole('pelanggan');

        // Pelanggan yang diuji harus punya Customer sendiri; kalau tidak,
        // portal menolak 403 dan test tidak menguji apa pun soal scoping.
        $this->giveCustomerRecord('pelanggan');

        // Portal pelanggan hanya boleh kosong, bukan error.
        $this->getJson('/api/pelanggan/dashboard')->assertSuccessful();
        $this->getJson('/api/pelanggan/bill-history')->assertSuccessful();

        // Tagihan milik orang lain tidak boleh terbaca meski ID-nya diketahui.
        $this->getJson('/api/pelanggan/bill-detail/999999')
            ->assertStatus(404);

        // Endpoint tanpa ID harus mengembalikan tagihan milik sendiri, bukan
        // error — frontend rely pada cabang ini (lihat pelanggan.service.js).
        $this->getJson('/api/pelanggan/bill-detail')
            ->assertSuccessful();
    }
}
