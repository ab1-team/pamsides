<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FIX1 — regresi `Undefined variable $flushBatch` (GHA run 39243941784:
 * `failed : 2412`, `{"key":"24303|2025-05-19","error":"Undefined variable $flushBatch"}`).
 *
 * `$processGroup` memanggil `$flushBatch()` setiap batch mencapai 500 baris,
 * tetapi closure itu tidak menangkap `&$flushBatch`. Akibatnya SETIAP flush
 * ke-500 melempar error, event dihitung gagal, dan barisnya tidak pernah
 * ditulis. Test ini menyiapkan lebih dari 500 pembayaran baru supaya jalur
 * flush benar-benar dieksekusi.
 *
 * Kunci bisnis tagihan: docs/database.md §9 `monthly_bills`
 * (customer_id + billing_period_year + billing_period_month) → `bill_payments.bill_id`.
 */
class LegacyBillPaymentsFlushBatchTest extends TestCase
{
    use RefreshDatabase;

    /** Jumlah event (grup usage|tgl) > 500 agar flush batch terpicu. */
    private const EVENTS = 501;

    private ?string $fixtureDb = null;

    private User $admin;

    #[Test]
    public function lebih_dari_500_pembayaran_ditulis_tanpa_kegagalan(): void
    {
        $this->seedTargets();
        $this->seedLegacy();

        $before = DB::table('bill_payments')->count();

        $exit = Artisan::call('import:bill-payments', ['--force' => true, '--business' => 5]);
        $output = Artisan::output();

        $this->assertSame(0, $exit, 'import:bill-payments harus sukses');
        $this->assertMatchesRegularExpression('/failed\s*:\s*0\b/', $output, 'tidak boleh ada event gagal (flush batch harus jalan)');
        $this->assertDoesNotMatchRegularExpression('/Undefined variable/', $output);

        $after = DB::table('bill_payments')->count();
        $this->assertSame(
            self::EVENTS,
            $after - $before,
            'setiap event baru harus tertulis ke bill_payments, termasuk yang melewati flush batch'
        );

        // Pembayaran yang melewati ambang 500 tidak boleh hilang maupun dobel:
        // 501 event unik, satu baris per event, nominal = total grup.
        $this->assertSame(self::EVENTS, DB::table('bill_payments')->count());
        $this->assertSame(0, DB::table('bill_payments')->whereNull('paid_at')->count());
        $this->assertMatchesRegularExpression('/created\s*:\s*'.self::EVENTS.'\b/', $output);
    }

    // =========================================================
    // Fixture target DB baru
    // =========================================================

    private function seedTargets(): void
    {
        $this->admin = User::create([
            'name' => 'Admin Flush',
            'email' => 'admin-flush@pdam.test',
            'password' => bcrypt('rahasia123'),
            'role' => 'admin',
        ]);

        $villageId = (int) DB::table('villages')->insertGetId([
            'village_name' => 'Sukamaju',
            'hamlet_name' => 'Dusun A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $packageId = (int) DB::table('installation_packages')->insertGetId([
            'name' => 'R1 (B5)',
            'installation_fee' => 500_000,
            'monthly_abodemen' => 20_000,
            'late_penalty' => 5_000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticketId = (int) DB::table('installation_tickets')->insertGetId([
            'legacy_installation_id' => 701,
            'package_id' => $packageId,
            'applicant_name' => 'Pelanggan Flush',
            'nik' => '3201010101010701',
            'address' => 'Kp. A',
            'lat' => 0,
            'lng' => 0,
            'status' => 'completed',
            'village_id' => $villageId,
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $this->admin->id,
            'customer_code' => 'INS-701',
            'initial_meter_reading' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('monthly_bills')->insert([
            'customer_id' => $customerId,
            'billing_period_year' => 2024,
            'billing_period_month' => 3,
            'meter_reading_start' => 0,
            'meter_reading_end' => 10,
            'usage_m3' => 10,
            'usage_charge' => 10_000,
            'abodemen' => 0,
            'penalty_amount' => 0,
            'total_amount' => 10_000,
            'status' => 'unpaid',
            'due_date' => '2024-03-20',
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
        $this->fixtureDb = 'pamsides_bp_flush_'.getmypid();

        $server = new \PDO(
            "mysql:host={$mysql['host']};port={$mysql['port']}",
            $mysql['username'],
            $mysql['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$this->fixtureDb}` DEFAULT CHARACTER SET utf8mb4");

        config(['database.connections.legacy' => array_merge($mysql, [
            'database' => $this->fixtureDb,
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
        $L->table('users')->insert([['id' => 7101, 'nama' => 'Admin Flush', 'jabatan' => 1, 'business_id' => 5]]);
        $L->table('packages')->insert(['id' => 11, 'business_id' => 5, 'kelas' => 'R1']);
        $L->table('villages')->insert(['id' => 101, 'nama' => 'Sukamaju', 'dusun' => 'Dusun A']);
        $L->table('customers')->insert(['id' => 9201, 'nama' => 'Pelanggan Flush', 'business_id' => 5]);
        $L->table('installations')->insert([
            'id' => 701, 'kode_instalasi' => 'INS-701', 'customer_id' => 9201, 'business_id' => 5,
            'package_id' => 11, 'desa' => 101, 'status' => 'A',
        ]);

        // Satu usage untuk setiap event (filter status tinggal di transactions, bukan usage).
        $usages = [];
        $trx = [];
        for ($i = 1; $i <= self::EVENTS; $i++) {
            $usages[] = [
                'id' => 8000 + $i, 'id_instalasi' => 701, 'tgl_pemakaian' => '2024-03-10',
                'customer' => 9201, 'business_id' => 5,
            ];
            $trx[] = [
                'id' => 9000 + $i, 'usage_id' => 8000 + $i, 'tgl_transaksi' => '2024-04-05',
                'keterangan' => 'Bayar tagihan', 'total' => 1_000 + $i,
                'user_id' => 7101, 'business_id' => 5,
            ];
        }
        $L->table('usages')->insert($usages);
        $L->table('transactions')->insert($trx);
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
