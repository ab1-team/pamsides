<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression test untuk CRUD pelanggan (halaman /app/data-pelanggan).
 *
 * Setiap test menjaga satu rantai yang dulu putus: Tambah -> tampil di daftar
 * -> Ubah -> Hapus. Testcase lama tidak pernah menyentuh endpoint ini, jadi
 * create() bisa sukses sementara datanya tetap tidak terlihat di UI.
 *
 * Catatan desain: pelanggan hasil form Tambah HANYA punya baris di
 * `installation_tickets` (status `draft`). Record `customers` beserta
 * customer_code dibuat saat aktivasi, bukan saat pendaftaran — lihat
 * docs/database.md bagian `customers`. Test yang menganggap record customers
 * dibuat oleh store() tidak sesuai model ini dan sudah dibuang.
 */
class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-crud@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);
    }

    private function packageId(): int
    {
        return InstallationPackage::create([
            'name' => 'Paket CRUD',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ])->id;
    }

    /** Payload yang dikirim PelangganCreate.vue / PelangganEdit.vue. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nik' => '3201010101010001',
            'nama_lengkap' => 'Warga Uji',
            'email' => 'warga-uji@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-05-06',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000000',
            'alamat_lengkap' => 'Jl. Uji No. 1',
            'package_id' => $this->packageId(),
        ], $overrides);
    }

    /**
     * Pelanggan yang baru disimpan wajib langsung bisa dilihat di
     * /app/data-pelanggan. store() membuat tiket berstatus `draft`, sementara
     * index() dulu menyaring `status != 'draft'` — Akibatnya data hasil Tambah
     * tidak pernah muncul, padahal form Tambah menawarkannya lewat tombol
     * "Tidak, Cek Data" yang langsung membuka halaman daftar.
     */
    #[Test]
    public function pelanggan_baru_muncul_di_daftar_setelah_disimpan(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/customers', $this->payload())->assertCreated();

        $list = $this->getJson('/api/customers');
        $list->assertOk();

        $niks = array_column($list->json('data.data'), 'nik');
        $this->assertContains(
            '3201010101010001',
            $niks,
            'Pelanggan yang baru disimpan tidak muncul di daftar — create() dan index() tidak konsisten.'
        );
    }

    /**
     * Setelah menekan "Tidak, Cek Data", user diarahkan ke daftar. Ia harus
     * bisa menekan tombol Ubah di baris itu dan membuka form yang terisi,
     * bukan mendarat di 404 atau form kosong.
     */
    #[Test]
    public function detail_pelanggan_bisa_diambil_lewat_id_dari_daftar(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/customers', $this->payload())->assertCreated();

        $row = $this->getJson('/api/customers')->json('data.data.0');
        $this->assertNotEmpty($row['id'], 'Baris daftar tidak punya id untuk form Ubah.');

        $detail = $this->getJson('/api/customers/'.$row['id']);
        $detail->assertOk();

        // Form Ubah mengisi ulang field dari detail; kalau tidak sinkron,
        // admin menekan Simpan tanpa sengaja menimpa data lama.
        $this->assertSame('Warga Uji', $detail->json('data.name'));
        $this->assertSame('3201010101010001', $detail->json('data.nik'));
        $this->assertSame('warga-uji@pdam.test', $detail->json('data.email'));
    }

    /**
     * Satu siklus penuh: simpan -> ubah -> hapus. Inilah yang dilakukan pengguna
     * lewat tiga halaman Tambah/Ubah/Hapus.
     */
    #[Test]
    public function siklus_simpan_ubah_hapus_berjalan_penuh(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/customers', $this->payload())->assertCreated();

        $row = $this->getJson('/api/customers')->json('data.data.0');
        $ticketId = $row['id'];

        $this->putJson('/api/customers/'.$ticketId, $this->payload([
            'nama_lengkap' => 'Warga Uji Diperbarui',
            'nik' => '3201010101010002',
        ]))->assertOk();

        $this->assertDatabaseHas('installation_tickets', [
            'id' => $ticketId,
            'nik' => '3201010101010002',
            'applicant_name' => 'Warga Uji Diperbarui',
        ]);

        $this->deleteJson('/api/customers/'.$ticketId)->assertOk();

        $this->assertDatabaseMissing('installation_tickets', ['id' => $ticketId]);
    }

    /**
     * Kolom yang bisa dicari harus sama dengan yang tampil di tabel.
     *
     * index() dulu hanya memfilter `applicant_name` + `nik`, padahal UI
     * menampilkan kolom ID (customer_code) dan ALAMAT. Admin yang mengetik
     * kode pelanggan atau potongan alamat mendapat "Pelanggan Tidak
     * Ditemukan" padahal barisnya ada di tabel tepat di depannya.
     */
    #[Test]
    public function pencarian_mencakup_kode_pelanggan_dan_alamat(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $package = InstallationPackage::create([
            'name' => 'Paket Cari',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        $owner = User::create([
            'name' => 'Warga Cari',
            'email' => 'warga-cari@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $owner->id,
            'applicant_name' => 'Warga Cari Kode',
            'nik' => '3273010101010031',
            'address' => 'Jl. Melati Aman No. 45',
            'phone' => '081211112222',
            'lat' => 0,
            'lng' => 0,
            'status' => 'completed',
            'created_by' => $owner->id,
        ]);

        Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => '005.0007.200.3',
            'initial_meter_reading' => 5,
        ]);

        // Kolom ID di UI = customer_code.
        $byCode = $this->getJson('/api/customers?search='.urlencode('005.0007.200'));
        $byCode->assertOk();
        $this->assertSame(
            ['3273010101010031'],
            array_column($byCode->json('data.data'), 'nik'),
            'Mencari kode pelanggan tidak menemukan barisnya sendiri.'
        );

        // Alamat juga kolom yang tampil di tabel, jadi harus bisa dicari.
        $byAddress = $this->getJson('/api/customers?search='.urlencode('Melati Aman'));
        $byAddress->assertOk();
        $this->assertSame(
            ['3273010101010031'],
            array_column($byAddress->json('data.data'), 'nik'),
            'Mencari alamat tidak menemukan barisnya sendiri.'
        );
    }

    /**
     * Paginasi harus dihitung di server, bukan dengan menarik semua baris lalu
     * memotongnya di browser. index() mengirim `total` & `last_page` supaya
     * DataTable bisa menggambar jumlah entri dan tombol halaman yang benar.
     */
    #[Test]
    public function paginasi_dilaporkan_ke_frontend(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $packageId = $this->packageId();

        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/customers', $this->payload([
                'nik' => '320101010101000'.$i,
                'email' => 'paginasi-'.$i.'@pdam.test',
            ]))->assertCreated();
        }

        $page = $this->getJson('/api/customers?per_page=2&page=1');
        $page->assertOk();
        $this->assertCount(2, $page->json('data.data'), 'per_page tidak dipatuhi.');
        $this->assertSame(3, $page->json('data.total'), 'total tidak sesuai jumlah baris.');
        $this->assertSame(2, $page->json('data.last_page'));

        $last = $this->getJson('/api/customers?per_page=2&page=2');
        $last->assertOk();
        $this->assertCount(1, $last->json('data.data'));
    }

    /**
     * Guard: halaman ini admin-only di router. Backend juga harus menolak
     * role lain, bukan cuma menyembunyikan menu di sidebar.
     */
    #[Test]
    public function role_bukan_admin_tidak_bisa_menulis_data_pelanggan(): void
    {
        Sanctum::actingAs(User::create([
            'name' => 'Teknisi Uji',
            'email' => 'teknisi-crud@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'teknisi',
        ]), ['*']);

        $this->postJson('/api/customers', $this->payload())->assertForbidden();
        $this->deleteJson('/api/customers/1')->assertForbidden();
    }

    /**
     * RT/RW endpoint pelanggan diambil dari TIKET, bukan dari desa.
     *
     * Bug lama: search() membaca `$t->village?->rt`, padahal tabel `villages`
     * tidak punya kolom rt/rw sama sekali — jadi endpoint SELALU mengembalikan
     * rt=null rw=null walau tiketnya punya rt (mis. tiket 1593 rt='07').
     * Sumber yang benar sama dengan MonthlyBillController: `$ticket->rt`.
     */
    #[Test]
    public function rt_rw_pelanggan_diambil_dari_tiket_bukan_desa(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        // Desa sengaja dipasang supaya bila kode kembali membaca village,
        // nilai desa yang salah (mis. '99') akan ketahuan lewat assertion.
        $village = Village::create([
            'code' => '2.07',
            'village_name' => 'Sukamaju',
            'hamlet_name' => 'Dusun Satu',
        ]);

        $this->makeActiveTicket('Warga Rt Tujuh', '3273010101010077', $village->id, rt: '07', rw: '03');

        $res = $this->getJson('/api/customers/search?q=Warga Rt Tujuh');
        $res->assertOk();
        $this->assertCount(1, $res->json('data'));

        $row = $res->json('data.0');
        $this->assertSame('07', $row['rt'], 'rt harus diambil dari tiket, bukan dari desa.');
        $this->assertSame('03', $row['rw'], 'rw harus diambil dari tiket, bukan dari desa.');
    }

    /**
     * Nilai RT/RW kosong pada tiket dinormalisasi jadi null di respons:
     * string kosong, whitespace, dan placeholder '-'.
     */
    #[Test]
    public function rt_rw_kosong_dinormalisasi_jadi_null(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->makeActiveTicket('Warga Rt Kosong', '3273010101010078', null, rt: '-', rw: '');
        $this->makeActiveTicket('Warga Rt Spasi', '3273010101010079', null, rt: '   ', rw: '-');

        $plain = $this->getJson('/api/customers/search?q=Warga Rt Kosong')->json('data.0');
        $this->assertNull($plain['rt'], "rt '-' harus jadi null.");
        $this->assertNull($plain['rw'], "rw '' harus jadi null.");

        $spaced = $this->getJson('/api/customers/search?q=Warga Rt Spasi')->json('data.0');
        $this->assertNull($spaced['rt'], 'rt whitespace harus jadi null.');
        $this->assertNull($spaced['rw'], "rw '-' harus jadi null.");
    }

    /**
     * Tiket aktif (punya record customer) yang punya rt/rw di DB.
     * search() hanya menyaring status completed/suspended/terminated dan
     * membuang baris tanpa customer, jadi tiket di sini harus punya keduanya.
     */
    private function makeActiveTicket(string $name, string $nik, ?int $villageId, string $rt, string $rw): InstallationTicket
    {
        $packageId = $this->packageId();

        $owner = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $packageId,
            'user_id' => $owner->id,
            'applicant_name' => $name,
            'nik' => $nik,
            'rt' => $rt,
            'rw' => $rw,
            'address' => 'Jl. Uji RT/RW',
            'phone' => '081200000099',
            'lat' => 0,
            'lng' => 0,
            'status' => 'completed',
            'village_id' => $villageId,
            'created_by' => $owner->id,
        ]);

        Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => '005.'.substr($nik, -4).'.100.1',
            'initial_meter_reading' => 0,
        ]);

        return $ticket;
    }
}
