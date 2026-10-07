<?php

namespace Tests\Feature;

use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Perilaku "isi 0 atau -" yang tertulis di catatan bawah form Tambah/Ubah.
 *
 * PelangganCreate.vue dan PelangganEdit.vue sama-sama mengganti setiap kolom
 * kosong dengan '0' atau '-' sebelum mengirim. Form ini punya banyak kolom
 * opsional, jadi jalur ini yang paling sering dipakai user — harus diuji apa
 * yang terjadi ke database.
 */
class PelangganKosongTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-kosong@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);
    }

    private function packageId(): int
    {
        return InstallationPackage::create([
            'name' => 'Paket Uji',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ])->id;
    }

    /**
     * Kolom tanggal lahir dibiarkan kosong di form, jadi setelah normalisasi
     * isinya jadi '-' (bukan string kosong). Backend harus memperlakukannya
     * sebagai "tidak diisi" — bukan tanggal.
     */
    #[Test]
    public function tempat_lahir_kosong_jadi_tanda_hubung_tidak_menjadi_teks_aneh(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/customers', [
            'nik' => '3273010101010011',
            'nama_lengkap' => 'Tanpa Tempat Lahir',
            'email' => 'tanpa-tempat@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => '-',
            'tgl_lahir' => '-',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '0',
            'alamat_lengkap' => 'Jl. Kosong No. 1',
            'package_id' => $this->packageId(),
        ])->assertCreated();

        $ticket = InstallationTicket::where('nik', '3273010101010011')->first();

        $this->assertNotNull($ticket, 'Pelanggan dengan kolom kosong gagal disimpan.');
        $this->assertNull(
            $ticket->birth_date,
            'Tanggal lahir kosong tersimpan sebagai tanggal Palsu — dashboard/filter tahun jadi salah.'
        );
    }

    /**
     * Dua pelanggan dengan NIK sama berarti ambiguous di mana-mana: daftar,
     * pencarian tagihan, dan pengecekan NIK di form Tambah. Yang kedua harus
     * ditolak, bukan diam-diam dibuatkan.
     */
    #[Test]
    public function nik_ganda_ditolak_dengan_pesan_yang_jelas(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $payload = [
            'nama_lengkap' => 'NIK Kembar',
            'email' => 'nik-kembar-1@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-05-06',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000002',
            'alamat_lengkap' => 'Jl. Kembar No. 1',
            'package_id' => $this->packageId(),
        ];

        // Catatan: pakai array_merge, BUKAN operator `+`. `+` tidak
        // menimpa key yang sudah ada, jadi email kedua diam-diam tetap sama
        // dan penolakan datang dari unique:users,email — bukan dari NIK.
        $this->postJson('/api/customers', array_merge($payload, [
            'nik' => '3273010101010099',
            'email' => 'nik-kembar-1@pdam.test',
        ]))->assertCreated();

        $response = $this->postJson('/api/customers', array_merge($payload, [
            'nik' => '3273010101010099',
            'email' => 'nik-kembar-2@pdam.test',
        ]));

        $response->assertStatus(422, 'NIK ganda harus ditolak, bukan dibuatkan pelanggan kedua.');

        $this->assertSame(
            1,
            InstallationTicket::where('nik', '3273010101010099')->count(),
            'Terdapat lebih dari satu tiket dengan NIK yang sama.'
        );
    }

    /**
     * Form Tambah menampilkan "NIK sudah digunakan" lalu mengisi data lama
     * dari pelanggan tersebut. Kalau NIK ganda tetap bisa disimpan lewat
     * API, cek itu hanya hiasan.
     */
    #[Test]
    public function nik_yang_sudah_ada_tidak_bisa_dipakai_ulang(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $packageId = $this->packageId();

        $this->postJson('/api/customers', [
            'nik' => '3273010101010088',
            'nama_lengkap' => 'Pemilik NIK',
            'email' => 'pemilik-nik@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-05-06',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000003',
            'alamat_lengkap' => 'Jl. Asli No. 1',
            'package_id' => $packageId,
        ])->assertCreated();

        $this->postJson('/api/customers', [
            'nik' => '3273010101010088',
            'nama_lengkap' => 'Pemakai NIK',
            'email' => 'pemakai-nik@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-05-06',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000004',
            'alamat_lengkap' => 'Jl. Palsu No. 2',
            'package_id' => $packageId,
        ])->assertStatus(422);

        // Pelanggan pertama tidak boleh ikut berubah.
        $this->assertDatabaseHas('installation_tickets', [
            'nik' => '3273010101010088',
            'applicant_name' => 'Pemilik NIK',
        ]);
    }

    /**
     * Gender di form dikirim sebagai "Laki-laki"/"Perempuan". Kalau kosong,
     * form Tambah masih mengirim nilai itu apa adanya; kolom gender di DB
     * nullable, jadi tidak boleh jadi error 500.
     */
    #[Test]
    public function gender_tidak_dikenal_ditolak_bukan_memicu_error(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->postJson('/api/customers', [
            'nik' => '3273010101010077',
            'nama_lengkap' => 'Gender Aneh',
            'email' => 'gender-aneh@pdam.test',
            'password' => 'rahasia123',
            'tempat_lahir' => '-',
            'tgl_lahir' => '-',
            'jenis_kelamin' => '-',
            'no_telp' => '0',
            'alamat_lengkap' => '-',
            'package_id' => $this->packageId(),
        ])->assertStatus(422);
    }
}