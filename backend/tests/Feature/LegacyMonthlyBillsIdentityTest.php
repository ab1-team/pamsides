<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regresi B9/F-2c: `import:monthly-bills` WAJIB menautkan usage legacy ke
 * tiket lewat `installation_tickets.legacy_installation_id`
 * (docs/database.md §4), bukan lewat heuristik
 * `nama_pelanggan|desa_baru|paket_lama`.
 *
 * Kasus dijaga: dua instalasi legacy BERBEDA yang pelanggannya bernama sama,
 * desanya sama, dan paketnya sama. Kunci triple memberi SATU tiket untuk
 * keduanya -> usages instalasi kedua tertempel ke pelanggan yang salah (atau
 * di-skip "no customer"). Test ini memastikan tiap usage mendarat di tagihan
 * pelanggannya sendiri.
 *
 * Upsert tetap memakai kunci bisnis `customer_id|year|month`
 * (docs/database.md §9) -- tidak berubah.
 *
 * DB legacy di sini adalah fixture sementara milik test (dibuat/dibuang
 * test ini), bukan DB legacy produksi.
 */
class LegacyMonthlyBillsIdentityTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureDb = null;

    private const INST_A = 701;

    private const INST_B = 702;

    private const INST_NO_CUSTOMER = 703;

    /** Instalasi B sengaja berstatus NON-A (dulu terbuang filter status='A'). */
    private const STATUS_B = 'N';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAppTargets();
        $this->seedLegacyFixture();
    }

    #[Test]
    public function importer_dry_run_tidak_menulis_tagihan(): void
    {
        $exit = Artisan::call('import:monthly-bills', ['--dry-run' => true, '--business' => 5]);
        $this->assertSame(0, $exit);
        $this->assertSame(0, DB::table('monthly_bills')->count(), 'dry-run tidak boleh menulis');
    }

    #[Test]
    public function mapping_memakai_legacy_installation_id_bukan_kunci_triple(): void
    {
        $exit = Artisan::call('import:monthly-bills', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exit);

        $customerA = $this->customerOfTicket(self::INST_A);
        $customerB = $this->customerOfTicket(self::INST_B);
        $this->assertNotSame($customerA, $customerB, 'dua instalasi legacy harus punya pelanggan berbeda');

        // (1) Usage instalasi B (status NON-A, nama/desa/paket identik dgn A)
        //     mendarat ke pelanggan B, BUKAN ke pelanggan A.
        $billB = DB::table('monthly_bills')
            ->where('customer_id', $customerB)
            ->where('billing_period_year', 2024)
            ->where('billing_period_month', 3)
            ->first();
        $this->assertNotNull($billB, 'usage instalasi B wajib jadi tagihan pelanggan B');
        $this->assertSame(10, (int) $billB->usage_m3);
        $this->assertSame(60000, (int) $billB->usage_charge);

        $this->assertSame(0, DB::table('monthly_bills')
            ->where('customer_id', $customerA)
            ->where('billing_period_year', 2024)
            ->where('billing_period_month', 3)
            ->count(), 'usage instalasi B tidak boleh menempel ke pelanggan A');

        // (2) Usage instalasi A mendarat ke pelanggan A.
        $billA = DB::table('monthly_bills')
            ->where('customer_id', $customerA)
            ->where('billing_period_year', 2024)
            ->where('billing_period_month', 2)
            ->first();
        $this->assertNotNull($billA, 'usage instalasi A wajib jadi tagihan pelanggan A');
        $this->assertSame(5, (int) $billA->usage_m3);
        $this->assertSame('paid', $billA->status);

        // (3) Instalasi legacy tanpa baris customers tetap di-skip.
        $this->assertSame(2, DB::table('monthly_bills')->count(),
            'hanya 2 pasangan yang berpelanggan; instalasi tanpa customer di-skip');
    }

    #[Test]
    public function tanpa_legacy_installation_id_usage_tidak_ditebak_lewat_triple(): void
    {
        // Tiket B kehilangan anchor -> usage B tidak boleh dipetakan ke tiket A
        // walau triple-nya identik. Harus berakhir "tanpa tiket".
        DB::table('installation_tickets')
            ->where('legacy_installation_id', self::INST_B)
            ->update(['legacy_installation_id' => null]);

        Artisan::call('import:monthly-bills', ['--force' => true, '--business' => 5]);

        $customerA = $this->customerOfTicket(self::INST_A);
        $this->assertSame(0, DB::table('monthly_bills')->where('billing_period_month', 3)->count(),
            'tanpa anchor, usage tidak boleh ditebak ke tiket lain lewat triple');
        $this->assertSame(0, DB::table('monthly_bills')->where('customer_id', $customerA)
            ->where('billing_period_month', 3)->count());
        $this->assertSame(1, DB::table('monthly_bills')->count());
    }

    private function customerOfTicket(int $legacyInstId): int
    {
        $ticketId = DB::table('installation_tickets')
            ->where('legacy_installation_id', $legacyInstId)
            ->value('id');

        return (int) DB::table('customers')->where('ticket_id', $ticketId)->value('id');
    }

    private function seedAppTargets(): void
    {
        $admin = User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-bills-identity@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $package = InstallationPackage::create([
            'name' => 'R1 (B5)',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty' => 5_000,
        ]);

        $village = DB::table('villages')->insertGetId([
            'village_name' => 'Sukamaju',
            'hamlet_name' => 'Dusun A',
        ]);

        // Dua pelanggan nama sama -> tiga field triple IDENTIK.
        foreach ([self::INST_A => 'Sunarto', self::INST_B => 'Sunarto'] as $legacyId => $name) {
            $ticketId = InstallationTicket::create([
                'package_id' => $package->id,
                'applicant_name' => $name,
                'nik' => '32010101010100'.$legacyId,
                'address' => 'Kp. A',
                'lat' => -7.1,
                'lng' => 110.1,
                'status' => 'completed',
                'created_by' => $admin->id,
                'legacy_installation_id' => $legacyId,
            ])->id;

            $userId = User::create([
                'name' => $name,
                'email' => "sunarto-{$legacyId}@pdam.test",
                'password' => Hash::make('password'),
                'role' => 'pelanggan',
            ])->id;

            Customer::create([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'customer_code' => 'INS-'.$legacyId,
                'initial_meter_reading' => 0,
                'village_id' => $village,
            ]);
        }

        // Tiket ketiga: instalasi legacy yang TIDAK punya baris customers.
        InstallationTicket::create([
            'package_id' => $package->id,
            'applicant_name' => 'Sunarto',
            'nik' => '32010101010100'.self::INST_NO_CUSTOMER,
            'address' => 'Kp. A',
            'lat' => -7.1,
            'lng' => 110.1,
            'status' => 'completed',
            'created_by' => $admin->id,
            'legacy_installation_id' => self::INST_NO_CUSTOMER,
        ]);
    }

    private function seedLegacyFixture(): void
    {
        $mysql = config('database.connections.mysql');
        $this->fixtureDb = 'pamsides_bills_fixture_'.getmypid();

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
        $schema->create('customers', function ($t) {
            $t->bigIncrements('id');
            $t->string('nama')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
        });
        $schema->create('installations', function ($t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('package_id')->nullable();
            $t->unsignedBigInteger('desa')->nullable();
            $t->string('status')->nullable();
        });
        $schema->create('usages', function ($t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('id_instalasi')->nullable();
            $t->string('tgl_pemakaian')->nullable();
            $t->string('tgl_akhir')->nullable();
            $t->string('awal')->nullable();
            $t->string('akhir')->nullable();
            $t->string('nominal')->nullable();
            $t->string('status')->nullable();
        });

        $L = DB::connection('legacy');
        $L->table('customers')->insert([
            ['id' => 9001, 'nama' => 'Sunarto', 'business_id' => 5],
            ['id' => 9002, 'nama' => 'Sunarto', 'business_id' => 5],
        ]);
        $L->table('installations')->insert([
            ['id' => self::INST_A, 'customer_id' => 9001, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => 'A'],
            ['id' => self::INST_B, 'customer_id' => 9002, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => self::STATUS_B],
            ['id' => self::INST_NO_CUSTOMER, 'customer_id' => 9003, 'business_id' => 5, 'package_id' => 11, 'desa' => 101, 'status' => 'A'],
        ]);
        $L->table('usages')->insert([
            ['id' => 1, 'business_id' => 5, 'id_instalasi' => self::INST_A, 'tgl_pemakaian' => '2024-02-10', 'tgl_akhir' => '2024-03-20', 'awal' => '100', 'akhir' => '105', 'nominal' => '30000', 'status' => 'PAID'],
            ['id' => 2, 'business_id' => 5, 'id_instalasi' => self::INST_B, 'tgl_pemakaian' => '2024-03-10', 'tgl_akhir' => '2024-04-20', 'awal' => '200', 'akhir' => '210', 'nominal' => '60000', 'status' => 'UNPAID'],
            ['id' => 3, 'business_id' => 5, 'id_instalasi' => self::INST_NO_CUSTOMER, 'tgl_pemakaian' => '2024-04-10', 'tgl_akhir' => '2024-05-20', 'awal' => '0', 'akhir' => '4', 'nominal' => '24000', 'status' => 'UNPAID'],
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
