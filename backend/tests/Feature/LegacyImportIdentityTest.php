<?php

namespace Tests\Feature;

use App\Console\Commands\LegacyMigrateFastCommand;
use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\User;
use App\Support\LegacyIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regresi "pelanggan / tiket hilang" pada migrasi legacy (business 5 Tirtomulo).
 *
 * Dua cacat yang dijaga di sini:
 *
 *   1. importer memetakan beberapa pelanggan legazy bernama sama ke SATU tiket
 *      (kunci lemah package|village|nama|nik), sehingga tiket + baris customers
 *      pelanggan lain hilang. Kasus nyata: 3 pelanggan "Sunarto" -> 1 baris.
 *
 *   2. email akun user pelanggan = slug(nama) saja, sehingga pelanggan berbeda
 *      dengan nama sama berbagi satu akun user (~191 tabrakan).
 *
 * Yang diuji:
 *   - 3 test UNIT murni `App\Support\LegacyIdentity` + 1 test wiring rantai
 *     `LegacyMigrateFastCommand` (tanpa DB, tanpa legacy).
 *   - 2 test IMPORTER yang menjalankan `import:tickets` + `import:customers`
 *     terhadap DB legacy fixture sementara (dibuat/dibuang test ini sendiri,
 *     TIDAK menyentuh DB legacy produksi).
 */
class LegacyImportIdentityTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureDb = null;

    /**
     * Nilai-nilai fixture. Dua instalasi berbeda, DUA pelanggan bernama SAMA
     * ("Sunarto") di DESA berbeda -> wajib jadi 2 tiket & 2 baris customers.
     */
    private const SUNARTO_A = ['inst' => 501, 'cust' => 9001, 'village' => 'Sukamaju', 'hamlet' => 'Dusun A', 'kode' => 'INS-501'];

    private const SUNARTO_B = ['inst' => 502, 'cust' => 9002, 'village' => 'Sukamakmur', 'hamlet' => 'Dusun B', 'kode' => 'INS-502'];

    // =========================================================
    // UNIT: LegacyIdentity (tanpa DB / tanpa DB legacy)
    // =========================================================

    #[Test]
    public function ticket_key_memakai_legacy_installation_id_saat_tersedia(): void
    {
        // Baris dengan pasangan instalasi legacy -> kunci KUAT.
        $row = [
            'legacy_installation_id' => 4242,
            'package_id' => 7,
            'village_id' => 3,
            'applicant_name' => 'Sunarto',
            'nik' => '3201010101010001',
        ];

        $key = LegacyIdentity::ticketKey($row);

        $this->assertTrue($key['primary'], 'legacy_installation_id harus memakai kunci utama');
        $this->assertSame('legacy:4242', $key['key']);
    }

    #[Test]
    public function ticket_key_jatuh_ke_kunci_lemah_saat_legacy_id_null(): void
    {
        $row = [
            'legacy_installation_id' => null,
            'package_id' => 7,
            'village_id' => 3,
            'applicant_name' => 'Sunarto',
            'nik' => '3201010101010001',
        ];

        $key = LegacyIdentity::ticketKey($row);

        $this->assertFalse($key['primary'], 'tanpa legacy id harus jatuh ke kunci lemah');
        $this->assertSame(
            LegacyIdentity::weakTicketKey($row),
            $key['key'],
            'kunci lemah harus sama dengan weakTicketKey()'
        );
        // Kunci lemah TIDAK memakai nama mentah, huruf kecil + trim.
        $this->assertStringContainsString('sunarto', $key['key']);
    }

    #[Test]
    public function customer_email_berbeda_untuk_nama_sama_dengan_id_legacy_berbeda(): void
    {
        $a = LegacyIdentity::customerEmail('Sunarto', 9001);
        $b = LegacyIdentity::customerEmail('Sunarto', 9002);

        $this->assertNotSame($a, $b, 'dua pelanggan bernama sama wajib punya email berbeda');
        // Pembeda adalah id legacy pelanggan.
        $this->assertSame('sunarto9001@gmail.com', $a);
        $this->assertSame('sunarto9002@gmail.com', $b);
    }

    // =========================================================
    // RANTAI MIGRASI: LegacyMigrateFastCommand (wiring, tanpa DB)
    // =========================================================

    #[Test]
    public function rantai_migrasi_cepat_memfilter_users_dan_menghitung_denda_di_akhir(): void
    {
        $cmd = new LegacyMigrateFastCommand;

        $ref = new \ReflectionProperty($cmd, 'chain');
        /** @var array<int,array{command:string,business:bool,extra?:array<string,mixed>}> $chain */
        $chain = $ref->getValue($cmd);

        $byCommand = [];
        foreach ($chain as $i => $step) {
            $byCommand[$step['command']] = ['index' => $i, 'step' => $step];
        }

        // import:users HARUS di-scope ke business (bukan 'business' => false).
        $this->assertArrayHasKey('import:users', $byCommand);
        $this->assertTrue(
            $byCommand['import:users']['step']['business'],
            'import:users harus memakai --business supaya user bisnis lain tidak ikut terimpor'
        );

        // billing:recalc-penalty ada, di AKHIR rantai, dan meneruskan --force.
        $this->assertArrayHasKey('billing:recalc-penalty', $byCommand);
        $this->assertSame(
            count($chain) - 1,
            $byCommand['billing:recalc-penalty']['index'],
            'billing:recalc-penalty harus jadi langkah terakhir rantai'
        );
        $this->assertTrue($byCommand['billing:recalc-penalty']['step']['business']);
        $this->assertTrue(
            ($byCommand['billing:recalc-penalty']['step']['extra'] ?? [])['--force'] ?? false,
            'billing:recalc-penalty butuh --force (menolak jalan tanpanya)'
        );
    }

    // =========================================================
    // IMPORTER: jalankan command sungguhan atas DB legacy fixture
    // =========================================================

    #[Test]
    public function dua_pelanggan_nama_sama_di_desa_berbeda_menghasilkan_dua_tiket_dan_dua_customers(): void
    {
        $this->seedAppTargets();
        $this->seedLegacyFixture();

        $exitTickets = Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exitTickets, 'import:tickets harus sukses');

        $exitCustomers = Artisan::call('import:customers', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exitCustomers, 'import:customers harus sukses');

        // Dua tiket, satu per instalasi legacy.
        $tickets = InstallationTicket::query()
            ->whereIn('legacy_installation_id', [self::SUNARTO_A['inst'], self::SUNARTO_B['inst']])
            ->get();
        $this->assertCount(2, $tickets, 'dua instalasi legacy berbeda harus menghasilkan dua tiket');
        $this->assertSame(
            [self::SUNARTO_A['inst'], self::SUNARTO_B['inst']],
            $tickets->pluck('legacy_installation_id')->sort()->values()->all()
        );

        // Dua baris customers, terhubung ke tiket yang BERBEDA.
        $customers = Customer::query()
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->get();
        $this->assertCount(2, $customers, 'dua tiket berbeda harus menghasilkan dua baris customers');
        $this->assertCount(2, $customers->pluck('ticket_id')->unique(), 'tiap customers harus menunjuk tiket yang berbeda');

        // Dua akun user pelanggan berbeda (email unik).
        $userIds = $customers->pluck('user_id')->unique();
        $this->assertCount(2, $userIds, 'dua pelanggan harus punya akun user berbeda');
        $emails = User::whereIn('id', $userIds)->pluck('email');
        $this->assertCount(2, $emails->unique(), 'email kedua akun harus unik');
        $this->assertEqualsCanonicalizing(
            [LegacyIdentity::customerEmail('Sunarto', self::SUNARTO_A['cust']),
                LegacyIdentity::customerEmail('Sunarto', self::SUNARTO_B['cust'])],
            $emails->all()
        );
    }

    #[Test]
    public function importer_idempotent_dijalankan_dua_kali_tidak_menggandakan(): void
    {
        $this->seedAppTargets();
        $this->seedLegacyFixture();

        Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        Artisan::call('import:customers', ['--force' => true, '--business' => 5]);

        // Ulangi: harus update, bukan bikin baris baru.
        Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        Artisan::call('import:customers', ['--force' => true, '--business' => 5]);

        $tickets = InstallationTicket::query()
            ->whereIn('legacy_installation_id', [self::SUNARTO_A['inst'], self::SUNARTO_B['inst']])
            ->get();
        $this->assertCount(2, $tickets, 'jalur kedua tidak boleh menggandakan tiket');

        $this->assertSame(2, Customer::query()->whereIn('ticket_id', $tickets->pluck('id'))->count());
        $this->assertSame(2, User::where('role', 'pelanggan')->count(), 'jalur kedua tidak boleh menggandakan user pelanggan');
    }

    // =========================================================
    // REGRESI: customer_code tidak boleh tersuffix saat impor ulang
    // =========================================================

    /**
     * Impor ulang TIDAK boleh mengubah customer_code. Dulu baris yang sedang
     * di-update dianggap bentrok dengan dirinya sendiri -> 'INS-501_c9001'
     * (produksi: 1.01.0002 -> 1.01.0002_c8805) sehingga pencocokan
     * legacy customer_code <-> kode_instalasi putus dan cakupan denda = 0.
     */
    #[Test]
    public function impor_ulang_tidak_mengubah_customer_code(): void
    {
        $this->seedAppTargets();
        $this->seedLegacyFixture();

        Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        Artisan::call('import:customers', ['--force' => true, '--business' => 5]);

        $before = Customer::query()->orderBy('id')->pluck('customer_code', 'id')->all();
        $this->assertContains(self::SUNARTO_A['kode'], $before, 'kode instalasi legacy harus dipakai apa adanya');
        $this->assertContains(self::SUNARTO_B['kode'], $before, 'kode instalasi legacy harus dipakai apa adanya');

        // Jalur kedua persis data yang sama.
        Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        Artisan::call('import:customers', ['--force' => true, '--business' => 5]);

        $after = Customer::query()->orderBy('id')->pluck('customer_code', 'id')->all();
        $this->assertSame($before, $after, 'customer_code TIDAK boleh berubah saat impor ulang (tidak boleh tersuffix).');
        $this->assertSame('INS-501', $after[array_key_first($after)], 'kode baris sendiri tidak boleh disuffix jadi INS-501_c9001');
    }

    /**
     * Suffix HARUS tetap muncul untuk kasus asli: dua pelanggan berbeda yang
     * kode_instalasi-nya sama persis (duplikat di data legacy). Yang kedua
     * harus disuffix '_c<legacy_cust_id>' supaya unik, tanpa merusak yang pertama.
     */
    #[Test]
    public function dua_pelanggan_beda_dengan_kode_sama_tetap_disuffix(): void
    {
        $this->seedAppTargets();
        $this->seedLegacyFixture();

        // Jadikan instalasi B memakai kode yang SAMA dengan A (duplikat asli).
        DB::connection('legacy')->table('installations')
            ->where('id', self::SUNARTO_B['inst'])
            ->update(['kode_instalasi' => self::SUNARTO_A['kode']]);

        Artisan::call('import:tickets', ['--force' => true, '--business' => 5]);
        Artisan::call('import:customers', ['--force' => true, '--business' => 5]);

        $codes = Customer::query()->orderBy('id')->pluck('customer_code')->all();

        // Satu baris memakai kode asli, satu baris disuffix dengan id legacy pelanggan.
        $this->assertContains(self::SUNARTO_A['kode'], $codes, 'baris pertama tetap pakai kode asli');
        $this->assertContains(
            self::SUNARTO_A['kode'].'_c'.self::SUNARTO_B['cust'],
            $codes,
            'pelanggan berbeda dengan kode sama harus disuffix _c<legacy_cust_id>'
        );
        $this->assertCount(2, array_unique($codes), 'customer_code akhir harus unik');
    }

    // =========================================================
    // Fixture helpers
    // =========================================================

    /** Siapkan target di DB BARU: paket, desa, teknisi, dan tabel jabatan (FK users.jabatan_id). */
    private function seedAppTargets(): void
    {
        InstallationPackage::create([
            'name' => 'R1 (B5)',
            'installation_fee' => 500_000,
            'monthly_abodemen' => 20_000,
            'late_penalty' => 5_000,
        ]);

        DB::table('villages')->insert([
            ['village_name' => self::SUNARTO_A['village'], 'hamlet_name' => self::SUNARTO_A['hamlet'], 'created_at' => now(), 'updated_at' => now()],
            ['village_name' => self::SUNARTO_B['village'], 'hamlet_name' => self::SUNARTO_B['hamlet'], 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Jabatan untuk FK users.jabatan_id (mapRow/buildUserMap sets jabatan_id).
        DB::table('jabatans')->insert([
            'id' => 5, 'nama_jabatan' => 'Teknisi', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Teknisi (cater) DB baru yang dicocokkan buildUserMap lewat nama+role.
        User::create([
            'name' => 'Teknisi Uji',
            'email' => 'teknisi-uji@pdam.test',
            'password' => bcrypt('rahasia123'),
            'role' => 'teknisi',
        ]);
    }

    /**
     * Buat DB legacy fixture sementara, isi tabel + baris legacy, lalu arahkan
     * koneksi `legacy` ke sana. DB dibuat dari kredensial server mysql yang
     * sedang dipakai test (phpunit / CI), bukan kredensial produksi.
     */
    private function seedLegacyFixture(): void
    {
        $mysql = config('database.connections.mysql');
        $this->fixtureDb = 'pamsides_legacy_fixture_'.getmypid();

        $server = new \PDO(
            "mysql:host={$mysql['host']};port={$mysql['port']}",
            $mysql['username'],
            $mysql['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$this->fixtureDb}` DEFAULT CHARACTER SET utf8mb4");

        // Arahkan koneksi 'legacy' ke DB fixture ini.
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
            $t->string('username')->nullable();
            $t->string('password')->nullable();
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
            $t->string('nik')->nullable();
            $t->string('jk')->nullable();
            $t->string('tgl_lahir')->nullable();
            $t->string('tempat_lahir')->nullable();
            $t->string('hp')->nullable();
            $t->string('alamat')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });
        $schema->create('installations', function ($t) {
            $t->bigIncrements('id');
            $t->string('kode_instalasi')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('package_id')->nullable();
            $t->unsignedBigInteger('desa')->nullable();
            $t->unsignedBigInteger('cater_id')->nullable();
            $t->string('status')->nullable();
            $t->string('alamat')->nullable();
            $t->string('rt')->nullable();
            $t->string('rw')->nullable();
            $t->string('order')->nullable();
            $t->string('aktif')->nullable();
        });

        $L = DB::connection('legacy');

        // Paket legacy kelas "R1" business 5 -> match "R1 (B5)" di DB baru.
        $L->table('packages')->insert(['id' => 11, 'business_id' => 5, 'kelas' => 'R1']);

        // Teknisi legacy (jabatan 5) bernama sama dgn user DB baru -> ter-map.
        $L->table('users')->insert([
            ['id' => 7001, 'nama' => 'Teknisi Uji', 'username' => 'teknisi', 'password' => '$2y$04$abcdefghijklmnopqrstuv', 'jabatan' => 5, 'business_id' => 5],
        ]);

        $L->table('villages')->insert([
            ['id' => 101, 'nama' => self::SUNARTO_A['village'], 'dusun' => self::SUNARTO_A['hamlet']],
            ['id' => 102, 'nama' => self::SUNARTO_B['village'], 'dusun' => self::SUNARTO_B['hamlet']],
        ]);

        // DUA pelanggan legacy, NAMA SAMA, di desa berbeda.
        $L->table('customers')->insert([
            ['id' => self::SUNARTO_A['cust'], 'nama' => 'Sunarto', 'nik' => '3201010101010001', 'jk' => 'L', 'tgl_lahir' => '1980-01-01', 'tempat_lahir' => 'Bandung', 'hp' => '081200000001', 'alamat' => 'Kp. A', 'business_id' => 5],
            ['id' => self::SUNARTO_B['cust'], 'nama' => 'Sunarto', 'nik' => '3201010101010002', 'jk' => 'L', 'tgl_lahir' => '1985-02-02', 'tempat_lahir' => 'Bandung', 'hp' => '081200000002', 'alamat' => 'Kp. B', 'business_id' => 5],
        ]);

        $L->table('installations')->insert([
            [
                'id' => self::SUNARTO_A['inst'], 'kode_instalasi' => self::SUNARTO_A['kode'],
                'customer_id' => self::SUNARTO_A['cust'], 'business_id' => 5, 'package_id' => 11,
                'desa' => 101, 'cater_id' => 7001, 'status' => 'A', 'alamat' => 'Kp. A',
                'rt' => '01', 'rw' => '02', 'order' => '2024-01-10', 'aktif' => '2024-02-01',
            ],
            [
                'id' => self::SUNARTO_B['inst'], 'kode_instalasi' => self::SUNARTO_B['kode'],
                'customer_id' => self::SUNARTO_B['cust'], 'business_id' => 5, 'package_id' => 11,
                'desa' => 102, 'cater_id' => 7001, 'status' => 'A', 'alamat' => 'Kp. B',
                'rt' => '03', 'rw' => '04', 'order' => '2024-03-15', 'aktif' => '2024-04-01',
            ],
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
