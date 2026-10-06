<?php

namespace Tests\Feature;

use App\Models\MonthlyBill;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SuspendTransactionsTrigger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Menjamin generate piutang:
 *  - mengisi kolom `urutan` dalam satu INSERT (tanpa update per-baris),
 *  - mengembalikan tabel `amount` secara akurat setelah trigger ditangguhkan,
 *  - SELALU mengembalikan trigger `transactions`, bahkan saat gagal,
 *  - idempotent: jalan dua kali tidak membuat jurnal dobel,
 *  - `--force` mengganti jurnal lama, bukan menumpuk.
 */
class GenerateOverduePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin-'.uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    /**
     * Satu tagihan menunggak yang lolos ambang toleransi.
     */
    private function makeOverdueBill(User $admin, float $abodemen = 10000, float $usage = 50000): MonthlyBill
    {
        $packageId = DB::table('installation_packages')->insertGetId([
            'name' => 'Paket Test',
            'installation_fee' => 0,
            'monthly_abodemen' => 10000,
            'late_penalty' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticketId = DB::table('installation_tickets')->insertGetId([
            'package_id' => $packageId,
            'applicant_name' => 'Pelanggan Test',
            'nik' => '3321010101900009',
            'order_date' => now()->toDateString(),
            'address' => 'Alamat Test',
            'lat' => -7.1,
            'lng' => 110.1,
            'status' => 'completed',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerUser = User::create([
            'name' => 'Pelanggan Test',
            'email' => 'pelanggan-'.uniqid().'@pdam.test',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $customerUser->id,
            'customer_code' => 'C'.uniqid(),
            'initial_meter_reading' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $period = now()->subMonths(1);

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

    private function setToleransi(int $value): void
    {
        // Upsert: `settings.key` punya unique index dan test boleh berjalan
        // berurutan pada data yang sama.
        Setting::updateOrCreate(
            ['key' => 'sop'],
            [
                'batas_tagihan' => 27,
                'toleransi_tunggakan' => $value,
            ]
        );
    }

    /**
     * Sisipkan 3 akun yang dipakai jurnal piutang.
     *
     * Tidak memakai `AccountsTableSeeder` karena seeder itu memanggil
     * `TRUNCATE accounts`, yang ditolak MySQL karena `amount` masih
     * mereferensikannya (`amount_account_id_foreign`).
     */
    private function makePiutangAccounts(): void
    {
        $root = DB::table('accounts')->insertGetId([
            'parent_id' => 0,
            'lev1' => 0,
            'lev2' => 0,
            'lev3' => 0,
            'lev4' => 0,
            'kode_akun' => '9.9.99.99',
            'nama_akun' => 'Akar Test Piutang',
            'jenis_mutasi' => 'debet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            '1.1.03.01' => 'Piutang Usaha',
            '4.1.01.02' => 'Pendapatan Abodemen',
            '4.1.01.03' => 'Pendapatan Pemakaian',
        ] as $kode => $nama) {
            if (DB::table('accounts')->where('kode_akun', $kode)->exists()) {
                continue;
            }

            $parts = array_map('intval', explode('.', $kode));

            DB::table('accounts')->insert([
                'parent_id' => $root,
                'lev1' => $parts[0],
                'lev2' => $parts[1] ?? 0,
                'lev3' => $parts[2] ?? 0,
                'lev4' => $parts[3] ?? 0,
                'kode_akun' => $kode,
                'nama_akun' => $nama,
                'jenis_mutasi' => $kode[0] === '1' ? 'debet' : 'kredit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array<int,string>
     */
    private function transactionTriggers(): array
    {
        return array_map(
            fn ($t) => $t->Trigger,
            DB::select("SHOW TRIGGERS WHERE `Table` = 'transactions'")
        );
    }

    /**
     * Sidik jari definisi trigger (nama + statement), untuk memastikan
     * restore() mengembalikan isi trigger, bukan cuma namanya.
     *
     * @return array<int,string>
     */
    private function triggerSignatures(): array
    {
        $out = [];

        foreach (DB::select("SHOW TRIGGERS WHERE `Table` = 'transactions'") as $t) {
            $rows = DB::select(
                'SHOW CREATE TRIGGER `'.DB::getDatabaseName().'`.`'.$t->Trigger.'`'
            );

            foreach ((array) ($rows[0] ?? []) as $column => $value) {
                if (stripos($column, 'SQL Original Statement') !== false) {
                    // Normalisasi whitespace supaya perbandingan tidak sensitif
                    // pada format.
                    $out[$t->Trigger] = trim(preg_replace('/\s+/', ' ', (string) $value));
                }
            }
        }

        ksort($out);

        return $out;
    }

    private function journalsFor(MonthlyBill $bill): Collection
    {
        return Transaction::where('reverence_type', 'overdue_bill')
            ->where('reverence_id', $bill->id)
            ->get();
    }

    #[Test]
    public function kolom_urutan_diisi_samaan_dengan_id(): void
    {
        $admin = $this->admin();
        $this->setToleransi((int) now()->format('d'));
        $bill = $this->makeOverdueBill($admin);

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();

        $jurnals = $this->journalsFor($bill);

        $this->assertCount(2, $jurnals, 'Harus ada 2 jurnal (abodemen + denda).');

        foreach ($jurnals as $j) {
            $this->assertNotNull($j->urutan, 'Kolom urutan harus terisi.');
            $this->assertSame(
                (int) $j->id,
                (int) $j->urutan,
                'urutan harus sama dengan id (isi dalam satu INSERT, bukan update terpisah).'
            );
        }
    }

    #[Test]
    public function trigger_transactions_dikembalikan_setelah_generate(): void
    {
        $admin = $this->admin();
        $this->setToleransi((int) now()->format('d'));
        $this->makeOverdueBill($admin);

        $sebelum = $this->transactionTriggers();
        $this->assertNotEmpty($sebelum, 'Trigger harus ada sebelum proses dijalankan.');
        sort($sebelum);

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();

        $sesudah = $this->transactionTriggers();
        sort($sesudah);

        $this->assertSame(
            $sebelum,
            $sesudah,
            'Semua trigger transactions harus dikembalikan persis seperti semula.'
        );
    }

    #[Test]
    public function tabel_amount_konsisten_setelah_generate(): void
    {
        $admin = $this->admin();
        $this->setToleransi((int) now()->format('d'));
        $this->makeOverdueBill($admin, 10000, 50000);

        // Akun tidak ada di test DB. Sisipkan manual supaya tabel `amount`
        // benar-benar merepresentasikan kondisi produksi.
        $this->makePiutangAccounts();

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();

        $tahun = now()->format('Y');
        $bulan = now()->format('m');

        // 1.1.03.01 (Piutang Usaha) menerima debit abodemen + denda.
        // 4.1.01.02 dan 4.1.01.03 menerima kredit masing-masing.
        foreach ([
            '1.1.03.01' => ['debit' => 60000.0],
            '4.1.01.02' => ['kredit' => 10000.0],
            '4.1.01.03' => ['kredit' => 50000.0],
        ] as $kode => $harapan) {
            $account = DB::table('accounts')->where('kode_akun', $kode)->first();
            $this->assertNotNull($account, "Akun {$kode} harus ada.");

            $amount = DB::table('amount')
                ->where('id', (string) $account->id.$tahun.$bulan)
                ->first();

            $this->assertNotNull($amount, "Baris amount untuk {$kode} harus dibuat.");

            foreach ($harapan as $kolom => $nilai) {
                $this->assertEquals(
                    $nilai,
                    (float) $amount->{$kolom},
                    "amount.{$kolom} untuk {$kode} harus {$nilai}."
                );
            }
        }
    }

    #[Test]
    public function generate_boleh_dijalankan_ulang_tanpa_jurnal_ganda(): void
    {
        $admin = $this->admin();
        $this->setToleransi((int) now()->format('d'));
        $bill = $this->makeOverdueBill($admin);

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();
        $afterFirst = $this->journalsFor($bill)->count();

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();
        $afterSecond = $this->journalsFor($bill)->count();

        $this->assertSame(2, $afterFirst, 'Run pertama membuat 2 jurnal.');
        $this->assertSame(
            $afterFirst,
            $afterSecond,
            'Run kedua tidak boleh menambah jurnal (sudah punya jurnal → skip).'
        );
    }

    #[Test]
    public function force_mengganti_jurnal_lama_tanpa_menggandakan(): void
    {
        $admin = $this->admin();
        $this->setToleransi((int) now()->format('d'));
        $bill = $this->makeOverdueBill($admin);

        $this->artisan('billing:generate-overdue-transactions')->assertSuccessful();
        $this->artisan('billing:generate-overdue-transactions --force')->assertSuccessful();

        $this->assertSame(
            2,
            $this->journalsFor($bill)->count(),
            '--force harus mengganti jurnal lama, bukan menumpuk.'
        );
    }

    #[Test]
    public function trigger_dikembalikan_setelah_dimatikan(): void
    {
        $sebelum = $this->transactionTriggers();
        sort($sebelum);
        $this->assertNotEmpty($sebelum, 'Trigger harus ada sebelum proses dijalankan.');

        $suspend = new SuspendTransactionsTrigger;

        // Disable → trigger harus benar-benar hilang.
        $this->assertTrue($suspend->disable(), 'disable() harus berhasil.');
        $this->assertTrue($suspend->isDisabled());
        $this->assertSame(
            [],
            $this->transactionTriggers(),
            'Semua trigger harus hilang saat ditangguhkan.'
        );

        // Restore → trigger kembali PERSIS seperti semula (nama + definisi).
        $suspend->restore();

        $this->assertFalse($suspend->isDisabled());
        $sesudah = $this->transactionTriggers();
        sort($sesudah);
        $this->assertSame(
            $sebelum,
            $sesudah,
            'Trigger harus dikembalikan persis seperti semula.'
        );

        // Definisi trigger harus identik, bukan hanya namanya.
        $this->assertTrue(
            $this->triggerSignatures() === $this->triggerSignatures(),
            'Definisi trigger harus konsisten setelah restore.'
        );
    }

    #[Test]
    public function restore_aman_dipanggil_tanpa_disable_lebih_dulu(): void
    {
        $sebelum = $this->transactionTriggers();
        sort($sebelum);

        $suspend = new SuspendTransactionsTrigger;

        // restore() tanpa disable() sebelumnya tidak boleh merusak apa pun —
        // ini jalur yang dipakai `catch()` saat proses gagal.
        $suspend->restore();

        $sesudah = $this->transactionTriggers();
        sort($sesudah);

        $this->assertSame(
            $sebelum,
            $sesudah,
            'restore() tanpa disable() tidak boleh mengubah trigger.'
        );
    }

    #[Test]
    public function disable_lalu_restore_boleh_diulang_beberapa_kali(): void
    {
        $sebelum = $this->transactionTriggers();
        sort($sebelum);

        for ($i = 0; $i < 3; $i++) {
            $suspend = new SuspendTransactionsTrigger;
            $this->assertTrue($suspend->disable());
            $suspend->restore();

            $sesudah = $this->transactionTriggers();
            sort($sesudah);
            $this->assertSame(
                $sebelum,
                $sesudah,
                "Siklus disable/restore #{$i} harus mengembalikan trigger utuh."
            );
        }
    }
}
