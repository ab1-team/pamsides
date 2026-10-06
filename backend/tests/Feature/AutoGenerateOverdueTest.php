<?php

namespace Tests\Feature;

use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutoGenerateOverdueTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'admin'): User
    {
        // Pakai suffix random kecil supaya email selalu unik walau
        // dipanggil beberapa kali dalam satu test class.
        $uniq = uniqid('', true);

        return User::create([
            'name' => 'Test User',
            'email' => 'test-'.$role.'-'.substr($uniq, -6).'@pdam.test',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }

    #[Test]
    public function auto_generate_overdue_tanpa_auth_ditolak(): void
    {
        $response = $this->postJson('/api/dashboard/auto-generate-overdue');
        $response->assertStatus(401);
    }

    #[Test]
    public function auto_generate_overdue_oleh_pelanggan_ditolak(): void
    {
        $user = $this->createUser('pelanggan');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertStatus(403);
    }

    #[Test]
    public function auto_generate_overdue_tidak_jalan_jika_toleransi_nol(): void
    {
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => 0,
        ]);

        $user = $this->createUser('admin');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ran' => false,
            ]);
    }

    #[Test]
    public function auto_generate_overdue_tidak_jalan_jika_tanggal_tidak_cocok(): void
    {
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            // Set ke hari yang PASTI tidak cocok dengan hari ini.
            'toleransi_tunggakan' => ((int) now()->format('d') % 28) + 1,
        ]);

        $user = $this->createUser('admin');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ran' => false,
            ])
            ->assertJsonStructure(['reason']);
    }

    #[Test]
    public function auto_generate_overdue_idempotent_per_user_di_bulan_yang_sama(): void
    {
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            // Paksa cocok dengan hari ini agar command benar-benar jalan
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $user = $this->createUser('admin');

        // Panggilan ke-1 harus eksekusi (ran=true).
        $first = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');
        $first->assertOk();

        // Panggilan ke-2 harus skip (ran=false) karena cache per (bulan,user).
        $second = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');
        $second->assertOk()
            ->assertJson([
                'success' => true,
                'ran' => false,
            ])
            ->assertJsonStructure(['reason']);
    }

    #[Test]
    public function check_endpoint_melaporkan_will_run_tanpa_tergantung_sudah_jalan(): void
    {
        // `will_run` tidak boleh bergantung pada cache "sudah pernah jalan",
        // karena frontend memakainya untuk membuka popup di setiap login.
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $admin = $this->createUser('admin');
        $headers = $this->authHeaders($admin);

        $this->withHeaders($headers)
            ->postJson('/api/dashboard/auto-generate-overdue')
            ->assertOk();

        // Setelah generate pertama, `will_run` tetap true.
        $this->withHeaders($headers)
            ->getJson('/api/dashboard/auto-generate-overdue/check')
            ->assertOk()
            ->assertJsonPath('will_run', true)
            ->assertJsonPath('already_ran', false);
    }

    #[Test]
    public function auto_generate_overdue_tanpa_tagihan_overdue_masih_ran_true_namun_summary_nol(): void
    {
        // Set toleransi = hari ini, TAPI tidak ada tagihan overdue.
        // Endpoint harus tetap return success dengan summary=0.
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $user = $this->createUser('admin');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ran' => true,
            ])
            ->assertJsonStructure([
                'summary' => [
                    'tagihan_dengan_abodemen_tungakan',
                    'tagihan_dengan_pemakaian_tungakan',
                    'total_unpaid',
                    'total_overdue',
                ],
            ]);

        // Tanpa tagihan overdue -> semua summary harus 0.
        $response->assertJsonPath('summary.tagihan_dengan_abodemen_tungakan', 0)
            ->assertJsonPath('summary.tagihan_dengan_pemakaian_tungakan', 0)
            ->assertJsonPath('summary.total_unpaid', 0)
            ->assertJsonPath('summary.total_overdue', 0);

        // Tidak ada jurnal baru.
        $this->assertSame(0, Transaction::where('reverence_type', 'overdue_bill')->count());
    }

    #[Test]
    public function response_selalu_memiliki_field_wajib(): void
    {
        // Apapun kondisi SOP, response harus menyertakan field
        // wajib agar frontend bisa menampilkan pop up dengan benar.
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => 0, // non-trigger
        ]);

        $user = $this->createUser('admin');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'ran',
                'scheduled_day',
                'today_day',
                'date',
            ]);
    }

    #[Test]
    public function command_menghasilkan_format_keterangan_piutang_abodemen_dan_denda(): void
    {
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        // Buat admin dulu karena ticket.created_by mereferensinya.
        $adminUser = $this->createUser('admin');

        // Setup chain: package → ticket → customer → monthly bill overdue
        $packageId = \DB::table('installation_packages')->insertGetId([
            'name' => 'Paket Test',
            'installation_fee' => 0,
            'monthly_abodemen' => 10000,
            'late_penalty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticketId = \DB::table('installation_tickets')->insertGetId([
            'package_id' => $packageId,
            'applicant_name' => 'Fuji Riyanta',
            'nik' => '3321010101900001',
            'order_date' => now()->toDateString(),
            'address' => 'Alamat Test',
            'lat' => -7.1234567,
            'lng' => 110.1234567,
            'status' => 'completed',
            'created_by' => $adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // User dengan nama "Fuji Riyanta" (simulasi akun pelanggan).
        $customerUser = User::create([
            'name' => 'Fuji Riyanta',
            'email' => 'fuji.riyanta-'.uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $customerId = \DB::table('customers')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $customerUser->id,
            'customer_code' => '7349',
            'initial_meter_reading' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $periodMonth = max(1, (int) now()->format('m') - 1);
        MonthlyBill::create([
            'customer_id' => $customerId,
            'billing_period_year' => now()->year,
            'billing_period_month' => $periodMonth,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 50000,
            'abodemen' => 10000,
            'penalty_amount' => 0,
            'total_amount' => 60000,
            'status' => 'unpaid',
            // `due_date` harus PAST melewati ambang `today - toleransi_tunggakan`.
            // Test ini menyetel toleransi = tanggal hari ini, jadi ambangnya
            // bergeser mengikuti tanggal eksekusi. `subDays(5)` dulu dipakai
            // sebagai tebakan tetap dan langsung gagal pada tanggal 1-4:
            // ambang jatuh di bulan sebelumnya sehingga `subDays(5)` justru
            // LEBIH baru dari ambang, sehingga tagihan tidak terdeteksi
            // menunggak dan jurnal tidak pernah terbentuk.
            // `subMonths(1)->day(1)` menaruh due_date di awal bulan lalu,
            // yang pasti lebih kecil dari ambang berapa pun tanggal test jalan.
            'due_date' => now()->subMonths(1)->day(1)->toDateString(),
        ]);

        $response = $this->withHeaders($this->authHeaders($adminUser))
            ->postJson('/api/dashboard/auto-generate-overdue');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ran' => true,
            ]);

        // Keterangan harus mengandung "Piutang Abodemen bulan <long month> <year>
        // an. Fuji Riyanta (7349)" untuk jurnal 4.1.01.02.
        $monthsLong = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $expectedPeriod = $monthsLong[$periodMonth].' '.now()->year;
        $expectedSuffix = 'bulan '.$expectedPeriod.' an. Fuji Riyanta (7349)';

        $abodemenJurnal = Transaction::where('reverence_type', 'overdue_bill')
            ->where('account_kredit', '4.1.01.02')
            ->first();
        $this->assertNotNull($abodemenJurnal, 'Harus ada jurnal piutang Abodemen.');
        $this->assertSame(
            'Piutang Abodemen '.$expectedSuffix,
            $abodemenJurnal->keterangan_transaksi,
            'Format keterangan Abodemen harus "Piutang Abodemen <suffix>".'
        );

        // Keterangan harus mengandung "Piutang Denda bulan ... an. Fuji Riyanta (7349)"
        // untuk jurnal 4.1.01.03.
        $dendaJurnal = Transaction::where('reverence_type', 'overdue_bill')
            ->where('account_kredit', '4.1.01.03')
            ->first();
        $this->assertNotNull($dendaJurnal, 'Harus ada jurnal piutang Denda.');
        $this->assertSame(
            'Piutang Denda '.$expectedSuffix,
            $dendaJurnal->keterangan_transaksi,
            'Format keterangan Denda harus "Piutang Denda <suffix>".'
        );

        // Relasi harus berisi customer_code.
        $this->assertSame('7349', $abodemenJurnal->relasi);
        $this->assertSame('7349', $dendaJurnal->relasi);
    }

    #[Test]
    public function command_menggunakan_nama_dari_customer_user_jika_tersedia(): void
    {
        // Bila customer->user->name ada, gunakan itu.
        // (lihat Customer->user relasi).
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $adminUser = $this->createUser('admin');

        $packageId = \DB::table('installation_packages')->insertGetId([
            'name' => 'Paket Test',
            'installation_fee' => 0,
            'monthly_abodemen' => 10000,
            'late_penalty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticketId = \DB::table('installation_tickets')->insertGetId([
            'package_id' => $packageId,
            'applicant_name' => 'Nama di Tiket', // berbeda
            'nik' => '3321010101900002',
            'order_date' => now()->toDateString(),
            'address' => 'Alamat Test',
            'lat' => -7.1234567,
            'lng' => 110.1234567,
            'status' => 'completed',
            'created_by' => $adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerUser = User::create([
            'name' => 'Nama di User', // sumber utama
            'email' => 'nama.user-'.uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $customerId = \DB::table('customers')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-X',
            'initial_meter_reading' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        MonthlyBill::create([
            'customer_id' => $customerId,
            'billing_period_year' => now()->year,
            'billing_period_month' => max(1, (int) now()->format('m') - 1),
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 50000,
            'abodemen' => 10000,
            'penalty_amount' => 0,
            'total_amount' => 60000,
            'status' => 'unpaid',
            // `due_date` harus PAST melewati ambang `today - toleransi_tunggakan`.
            // Test ini menyetel toleransi = tanggal hari ini, jadi ambangnya
            // bergeser mengikuti tanggal eksekusi. `subDays(5)` dulu dipakai
            // sebagai tebakan tetap dan langsung gagal pada tanggal 1-4:
            // ambang jatuh di bulan sebelumnya sehingga `subDays(5)` justru
            // LEBIH baru dari ambang, sehingga tagihan tidak terdeteksi
            // menunggak dan jurnal tidak pernah terbentuk.
            // `subMonths(1)->day(1)` menaruh due_date di awal bulan lalu,
            // yang pasti lebih kecil dari ambang berapa pun tanggal test jalan.
            'due_date' => now()->subMonths(1)->day(1)->toDateString(),
        ]);

        $this->withHeaders($this->authHeaders($adminUser))
            ->postJson('/api/dashboard/auto-generate-overdue')
            ->assertOk();

        $jurnal = Transaction::where('reverence_type', 'overdue_bill')
            ->where('account_kredit', '4.1.01.02')
            ->first();
        $this->assertNotNull($jurnal);
        $this->assertStringContainsString('Nama di User', $jurnal->keterangan_transaksi);
        $this->assertStringNotContainsString('Nama di Tiket', $jurnal->keterangan_transaksi);
    }
}
