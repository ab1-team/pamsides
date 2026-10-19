<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * B9b — `import:bill-payments` WAJIB menautkan instalasi legacy ke tiket lewat
 * `installation_tickets.legacy_installation_id`, bukan triple nama|desa|paket.
 *
 * Dokumen sumber:
 *   - docs/database.md §10 `bill_payments`: `bill_id` FK → `monthly_bills.id`,
 *     `amount_paid`, `confirmed_by`, `paid_at`. Tidak ada kolom baru.
 *   - docs/database.md §9 `monthly_bills`: tagihan dicari lewat kunci bisnis
 *     `customer_id|billing_period_year|billing_period_month`.
 *   - docs/api_backend.md §Auth: konvensi respons `{success, data}` — tidak
 *     berlaku di sini karena command CLI hanya mencetak ringkasan teks.
 *
 * Skenario cacat produksi:
 *   Dua instalasi legacy BERBEDA dengan pelanggan bernama SAMA, di desa yang
 *   sama, paket yang sama. Kunci triple memetakan keduanya ke SATU tiket
 *   (instalasi kedua hilang), sehingga pembayarannya tertempel ke tagihan
 *   pelanggan pertama. Dengan anchor legacy id, tiap instalasi menemukan
 *   tiket & tagihannya sendiri.
 */
class LegacyBillPaymentsIdentityTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureDb = null;

    /** Instalasi A: legacy id 601, pelanggan legacy 9101, status A. */
    private const INST_A = 601;

    private const CUST_A = 9101;

    /** Instalasi B: legacy id 602, pelanggan legacy 9102, status A (nama/desa/paket identik). */
    private const INST_B = 602;

    private const CUST_B = 9102;

    /** Instalasi C: legacy id 603, status NON-A — tetap harus tertaut (filter status lama dibuang). */
    private const INST_C = 603;

    private const CUST_C = 9103;

    #[Test]
    public function dua_instalasi_nama_desa_paket_identik_menghasilkan_tagihan_masing_masing(): void
    {
        $this->seedTargets();
        $this->seedLegacy();

        // Tagihan periode yang sama untuk DUA pelanggan berbeda.
        $billA = $this->insertBill(self::CUST_A, 2024, 3, 50_000);
        $billB = $this->insertBill(self::CUST_B, 2024, 3, 75_000);

        $exit = Artisan::call('import:bill-payments', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exit, 'import:bill-payments harus sukses');
        $output = Artisan::output();

        // Tiap tiket punya BARIS pelanggan sendiri -> tiap tiket punya tagihan sendiri.
        $payments = DB::table('bill_payments')->orderBy('bill_id')->get(['bill_id', 'amount_paid']);
        $this->assertCount(2, $payments, 'dua instalasi berbeda harus menghasilkan dua pembayaran terpisah');
        $this->assertSame(
            [$billA, $billB],
            $payments->pluck('bill_id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            'pembayaran instalasi A harus ke tagihan A dan instalasi B ke tagihan B'
        );

        // Tagihan A hanya menerima trx instalasi A; B hanya trx instalasi B.
        // Kalau kedua instalasi tertaut ke tiket yang sama, salah satu tagihan
        // PERGI tanpa pembayaran (atau menerima dua pembayaran).
        $this->assertEqualsWithDelta(
            12_000.0,
            (float) DB::table('bill_payments')->where('bill_id', $billA)->sum('amount_paid'),
            0.001,
            'nominal tagihan A = total trx instalasi A (10.000 + 2.000)'
        );
        $this->assertEqualsWithDelta(
            34_000.0,
            (float) DB::table('bill_payments')->where('bill_id', $billB)->sum('amount_paid'),
            0.001,
            'nominal tagihan B = total trx instalasi B'
        );

        // Ringkasan angka akhir wajib muncul (brief C2 poin 5).
        // A & B punya tagihan; hanya usage C (fixture tanpa tagihan) yang
        // tercatat "usage tanpa tagihan".
        $this->assertMatchesRegularExpression('/inst→ticket: \d+/', $output);
        $this->assertStringContainsString('usage tanpa tiket: 0', $output);
        $this->assertStringContainsString('usage tanpa tagihan: 1', $output);
    }

    #[Test]
    public function instalasi_status_non_a_tetap_tertaut_lewat_legacy_installation_id(): void
    {
        $this->seedTargets();
        $this->seedLegacy();

        $billC = $this->insertBill(self::CUST_C, 2024, 3, 20_000);

        $exit = Artisan::call('import:bill-payments', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exit);

        // Filter ->where('status','A') lama akan membuang instalasi ini.
        $this->assertSame(
            1,
            DB::table('bill_payments')->where('bill_id', $billC)->count(),
            'instalasi legacy berstatus non-A tetap harus tertaut via legacy_installation_id'
        );

        // Tidak tersangkut di "usage tanpa tiket"; satu-satunya usage tanpa
        // tagihan adalah C (tidak ada transaksi, jadi tidak ada pembayaran baru).
        $output = Artisan::output();
        $this->assertStringContainsString('usage tanpa tiket: 0', $output);
        // Instalasi C tertaut ke tiket & tagihannya sendiri (bukan ke tiket
        // lain); hanya usage A & B yang tanpa tagihan -- C TIDAK ikut.
        $this->assertStringContainsString('usage tanpa tagihan: 2', $output);
    }

    #[Test]
    public function impor_ulang_tidak_menggandakan_bill_payments(): void
    {
        $this->seedTargets();
        $this->seedLegacy();

        $this->insertBill(self::CUST_A, 2024, 3, 50_000);
        $this->insertBill(self::CUST_B, 2024, 3, 75_000);

        Artisan::call('import:bill-payments', ['--force' => true, '--business' => 5]);
        $first = DB::table('bill_payments')->orderBy('id')->get(['bill_id', 'amount_paid', 'paid_at'])
            ->map(fn ($r) => [(int) $r->bill_id, (float) $r->amount_paid, (string) $r->paid_at])->all();
        $this->assertCount(2, $first);

        // Run kedua: harus update (tanpa insert baru), hasil identik.
        $exit2 = Artisan::call('import:bill-payments', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exit2);

        $second = DB::table('bill_payments')->orderBy('id')->get(['bill_id', 'amount_paid', 'paid_at'])
            ->map(fn ($r) => [(int) $r->bill_id, (float) $r->amount_paid, (string) $r->paid_at])->all();
        $this->assertSame($first, $second, 'run kedua tidak boleh mengubah/menggandakan bill_payments');
        $this->assertSame(2, DB::table('bill_payments')->count(), 'bill_payments tidak boleh dobel');
    }

    // =========================================================
    // Fixture target DB baru
    // =========================================================

    private function seedTargets(): void
    {
        // users.business_id / jabatan_id nullable; satu teknisi + satu admin.
        $this->admin = User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-bp@pdam.test',
            'password' => bcrypt('rahasia123'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'Teknisi Uji',
            'email' => 'teknisi-bp@pdam.test',
            'password' => bcrypt('rahasia123'),
            'role' => 'teknisi',
        ]);

        $this->villageId = (int) DB::table('villages')->insertGetId([
            'village_name' => 'Sukamaju',
            'hamlet_name' => 'Dusun A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->packageId = (int) DB::table('installation_packages')->insertGetId([
            'name' => 'R1 (B5)',
            'installation_fee' => 500_000,
            'monthly_abodemen' => 20_000,
            'late_penalty' => 5_000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tiga tiket (satu per instalasi legacy), nama & desa & paket SAMA untuk A/B.
        foreach ([self::INST_A, self::INST_B, self::INST_C] as $i => $legacyInstId) {
            $ticketId = (int) DB::table('installation_tickets')->insertGetId([
                'legacy_installation_id' => $legacyInstId,
                'package_id' => $this->packageId,
                'applicant_name' => 'Sunarto',
                'nik' => '320101010101'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'address' => 'Kp. A',
                'lat' => 0,
                'lng' => 0,
                'status' => 'pending',
                'village_id' => $this->villageId,
                'created_by' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('customers')->insert([
                'ticket_id' => $ticketId,
                'user_id' => $this->admin->id,
                'customer_code' => 'INS-'.$legacyInstId,
                'initial_meter_reading' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private User $admin;

    private int $villageId;

    private int $packageId;

    private function insertBill(int $legacyCustomerId, int $year, int $month, float $total): int
    {
        $localCustomerId = (int) DB::table('customers')->where('customer_code', 'INS-'.($legacyCustomerId - 8500))->value('id');
        $this->assertGreaterThan(0, $localCustomerId, 'baris customers fixture harus ada');

        return (int) DB::table('monthly_bills')->insertGetId([
            'customer_id' => $localCustomerId,
            'billing_period_year' => $year,
            'billing_period_month' => $month,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => $total,
            'abodemen' => 0,
            'penalty_amount' => 0,
            'total_amount' => $total,
            'status' => 'unpaid',
            'due_date' => sprintf('%04d-%02d-20', $year, $month),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================
    // Fixture DB legacy sementara
    // =========================================================

    private function seedLegacy(): void
    {
        $mysql = config('database.connections.mysql');
        $this->fixtureDb = 'pamsides_bp_fixture_'.getmypid();

        $server = new \PDO(
            "mysql:host={$mysql['host']};port={$mysql['port']}",
            $mysql['username'],
            $mysql['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$this->fixtureDb}` DEFAULT CHARACTER SET utf8mb4");

        config(['database.connections.legacy' => array_merge($mysql, [
            'host' => $mysql['host'],
            'port' => $mysql['port'],
            'database' => $this->fixtureDb,
            'username' => $mysql['username'],
            'password' => $mysql['password'],
        ])]);
        DB::purge('legacy');

        $schema = DB::connection('legacy')->getSchemaBuilder();
        $schema->create('users', function ($t) {
            $t->bigIncrements('id');
            $t->string('nama')->nullable();
            $t->integer('jabatan')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });
        $schema->create('packages', function ($t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->nullable();
            $t->string('kelas')->nullable();
        });
        $schema->create('villages', function ($t) {
            $t->bigIncrements('id');
            $t->string('nama')->nullable();
            $t->string('dusun')->nullable();
        });
        $schema->create('customers', function ($t) {
            $t->bigIncrements('id');
            $t->string('nama')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });
        $schema->create('installations', function ($t) {
            $t->bigIncrements('id');
            $t->string('kode_instalasi')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('package_id')->nullable();
            $t->unsignedBigInteger('desa')->nullable();
            $t->string('status')->nullable();
        });
        $schema->create('usages', function ($t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('id_instalasi')->nullable();
            $t->date('tgl_pemakaian')->nullable();
            $t->unsignedBigInteger('customer')->nullable();
            $t->integer('pakai')->nullable();
            $t->string('bulan')->nullable();
            $t->string('tahun')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });
        $schema->create('transactions', function ($t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('usage_id')->nullable();
            $t->string('tgl_transaksi')->nullable();
            $t->string('keterangan')->nullable();
            $t->integer('total')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });

        $L = DB::connection('legacy');
        $L->table('users')->insert([
            ['id' => 7001, 'nama' => 'Admin Uji', 'jabatan' => 1, 'business_id' => 5],
        ]);
        $L->table('packages')->insert(['id' => 11, 'business_id' => 5, 'kelas' => 'R1']);
        $L->table('villages')->insert(['id' => 101, 'nama' => 'Sukamaju', 'dusun' => 'Dusun A']);

        // NAMA SAMA + desa sama + paket sama untuk A dan B.
        $L->table('customers')->insert([
            ['id' => self::CUST_A, 'nama' => 'Sunarto', 'business_id' => 5],
            ['id' => self::CUST_B, 'nama' => 'Sunarto', 'business_id' => 5],
            ['id' => self::CUST_C, 'nama' => 'Sunarto', 'business_id' => 5],
        ]);
        $L->table('installations')->insert([
            ['id' => self::INST_A, 'kode_instalasi' => 'INS-601', 'customer_id' => self::CUST_A, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => 'A'],
            ['id' => self::INST_B, 'kode_instalasi' => 'INS-602', 'customer_id' => self::CUST_B, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => 'A'],
            ['id' => self::INST_C, 'kode_instalasi' => 'INS-603', 'customer_id' => self::CUST_C, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => 'N'],
        ]);

        $L->table('usages')->insert([
            ['id' => 8001, 'id_instalasi' => self::INST_A, 'tgl_pemakaian' => '2024-03-10', 'customer' => self::CUST_A, 'business_id' => 5],
            ['id' => 8002, 'id_instalasi' => self::INST_B, 'tgl_pemakaian' => '2024-03-10', 'customer' => self::CUST_B, 'business_id' => 5],
            ['id' => 8003, 'id_instalasi' => self::INST_C, 'tgl_pemakaian' => '2024-03-10', 'customer' => self::CUST_C, 'business_id' => 5],
        ]);
        $L->table('transactions')->insert([
            // Grup A: dua baris tanggal sama -> satu event (12.000).
            ['id' => 9001, 'usage_id' => 8001, 'tgl_transaksi' => '2024-04-05', 'keterangan' => 'Bayar tagihan', 'total' => 10_000, 'user_id' => 7001, 'business_id' => 5],
            ['id' => 9002, 'usage_id' => 8001, 'tgl_transaksi' => '2024-04-05', 'keterangan' => 'Pendapatan denda', 'total' => 2_000, 'user_id' => 7001, 'business_id' => 5],
            // Grup B: satu baris (34.000).
            ['id' => 9003, 'usage_id' => 8002, 'tgl_transaksi' => '2024-04-06', 'keterangan' => 'Bayar tagihan', 'total' => 34_000, 'user_id' => 7001, 'business_id' => 5],
            // Grup C: instalasi berstatus non-A.
            ['id' => 9004, 'usage_id' => 8003, 'tgl_transaksi' => '2024-04-07', 'keterangan' => 'Bayar tagihan', 'total' => 20_000, 'user_id' => 7001, 'business_id' => 5],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->fixtureDb !== null) {
            try {
                $mysql = config('database.connections.mysql');
                $server = new \PDO(
                    "mysql:host={$mysql['host']};port={$mysql['port']}",
                    $mysql['username'],
                    $mysql['password'],
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );
                $server->exec("DROP DATABASE IF EXISTS `{$this->fixtureDb}`");
            } catch (\Throwable) {
                // best effort cleanup
            }
        }

        parent::tearDown();
    }
}
