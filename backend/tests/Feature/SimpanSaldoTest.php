<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimpanSaldoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Account::create([
            'parent_id' => 111, 'lev1' => 1, 'lev2' => 1, 'lev3' => 1, 'lev4' => 1,
            'kode_akun' => '1.1.01.01', 'nama_akun' => 'Kas Tunai', 'jenis_mutasi' => 'debet',
        ]);
        Account::create([
            'parent_id' => 411, 'lev1' => 4, 'lev2' => 1, 'lev3' => 1, 'lev4' => 1,
            'kode_akun' => '4.1.01.01', 'nama_akun' => 'Pendapatan', 'jenis_mutasi' => 'kredit',
        ]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@saldo.test',
            'password' => Hash::make('password'), 'role' => 'admin',
        ]);
    }

    #[Test]
    public function simpan_saldo_bulanan_rekal_amount_dari_transaksi(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        Transaction::create([
            'tgl_transaksi' => '2026-05-15',
            'account_debet' => '1.1.01.01',
            'account_kredit' => '4.1.01.01',
            'saldo' => 1_000_000,
            'id_user' => $this->admin->id,
        ]);
        Transaction::create([
            'tgl_transaksi' => '2026-05-20',
            'account_debet' => '1.1.01.01',
            'account_kredit' => '4.1.01.01',
            'saldo' => 500_000,
            'id_user' => $this->admin->id,
        ]);

        $response = $this->postJson('/api/pelaporan/simpan-saldo', [
            'tahun' => 2026,
            'bulan' => '05',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $kasId = Account::where('kode_akun', '1.1.01.01')->value('id');
        $row = DB::table('amount')
            ->where('account_id', $kasId)
            ->where('tahun', 2026)
            ->where('bulan', '05')
            ->first();
        $this->assertNotNull($row);
        $this->assertEquals(1500000, (float) $row->debit);
    }

    #[Test]
    public function simpan_saldo_validasi_param_tahun_dan_bulan(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $this->postJson('/api/pelaporan/simpan-saldo', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tahun']);

        $this->postJson('/api/pelaporan/simpan-saldo', ['tahun' => 2026, 'bulan' => '99'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function simpan_saldo_semua_bulan_loop_1_sampai_12(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        Transaction::create([
            'tgl_transaksi' => '2026-03-10',
            'account_debet' => '1.1.01.01',
            'account_kredit' => '4.1.01.01',
            'saldo' => 250_000,
            'id_user' => $this->admin->id,
        ]);

        $response = $this->postJson('/api/pelaporan/simpan-saldo', ['tahun' => 2026]);

        $response->assertOk()->assertJson(['success' => true]);

        $kasId = Account::where('kode_akun', '1.1.01.01')->value('id');
        $this->assertDatabaseHas('amount', [
            'account_id' => $kasId, 'tahun' => 2026, 'bulan' => '03', 'debit' => '250000.00',
        ]);
    }
}
