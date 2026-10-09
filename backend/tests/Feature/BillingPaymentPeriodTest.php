<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingPaymentPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);
    }

    private function makeCustomer(string $code = 'PDAM-TEST-00001'): Customer
    {
        $package = InstallationPackage::create([
            'name'             => 'Paket Test',
            'installation_fee' => 1_500_000,
            'monthly_abodemen' => 15_000,
            'late_penalty'     => 10_000,
        ]);

        $user = User::create([
            'name'     => 'Pelanggan Test',
            'email'    => 'pelanggan@pdam.test',
            'password' => Hash::make('password'),
            'role'     => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id'     => $package->id,
            'applicant_name' => 'Pelanggan Test',
            'nik'            => '3300000000000001',
            'address'        => 'Jl. Test No. 1',
            'lat'            => -7.797068,
            'lng'            => 110.370529,
            'status'         => 'completed',
            'created_by'     => $this->admin->id,
        ]);

        return Customer::create([
            'ticket_id'             => $ticket->id,
            'user_id'               => $user->id,
            'customer_code'         => $code,
            'initial_meter_reading' => 0,
            'meter_photo_url'       => '/storage/test.jpg',
            'activated_at'          => now(),
        ]);
    }

    private function makeBill(Customer $customer, array $attrs = []): MonthlyBill
    {
        return MonthlyBill::create(array_merge([
            'customer_id'          => $customer->id,
            'billing_period_year'  => 2026,
            'billing_period_month' => 7,
            'meter_reading_start'  => 0,
            'meter_reading_end'    => 10,
            'usage_m3'             => 10,
            'usage_charge'         => 50_000,
            'abodemen'             => 15_000,
            'penalty_amount'       => 0,
            'total_amount'         => 65_000,
            'status'               => 'unpaid',
            'due_date'             => '2026-08-10',
        ], $attrs));
    }

    /**
     * Satu baris jurnal piutang, sama seperti yang ditulis
     * `billing:generate-overdue-transactions`.
     *
     * @param  string  $akunKredit  4.1.01.02 (abodemen) atau 4.1.01.03 (pemakaian)
     */
    private function makeOverdueJournal(MonthlyBill $bill, string $akunKredit, $saldo): Transaction
    {
        return Transaction::create([
            'tgl_transaksi'        => now(),
            'account_debet'        => '1.1.03.01',
            'account_kredit'       => $akunKredit,
            'transaction_group'    => null,
            'reverence_type'       => 'overdue_bill',
            'reverence_id'         => $bill->id,
            'keterangan_transaksi' => 'Piutang '.$bill->periodLabel(),
            'relasi'               => 'PDAM-TEST-00001',
            'saldo'                => $saldo,
            'id_user'              => $this->admin->id,
            'urutan'               => Transaction::max('id') + 1,
        ]);
    }

    #[Test]
    public function period_label_menghasilkan_nama_bulan_penuh(): void
    {
        $customer = $this->makeCustomer();

        $this->assertSame('Juli 2026', $this->makeBill($customer, ['billing_period_month' => 7])->periodLabel());
        $this->assertSame('Januari 2026', $this->makeBill($customer, ['billing_period_month' => 1])->periodLabel());
        $this->assertSame('Desember 2026', $this->makeBill($customer, ['billing_period_month' => 12])->periodLabel());
        $this->assertSame('', $this->makeBill($customer, ['billing_period_month' => 0])->periodLabel());
        $this->assertSame('', $this->makeBill($customer, ['billing_period_month' => 13])->periodLabel());
    }

    /**
     * Tanggal pembayaran tidak boleh di masa depan.
     *
     * Uang belum masuk kalau tanggalnya masih besok, jadi membukukannya
     * sekarang membuat saldo kas hari ini meleset. Validasi ini yang
     * menjamin data — kalender di frontend sudah membatasi, tapi endpoint
     * ini bisa dipanggil langsung.
     */
    #[Test]
    public function tanggal_pembayaran_masa_depan_ditolak(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, ['total_amount' => 65_000]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 65_000,
            'paid_at_date'   => now()->addDay()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('paid_at_date');

        // Tagihan harus tetap belum dibayar — tidak ada efek samping.
        $this->assertSame('unpaid', $bill->fresh()->status);
        $this->assertSame(0, Transaction::where('reverence_type', 'bill_payment')->count());
    }

    #[Test]
    public function payment_description_mengikuti_format_an_nama_kode(): void
    {
        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, ['billing_period_month' => 8]);

        $this->assertSame(
            'Tagihan Denda bulan Agustus 2026 an. Pelanggan Test (PDAM-TEST-00001)',
            $bill->paymentDescription('Denda', 'PDAM-TEST-00001', 'Pelanggan Test')
        );

        // Tanpa nama → kodenya tetap ditulis, tanpa "an." kosong.
        $this->assertSame(
            'Tagihan Abodemen bulan Agustus 2026 (PDAM-TEST-00001)',
            $bill->paymentDescription('Abodemen', 'PDAM-TEST-00001')
        );

        // Bulan invalid → string kosong supaya pemanggil pakai fallback.
        $invalid = $this->makeBill($customer, ['billing_period_month' => 0]);
        $this->assertSame('', $invalid->paymentDescription('Denda', 'PDAM-TEST-00001', 'Pelanggan Test'));
    }

    #[Test]
    public function jurnal_pembayaran_memuat_nama_bulan_tagihan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => 7,
        ]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 65_000,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $ket = Transaction::where('reverence_type', 'bill_payment')->pluck('keterangan_transaksi');

        $this->assertTrue($ket->contains('Tagihan Abodemen bulan Juli 2026 an. Pelanggan Test (PDAM-TEST-00001) - Tunai'), 'Jurnal abodemen harus memuat periode tagihan.');
        $this->assertTrue($ket->contains('Tagihan Pemakaian bulan Juli 2026 an. Pelanggan Test (PDAM-TEST-00001) - Tunai'), 'Jurnal pemakaian harus memuat periode tagihan.');
    }

    #[Test]
    public function metode_transfer_ikut_tertulis_di_keterangan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => 8,
            'penalty_amount'       => 10_000,
            'total_amount'         => 75_000,
        ]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'transfer',
            'amount_paid'    => 75_000,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $trx = Transaction::where('reverence_type', 'bill_payment')->get();

        // Metode transfer harus terbaca di keterangan DAN di akun debet (Kas BRI).
        foreach ($trx as $t) {
            $this->assertStringContainsString('- Transfer BRI', (string) $t->keterangan_transaksi);
            $this->assertSame('1.1.01.03', $t->account_debet);
        }
    }

    #[Test]
    public function jurnal_denda_memuat_nama_bulan_tagihan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => 6,
            'penalty_amount'       => 10_000,
            'total_amount'         => 75_000,
        ]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 75_000,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $denda = Transaction::where('reverence_type', 'bill_payment')
            ->where('account_kredit', '4.1.01.04')
            ->get();

        $this->assertCount(1, $denda, 'Harus ada satu jurnal denda.');
        $this->assertSame(
            'Tagihan Denda bulan Juni 2026 an. Pelanggan Test (PDAM-TEST-00001) - Tunai',
            $denda->first()->keterangan_transaksi
        );
        $this->assertSame(10_000, (int) $denda->first()->saldo);
    }

    #[Test]
    public function semua_jurnal_denda_tunggakan_memuat_periode(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => 5,
            'penalty_amount'       => 10_000,
            'total_amount'         => 75_000,
        ]);

        // Simulasikan hasil `billing:generate-overdue-transactions`: tagihan ini
        // sudah dibukukan sebagai piutang (debit 1.1.03.01).
        //
        // WAJIB membuat jurnal sungguhan di sini. Versi lama test ini hanya
        // mengandalkan `penalty_amount > 0` sebagai penanda "menunggak" — padahal
        // penanda itu tidak pernah jadi penentu di backend (backend memakai
        // keberadaan jurnal overdue_bill). Akibatnya test ini diam-diam menjaga
        // kode yang menerima "denda > 0 berarti menunggak" — persis bug yang
        // sudah diperbaiki: tagihan menunggak berpiutang tapi dendanya 0 akan
        // dikreditkan ke pendapatan, bukan menutup piutang.
        $this->makeOverdueJournal($bill, '4.1.01.02', $bill->abodemen);
        $this->makeOverdueJournal($bill, '4.1.01.03', $bill->usage_charge);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 75_000,
        ])->assertStatus(200);

        // Tunggakan: abodemen + pemakaian menutup piutang (1.1.03.01), denda ke 4.1.01.04
        $trx = Transaction::where('reverence_type', 'bill_payment')->get();
        $this->assertCount(3, $trx);

        foreach ($trx as $t) {
            $this->assertStringContainsString('bulan Mei 2026', (string) $t->keterangan_transaksi);
        }

        $kredit = fn (string $prefix) => Transaction::where('reverence_type', 'bill_payment')
            ->where('keterangan_transaksi', 'like', 'Tagihan '.$prefix.'%')
            ->value('account_kredit');

        $this->assertSame('1.1.03.01', $kredit('Abodemen'));
        $this->assertSame('1.1.03.01', $kredit('Pemakaian'));
        $this->assertSame('4.1.01.04', $kredit('Denda'));
    }

    /**
     * Tagihan menunggak yang dendanya 0 (cuma abodemen) tetap harus menutup
     * piutang, bukan diakui sebagai pendapatan.
     *
     * Ini regresi untuk bug di `MonthlyBillController::pay`: penentuan akun
     * kredit memakai `$isTunggakan = ($denda > 0)`. Tagihan seperti ini punya
     * piutang yang sudah dibukukan saat generate tunggakan, tapi dendanya 0 —
     * sehingga sebelumnya dikreditkan ke 4.1.01.02 (Pendapatan Abodemen).
     * Akibatnya pendapatan diakui dua kali: sekali lewat piutang, sekali lagi
     * lewat pembayaran. Seharusnya menutup 1.1.03.01 (Piutang Usaha).
     */
    #[Test]
    public function tagihan_menunggak_tanpa_denda_menutup_piutang(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => 5,
            'penalty_amount'       => 0,   // TIDAK menunggakan
            'abodemen'             => 15_000,
            'usage_charge'         => 50_000,
            'total_amount'         => 65_000,
        ]);

        // Piutang sudah tercatat saat generate tunggakan.
        $this->makeOverdueJournal($bill, '4.1.01.02', $bill->abodemen);
        $this->makeOverdueJournal($bill, '4.1.01.03', $bill->usage_charge);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 65_000,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $kredit = fn (string $prefix) => Transaction::where('reverence_type', 'bill_payment')
            ->where('keterangan_transaksi', 'like', 'Tagihan '.$prefix.'%')
            ->value('account_kredit');

        $this->assertSame('1.1.03.01', $kredit('Abodemen'), 'Abodemen harus menutup piutang.');
        $this->assertSame('1.1.03.01', $kredit('Pemakaian'), 'Pemakaian harus menutup piutang.');

        // Tidak boleh ada pendapatan yang tercatat dua kali.
        $this->assertSame(0, Transaction::where('reverence_type', 'bill_payment')
            ->whereIn('account_kredit', ['4.1.01.02', '4.1.01.03'])
            ->count(), 'Pendapatan tidak boleh diakui dua kali.');
    }

    /**
     * Tagihan tanpa piutang (bayar tepat waktu) diakui langsung sebagai
     * pendapatan — bukan lewat Piutang Usaha.
     */
    #[Test]
    public function tagihan_tanpa_piutang_diakui_sebagai_pendapatan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'penalty_amount' => 0,
            'total_amount'   => 65_000,
        ]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 65_000,
        ])->assertStatus(200)->assertJson(['success' => true]);

        $kredit = fn (string $prefix) => Transaction::where('reverence_type', 'bill_payment')
            ->where('keterangan_transaksi', 'like', 'Tagihan '.$prefix.'%')
            ->value('account_kredit');

        $this->assertSame('4.1.01.02', $kredit('Abodemen'));
        $this->assertSame('4.1.01.03', $kredit('Pemakaian'));
    }

    /**
     * Tagihan Rp 0 otomatis lunas: tidak ada pembayaran, tidak ada jurnal,
     * tapi tetap tampil PAID di halaman Pemakaian Air.
     *
     * Halaman ini (`usage`) menghitung status dari NOMINAL yang dibayarkan,
     * bukan dari kolom `status`. Karena tagihan nihil tidak punya `bill_payments`
     * maupun jurnal, kalau cuma-andalkan nominal ia akan tampil UNPAID —
     * padahal DB sudah `paid` dan Daftar Tagihan menampilkannya LUNAS.
     * Dua halaman berselisih soal tagihan yang sama.
     */
    #[Test]
    public function tagihan_nihil_tampil_pada_bukan_unpaid(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $this->makeBill($customer, [
            'billing_period_year'  => 2026,
            'billing_period_month' => (int) now()->month,
            'usage_m3'             => 0,
            'usage_charge'         => 0,
            'abodemen'             => 0,
            'penalty_amount'       => 0,
            'total_amount'         => 0,
            'status'               => 'paid',   // seperti hasil generator
            'due_date'             => now()->addDays(5)->toDateString(),
        ]);

        $res = $this->getJson('/api/monthly-bills/usage?month='.now()->month.'&year='.now()->year)
            ->assertOk();

        $row = collect($res->json('data'))->firstWhere('customer_code', 'PDAM-TEST-00001');

        $this->assertNotNull($row, 'Baris pelanggan harus ditemukan.');
        $this->assertSame('PAID', $row['status'], 'Tagihan Rp 0 harus tampil PAID, bukan UNPAID.');
    }

    /**
     * Tagihan Rp 0 tidak bisa di-rollback: tidak ada pembayaran yang bisa
     * dibatalkan, dan statusnya akan otomatis kembali `paid` begitu generator
     * jalan lagi.
     */
    #[Test]
    public function tagihan_nihil_tidak_bisa_di_rollback(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, [
            'usage_charge' => 0,
            'abodemen'     => 0,
            'total_amount' => 0,
            'status'       => 'paid',
        ]);

        $this->deleteJson("/api/monthly-bills/{$bill->id}")
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        // Status tidak boleh berubah.
        $this->assertSame('paid', $bill->fresh()->status);
    }

    #[Test]
    public function nama_pelanggan_mengikuti_nama_user_pelanggan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        // customers.user_id NOT NULL, jadi nama selalu dari user; applicant_name
        // hanya cadangan untuk data yang user-nya sudah tidak ada.
        $customer->user->update(['name' => 'Yuli Iswanto']);

        $bill = $this->makeBill($customer, [
            'billing_period_month' => 8,
            'penalty_amount'       => 10_000,
            'total_amount'         => 75_000,
        ]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 75_000,
        ])->assertStatus(200);

        $denda = Transaction::where('reverence_type', 'bill_payment')
            ->where('account_kredit', '4.1.01.04')
            ->value('keterangan_transaksi');

        $this->assertSame(
            'Tagihan Denda bulan Agustus 2026 an. Yuli Iswanto (PDAM-TEST-00001) - Tunai',
            $denda
        );
    }

    #[Test]
    public function periode_tidak_ditulis_jika_bulan_tidak_valid(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $customer = $this->makeCustomer();
        $bill = $this->makeBill($customer, ['billing_period_month' => 0]);

        $this->postJson("/api/monthly-bills/{$bill->id}/pay", [
            'payment_method' => 'cash',
            'amount_paid'    => 65_000,
        ])->assertStatus(200);

        $ket = Transaction::where('reverence_type', 'bill_payment')->pluck('keterangan_transaksi');

        // Tanpa periode valid, jatuh ke format lama + metode — tanpa kurung kosong.
        $this->assertTrue($ket->contains('Abodemen - PDAM-TEST-00001 (Tunai)'));
        $this->assertTrue($ket->contains('Pemakaian - PDAM-TEST-00001 (Tunai)'));
    }
}
