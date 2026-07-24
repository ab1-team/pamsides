<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JurnalUmumInventarisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Account::create([
            'parent_id' => 121,
            'lev1' => 1, 'lev2' => 2, 'lev3' => 1, 'lev4' => 4,
            'kode_akun' => '1.2.01.04', 'nama_akun' => 'Inventaris/Peralatan', 'jenis_mutasi' => 'debet',
        ]);
        Account::create([
            'parent_id' => 111,
            'lev1' => 1, 'lev2' => 1, 'lev3' => 1, 'lev4' => 1,
            'kode_akun' => '1.1.01.01', 'nama_akun' => 'Kas Tunai', 'jenis_mutasi' => 'debet',
        ]);
        Account::create([
            'parent_id' => 532,
            'lev1' => 5, 'lev2' => 3, 'lev3' => 2, 'lev4' => 1,
            'kode_akun' => '5.3.02.01', 'nama_akun' => 'Beban Penghapusan Aset', 'jenis_mutasi' => 'debet',
        ]);
        Account::create([
            'parent_id' => 421,
            'lev1' => 4, 'lev2' => 2, 'lev3' => 1, 'lev4' => 4,
            'kode_akun' => '4.2.01.04', 'nama_akun' => 'Pertambahan Nilai Penjualan Aset', 'jenis_mutasi' => 'kredit',
        ]);
        Account::create([
            'parent_id' => 431,
            'lev1' => 4, 'lev2' => 3, 'lev3' => 1, 'lev4' => 1,
            'kode_akun' => '4.3.01.01', 'nama_akun' => 'Pendapatan revaluasi Aset', 'jenis_mutasi' => 'kredit',
        ]);
        Account::create([
            'parent_id' => 511,
            'lev1' => 5, 'lev2' => 1, 'lev3' => 1, 'lev4' => 1,
            'kode_akun' => '5.1.01.01', 'nama_akun' => 'Beban Gaji', 'jenis_mutasi' => 'debet',
        ]);
        Account::create([
            'parent_id' => 122,
            'lev1' => 1, 'lev2' => 2, 'lev3' => 2, 'lev4' => 1,
            'kode_akun' => '1.2.02.01', 'nama_akun' => 'Akumulasi Penyusutan Gedung', 'jenis_mutasi' => 'debet',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin@inv.test',
            'password' => Hash::make('password'), 'role' => 'admin',
        ]);
    }

    #[Test]
    public function admin_dapat_beli_inventaris_via_jurnal_umum(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson('/api/transaksi/inventaris', [
            'tgl_transaksi' => '2026-07-01',
            'jenis_transaksi' => 1,
            'sumber_dana' => '1.1.01.01',
            'disimpan_ke' => '1.2.01.04',
            'nama_barang' => 'Laptop Asus',
            'jumlah' => 3,
            'harga_satuan' => 10_000_000,
            'umur_ekonomis' => 48,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'account_debet' => '1.2.01.04',
            'account_kredit' => '1.1.01.01',
            'saldo' => '30000000.00',
        ]);
        $this->assertDatabaseHas('inventories', [
            'nama_barang' => 'Laptop Asus',
            'unit' => 3,
            'harsat' => '10000000.00',
            'jenis' => '1',
            'kategori' => '4',
            'status' => 'Baik',
        ]);
    }

    #[Test]
    public function form_dispatch_pembelian_inventaris(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->getJson('/api/transaksi/jurnal-umum/form?'.http_build_query([
            'tgl_transaksi' => '2026-07-01',
            'jenis_transaksi' => 1,
            'sumber_dana' => '1.1.01.01',
            'disimpan_ke' => '1.2.01.04',
        ]));

        $response->assertOk()->assertJson(['data' => ['form_type' => 'inventaris']]);
    }

    #[Test]
    public function form_dispatch_hapus_inventaris_dengan_daftar(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::create([
            'nama_barang' => 'Meja', 'tgl_beli' => '2025-01-01',
            'unit' => 2, 'harsat' => 1_000_000, 'umur_ekonomis' => 48,
            'jenis' => '1', 'kategori' => '04', 'status' => 'Baik',
        ]);
        $this->assertSame(1, Inventory::where('jenis', '1')->where('kategori', '04')->count());

        $response = $this->getJson('/api/transaksi/jurnal-umum/form?'.http_build_query([
            'tgl_transaksi' => '2026-07-01',
            'jenis_transaksi' => 2,
            'sumber_dana' => '1.2.01.04',
            'disimpan_ke' => '5.3.02.01',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.form_type', 'hapus_inventaris');

        $list = $response->json('data.inventaris_list');
        $this->assertCount(1, $list, 'inventory_list count: '.json_encode($list));
    }

    #[Test]
    public function penjualan_inventaris_insert_jurnal_dan_update_status(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::factory()->peralatan()->create([
            'unit' => 3, 'harsat' => 1_000_000,
        ]);

        $response = $this->postJson("/api/transaksi/inventaris/{$inv->id}/hapus", [
            'tgl_transaksi' => '2026-07-15',
            'alasan' => 'dijual',
            'unit' => 1,
            'harsat' => 1_000_000,
            'harga_jual' => 800_000,
            'sumber_dana' => '1.2.01.04',
            'disimpan_ke' => '5.3.02.01',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'account_debet' => '1.1.01.01',
            'account_kredit' => '4.2.01.04',
            'saldo' => '800000.00',
        ]);
        $inv->refresh();
        $this->assertSame(2, (int) $inv->unit);
        $this->assertSame('Baik', $inv->status);
    }

    #[Test]
    public function hapus_sebagian_unit_insert_inventory_baru_dan_update_asal(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::factory()->peralatan()->create([
            'unit' => 5, 'harsat' => 1_000_000,
        ]);

        $response = $this->postJson("/api/transaksi/inventaris/{$inv->id}/hapus", [
            'tgl_transaksi' => '2026-07-15',
            'alasan' => 'hapus',
            'unit' => 2,
            'harsat' => 1_000_000,
            'harga_jual' => 0,
            'sumber_dana' => '1.2.01.04',
            'disimpan_ke' => '5.3.02.01',
        ]);

        $response->assertStatus(201);

        $inv->refresh();
        $this->assertSame(3, (int) $inv->unit);
        $this->assertSame('Baik', $inv->status);
        $this->assertDatabaseHas('inventories', [
            'unit' => 2,
            'status' => 'Hapus',
        ]);
        $this->assertEquals(2, Inventory::count());
    }

    #[Test]
    public function hapus_penuh_unit_langsung_update_status(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::factory()->peralatan()->create([
            'unit' => 2, 'harsat' => 500_000,
        ]);

        $response = $this->postJson("/api/transaksi/inventaris/{$inv->id}/hapus", [
            'tgl_transaksi' => '2026-07-15',
            'alasan' => 'rusak',
            'unit' => 2,
            'harsat' => 500_000,
            'harga_jual' => 0,
            'sumber_dana' => '1.2.01.04',
            'disimpan_ke' => '5.3.02.01',
        ]);

        $response->assertStatus(201);

        $inv->refresh();
        $this->assertSame(2, (int) $inv->unit);
        $this->assertSame('Rusak', $inv->status);
        $this->assertEquals(1, Inventory::count());
    }

    #[Test]
    public function revaluasi_insert_inventory_baru_dan_jurnal_selisih(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::factory()->gedung()->create([
            'tgl_beli' => '2020-01-01',
            'unit' => 1, 'harsat' => 1_000_000_000, 'umur_ekonomis' => 240,
        ]);

        $response = $this->postJson("/api/transaksi/inventaris/{$inv->id}/hapus", [
            'tgl_transaksi' => '2026-07-15',
            'alasan' => 'revaluasi',
            'unit' => 1,
            'harsat' => 1_000_000_000,
            'harga_jual' => 1_200_000_000,
            'sumber_dana' => '1.2.01.02',
            'disimpan_ke' => '5.3.02.01',
        ]);

        $response->assertStatus(201);

        $inv->refresh();
        $this->assertSame(1, (int) $inv->unit);
        $this->assertSame('Baik', $inv->status);

        $this->assertDatabaseHas('inventories', [
            'unit' => 1,
            'harsat' => '1200000000.00',
            'status' => 'Baik',
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_debet' => '1.1.01.01',
            'account_kredit' => '4.3.01.01',
        ]);
    }

    #[Test]
    public function form_default_nominal_when_no_inventaris_match(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->getJson('/api/transaksi/jurnal-umum/form?'.http_build_query([
            'tgl_transaksi' => '2026-07-01',
            'jenis_transaksi' => 1,
            'sumber_dana' => '1.1.01.01',
            'disimpan_ke' => '5.1.01.01',
        ]));

        $response->assertOk()->assertJson(['data' => ['form_type' => 'nominal']]);
    }

    #[Test]
    public function hapus_lebih_dari_stok_ditolak(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $inv = Inventory::factory()->peralatan()->create(['unit' => 2]);

        $response = $this->postJson("/api/transaksi/inventaris/{$inv->id}/hapus", [
            'tgl_transaksi' => '2026-07-15',
            'alasan' => 'hapus',
            'unit' => 5,
            'harsat' => 100,
            'harga_jual' => 0,
            'sumber_dana' => '1.2.01.04',
            'disimpan_ke' => '5.3.02.01',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    #[Test]
    public function non_admin_tidak_bisa_akses_inventaris_crud(): void
    {
        $teknisi = User::create([
            'name' => 'Teknisi', 'email' => 't@inv.test',
            'password' => Hash::make('password'), 'role' => 'teknisi',
        ]);
        Sanctum::actingAs($teknisi, ['*'], 'sanctum');

        $this->getJson('/api/inventaris')->assertStatus(403);
        $this->postJson('/api/transaksi/inventaris', [])->assertStatus(403);
    }
}
