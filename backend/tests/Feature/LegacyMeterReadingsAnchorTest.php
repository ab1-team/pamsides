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
 * Regresi MR1: `import:meter-readings` WAJIB menautkan usage legacy ke tiket
 * lewat `installation_tickets.legacy_installation_id` (docs/database.md §4),
 * bukan lewat heuristik `nama|desa|paket`.
 *
 * Tiga kasus dijaga (semua usages September 2026):
 *   1. tiket hasil migrasi masih memakai ejaan nama lama, sedangkan data
 *      legacy sudah diperbaiki -- triple nama|desa|paket tidak menemukan
 *      tiket apa pun (anchor menang);
 *   2. nama legacy duplikat di desa+paket yang sama, dua tiket berbeda
 *      (triple nama|desa|paket hanya menunjuk SATU tiket untuk keduanya);
 *   3. instalasi normal (kedua jalur sepakat).
 *
 * Bila anchor diabaikan dan triple dipakai, usage #1 ter-skip ("no
 * inst→ticket mapping") dan usage #2 mendarat dengan nilai meter milik
 * pelanggan lain.
 *
 * Upsert tetap memakai kunci bisnis `customer_id|year|month`
 * (docs/database.md §9) -- tidak diubah oleh regresi ini.
 *
 * DB legacy di sini adalah fixture sementara milik test (dibuat/dibuang test
 * ini sendiri), bukan DB legacy produksi.
 */
class LegacyMeterReadingsAnchorTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixtureDb = null;

    private const INST_SPELLING = 801;

    private const INST_DUPLICATE = 802;

    private const INST_NORMAL = 803;

    /** Tiket migrasi NGADIPAN lama; inst 802 ditautkan ke tiket NGADIPANPAK yang baru. */
    private const TICKET_DUPLICATE_OTHER = 813;

    private const LEGACY_PACKAGE = 11;

    /** Nilai meter September 2026 per instalasi legacy. */
    private const METER = [
        self::INST_SPELLING => 604,
        self::INST_DUPLICATE => 512,
        self::INST_NORMAL => 733,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAppTargets();
        $this->seedLegacyFixture();
    }

    #[Test]
    public function usages_september_ter_import_lewat_anchor_legacy_installation_id(): void
    {
        $exit = Artisan::call('import:meter-readings', ['--force' => true, '--business' => 5]);
        $this->assertSame(0, $exit, 'import:meter-readings harus sukses');
        $this->assertStringContainsString('via legacy_installation_id', Artisan::output());

        foreach (self::METER as $legacyInstId => $expectedMeter) {
            $customerId = $this->customerOfTicket($legacyInstId);
            $row = DB::table('meter_readings')
                ->where('customer_id', $customerId)
                ->where('reading_year', 2026)
                ->where('reading_month', 9)
                ->first();

            $this->assertNotNull($row, "usage instalasi {$legacyInstId} wajib jadi meter_readings September 2026");
            $this->assertSame((float) $expectedMeter, (float) $row->meter_value,
                "nilai meter instalasi {$legacyInstId} harus milik pelanggannya sendiri");
        }

        $this->assertSame(3, DB::table('meter_readings')->count(),
            'tepat satu meter_readings per pelanggan; tidak boleh ada usage ter-skip');
    }

    #[Test]
    public function dry_run_tidak_menulis_meter_readings(): void
    {
        $exit = Artisan::call('import:meter-readings', ['--dry-run' => true, '--business' => 5]);
        $this->assertSame(0, $exit);
        $this->assertSame(0, DB::table('meter_readings')->count(), 'dry-run tidak boleh menulis');
    }

    #[Test]
    public function impor_ulang_tidak_menggandakan_meter_readings(): void
    {
        Artisan::call('import:meter-readings', ['--force' => true, '--business' => 5]);
        Artisan::call('import:meter-readings', ['--force' => true, '--business' => 5]);

        $this->assertSame(3, DB::table('meter_readings')->count(), 'kunci customer|year|month harus idempoten');
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
            'name' => 'Admin Uji Meter',
            'email' => 'admin-meter-anchor@pdam.test',
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

        // (1) ejaan nama tiket migrasi berbeda dari nama legacy -> triple gagal.
        // (2) duplikat: tiket lain bernama sama (desa+paket sama) sudah ada
        //     lebih dulu -> triple menunjuk tiket itu, bukan tiket inst 802.
        $tickets = [
            self::TICKET_DUPLICATE_OTHER => 'Ngadipan',
            self::INST_DUPLICATE => 'Ngadipan Pak',
            self::INST_SPELLING => 'Partiyem Hj',
            self::INST_NORMAL => 'Parno',
        ];

        foreach ($tickets as $legacyId => $name) {
            $ticketId = InstallationTicket::create([
                'package_id' => $package->id,
                'applicant_name' => $name,
                'nik' => '32010101010100'.$legacyId,
                'address' => 'Kp. A',
                'lat' => -7.1,
                'lng' => 110.1,
                'status' => 'completed',
                'created_by' => $admin->id,
                'village_id' => $village,
                'legacy_installation_id' => $legacyId,
            ])->id;

            $userId = User::create([
                'name' => $name,
                'email' => 'meter-'.$legacyId.'@pdam.test',
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
    }

    private function seedLegacyFixture(): void
    {
        $mysql = config('database.connections.mysql');
        $this->fixtureDb = 'pamsides_mr_fixture_'.getmypid();

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
        $schema->create('users', function ($t) {
            $t->bigIncrements('id');
            $t->string('nama')->nullable();
            $t->integer('jabatan')->nullable();
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
            $t->unsignedBigInteger('cater')->nullable();
        });

        $L = DB::connection('legacy');
        $L->table('packages')->insert([
            ['id' => self::LEGACY_PACKAGE, 'business_id' => 5, 'kelas' => 'R1'],
        ]);
        $L->table('villages')->insert([
            ['id' => 101, 'nama' => 'Sukamaju', 'dusun' => 'Dusun A'],
        ]);
        $L->table('users')->insert([
            ['id' => 1, 'nama' => 'Admin Uji Meter', 'jabatan' => 1],
        ]);
        $L->table('customers')->insert([
            // (1) ejaan diperbaiki di legacy; tiket migrasi masih ejaan lama
            ['id' => 9101, 'nama' => 'Partiyem', 'business_id' => 5],
            // (2) nama duplikat di desa+paket yang sama
            ['id' => 9102, 'nama' => 'Ngadipan', 'business_id' => 5],
            ['id' => 9103, 'nama' => 'Ngadipan', 'business_id' => 5],
            ['id' => 9104, 'nama' => 'Parno', 'business_id' => 5],
        ]);
        $L->table('installations')->insert([
            ['id' => self::INST_SPELLING, 'customer_id' => 9101, 'business_id' => 5, 'package_id' => self::LEGACY_PACKAGE, 'desa' => 101, 'status' => 'A'],
            ['id' => self::INST_DUPLICATE, 'customer_id' => 9103, 'business_id' => 5, 'package_id' => self::LEGACY_PACKAGE, 'desa' => 101, 'status' => 'A'],
            ['id' => self::INST_NORMAL, 'customer_id' => 9104, 'business_id' => 5, 'package_id' => self::LEGACY_PACKAGE, 'desa' => 101, 'status' => 'A'],
        ]);
        $L->table('usages')->insert([
            ['id' => 1, 'business_id' => 5, 'id_instalasi' => self::INST_SPELLING, 'tgl_pemakaian' => '2026-09-10 08:00:00', 'tgl_akhir' => '2026-09-30', 'awal' => '500', 'akhir' => (string) self::METER[self::INST_SPELLING], 'nominal' => '30000', 'status' => 'PAID', 'cater' => 1],
            ['id' => 2, 'business_id' => 5, 'id_instalasi' => self::INST_DUPLICATE, 'tgl_pemakaian' => '2026-09-11 08:00:00', 'tgl_akhir' => '2026-09-30', 'awal' => '400', 'akhir' => (string) self::METER[self::INST_DUPLICATE], 'nominal' => '30000', 'status' => 'PAID', 'cater' => 1],
            ['id' => 3, 'business_id' => 5, 'id_instalasi' => self::INST_NORMAL, 'tgl_pemakaian' => '2026-09-12 08:00:00', 'tgl_akhir' => '2026-09-30', 'awal' => '700', 'akhir' => (string) self::METER[self::INST_NORMAL], 'nominal' => '30000', 'status' => 'PAID', 'cater' => 1],
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
