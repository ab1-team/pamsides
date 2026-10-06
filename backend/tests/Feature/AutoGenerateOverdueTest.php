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

    /**
     * Buat SATU tagihan unpaid yang sudah melewati ambang toleransi.
     *
     * Rantai wajib dibuat lengkap: package → ticket → user pelanggan →
     * customer → monthly_bill. `generateForCustomer` bukan yang dipakai
     * di sini; tagihan disisipkan langsung supaya test tidak bergantung
     * pada logika penagihan.
     *
     * `due_date` diambil dari bulan sebelumnya tanggal 1, yang secara
     * praktis selalu lebih kecil dari ambang `today - toleransi` hari —
     * inilah yang membuat tagihan dianggap menunggak.
     */
    private function makeOverdueBill(User $adminUser, array $amounts = []): MonthlyBill
    {
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
            'applicant_name' => 'Pelanggan Test '.uniqid(),
            'nik' => '33210101019'.random_int(10000, 99999),
            'order_date' => now()->toDateString(),
            'address' => 'Alamat Test',
            'lat' => -7.1234567,
            'lng' => 110.1234567,
            'status' => 'completed',
            'created_by' => $adminUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pelanggan = User::create([
            'name' => 'Pelanggan '.uniqid(),
            'email' => 'pelanggan-'.uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $customerId = \DB::table('customers')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $pelanggan->id,
            'customer_code' => (string) random_int(10000, 99999),
            'initial_meter_reading' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $period = now()->subMonths(2);
        $abodemen = (float) ($amounts['abodemen'] ?? 10000);
        $usage = (float) ($amounts['usage_charge'] ?? 50000);

        return MonthlyBill::create([
            'customer_id' => $customerId,
            'billing_period_year' => (int) $period->format('Y'),
            'billing_period_month' => (int) $period->format('n'),
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => $usage,
            'abodemen' => $abodemen,
            'penalty_amount' => 0,
            'total_amount' => $abodemen + $usage,
            'status' => 'unpaid',
            'due_date' => $period->copy()->day(1)->toDateString(),
        ]);
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
    public function summary_melaporkan_hanya_tagihan_yang_sungguhan_dijurnal(): void
    {
        // `$targets` pernah lebih longgar dari syarat di loop: tagihan yang
        // jurnal abodemenNYA sudah ada tapi tidak punya usage_charge tetap
        // ikut terhitung. Akibatnya command melaporkan "berhasil memproses
        // N tagihan" padahal tidak satu baris pun ter-insert, dan popup
        // menampilkan angka yang tidak pernah terjadi.
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $admin = $this->createUser('admin');

        // Tanpa `usage_charge`, jadi hanya satu jenis jurnal yang mungkin.
        $this->makeOverdueBill($admin, ['abodemen' => 10000, 'usage_charge' => 0]);

        $response = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/dashboard/auto-generate-overdue');
        $response->assertOk()->assertJsonPath('ran', true);

        $diproses = (int) $response->json('summary.tagihan_diproses');
        $jurnal = Transaction::where('reverence_type', 'overdue_bill')->count();

        $this->assertSame(
            $jurnal > 0 ? 1 : 0,
            $diproses,
            'tagihan_diproses harus mencerminkan jurnal yang benar-benar dibuat'
        );

        // Jalankan lagi: tidak ada jurnal baru, jadi reported processed
        // harus 0 — bukan 1.
        $second = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/dashboard/auto-generate-overdue');
        $second->assertOk();

        $this->assertSame(0, (int) $second->json('summary.tagihan_diproses'),
            'run kedua tidak boleh melaporkan tagihan diproses padahal jurnal tidak bertambah');
    }

    #[Test]
    public function generate_berulang_tetap_aman_dan_tidak_membuat_jurnal_ganda(): void
    {
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $admin = $this->createUser('admin');
        $this->makeOverdueBill($admin, ['abodemen' => 10000, 'usage_charge' => 5000]);

        $headers = $this->authHeaders($admin);

        // Panggilan ke-1: command benar-benar membuat jurnal.
        $first = $this->withHeaders($headers)
            ->postJson('/api/dashboard/auto-generate-overdue');
        $first->assertOk()->assertJsonPath('ran', true);

        $afterFirst = Transaction::where('reverence_type', 'overdue_bill')->count();
        $this->assertGreaterThan(0, $afterFirst, 'generate pertama harus membuat jurnal');

        // Panggilan ke-2 pada HARI YANG SAMA harus tetap jalan ulang
        // (permintaan: "setiap login di tanggal itu dihitung ulang"), bukan
        // ditolak karena sudah pernah jalan.
        $second = $this->withHeaders($headers)
            ->postJson('/api/dashboard/auto-generate-overdue');
        $second->assertOk()->assertJsonPath('ran', true);

        // tetapi jumlah jurnal TIDAK boleh bertambah: tagihan yang sama
        // tidak boleh dibuat dua kali.
        $afterSecond = Transaction::where('reverence_type', 'overdue_bill')->count();
        $this->assertSame(
            $afterFirst,
            $afterSecond,
            'generate ulang tidak boleh menambah baris transaksi (data ganda)'
        );

        // Tidak boleh ada pasangan (tagihan, akun kredit) yang dobel.
        $dupes = Transaction::where('reverence_type', 'overdue_bill')
            ->select('reverence_id', 'account_kredit')
            ->groupBy('reverence_id', 'account_kredit')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        $this->assertCount(0, $dupes, 'tidak boleh ada journal duplikat per tagihan+akun');
    }

    #[Test]
    public function jurnal_pemakaian_boleh_dibuat_walau_jurnal_abodemen_sudah_ada(): void
    {
        // Dulu deduplikasi hanya per TAGIHAN. Kalau abodemen sudah punya
        // jurnal tapi pemakaian belum, pemakaiannya tidak akan pernah
        // dibuat — padahal command dijalankan ulang di setiap login.
        Setting::create([
            'key' => 'sop',
            'batas_tagihan' => 27,
            'toleransi_tunggakan' => (int) now()->format('d'),
        ]);

        $admin = $this->createUser('admin');
        $bill = $this->makeOverdueBill($admin, ['abodemen' => 10000, 'usage_charge' => 5000]);

        // Simulasikan jurnal abodemen yang sudah ada.
        Transaction::create([
            'tgl_transaksi' => now()->subDays(10)->toDateString(),
            'account_debet' => '1.1.03.01',
            'account_kredit' => '4.1.01.02',
            'reverence_type' => 'overdue_bill',
            'reverence_id' => $bill->id,
            'keterangan_transaksi' => 'Piutang Abodemen (manual)',
            'relasi' => 'X',
            'saldo' => 10000,
            'id_user' => $admin->id,
            'urutan' => 1,
        ]);

        $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/dashboard/auto-generate-overdue')
            ->assertOk();

        // Jurnal pemakaian HARUS ikut dibuat walau abodemen sudah ada.
        $this->assertSame(1, Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->where('account_kredit', '4.1.01.03')
            ->count(), 'jurnal pemakaian harus dibuat meski abodemen sudah ada');

        // Dan abodemen-nya tidak boleh digandakan.
        $this->assertSame(1, Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->where('account_kredit', '4.1.01.02')
            ->count(), 'jurnal abodemen tidak boleh digandakan');
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

        // Tagihan dibuat untuk bulan SEBELUM `toleransi_tunggakan`, dengan
        // jatuh tempo SUDAH lewat ambang toleransi.
        //
        // `toleransi_tunggakan` = TANGGAL generate (1-28). Tagihan dianggap
        // menunggak bila `due_date`-nya sudah DI SEBELAH hari toleransi di
        // bulan pemakaiannya. Jadi agar benar-benar menunggak, tagihan ini
        // harus ber periode bulan sebelumnya DAN jatuh tempo sebelum tanggal
        // toleransi di bulan itu.
        //
        // Contoh (toleransi = 6): tagihan periode September jatuh tempo
        // 1 September → sudah lewat 6 September? Tidak. Karena itu test
        // memakai `subMonths(1)->day(1)` agar due_date = 1 Sep < 6 Sep.
        $periodDate = now()->subMonths(1);
        $periodMonth = (int) $periodDate->format('n');
        MonthlyBill::create([
            'customer_id' => $customerId,
            'billing_period_year' => (int) $periodDate->format('Y'),
            'billing_period_month' => $periodMonth,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 50000,
            'abodemen' => 10000,
            'penalty_amount' => 0,
            'total_amount' => 60000,
            'status' => 'unpaid',
            'due_date' => $periodDate->copy()->day(1)->toDateString(),
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
        // Tahun ikut periode tagihan, bukan tahun hari ini — `subMonths(1)`
        // bisa melompati tahun baru (mis. 1 Januari → Desember tahun lalu).
        $expectedPeriod = $monthsLong[$periodMonth].' '.(int) $periodDate->format('Y');
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

        // Sama seperti test sebelumnya: periode bulan sebelumnya dengan
        // jatuh tempo tanggal 1, supaya sudah melewati ambang toleransi.
        $periodDate = now()->subMonths(1);
        MonthlyBill::create([
            'customer_id' => $customerId,
            'billing_period_year' => (int) $periodDate->format('Y'),
            'billing_period_month' => (int) $periodDate->format('n'),
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 50000,
            'abodemen' => 10000,
            'penalty_amount' => 0,
            'total_amount' => 60000,
            'status' => 'unpaid',
            'due_date' => $periodDate->copy()->day(1)->toDateString(),
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
