<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InstallationPackage;
use App\Models\InstallationTicket;
use App\Models\MonthlyBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hazard khusus pada halaman Ubah & Hapus /app/data-pelanggan.
 *
 * Berbeda dengan CustomerCrudTest yang menyorot rantai Tambah -> Daftar, file
 * ini memakai pelanggan aktif (sudah punya customers + customer_code) karena
 * itulah keadaan riil saat admin menekan tombol Ubah/Hapus di UI.
 */
class PelangganEditHapusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'email' => 'admin-ubah@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);
    }

    /**
     * Pelanggan aktif: tiket `completed` + record customers (customer_code).
     * Inilah baris yang tampil di /app/data-pelanggan.
     */
    private function pelangganAktif(string $customerCode = '005.0001.100.1'): InstallationTicket
    {
        $package = InstallationPackage::create([
            'name' => 'Paket Uji',
            'installation_fee' => 1_000_000,
            'monthly_abodemen' => 10_000,
            'late_penalty' => 5_000,
        ]);

        $owner = User::create([
            'name' => 'Warga Aktif',
            'email' => uniqid().'@pdam.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'pelanggan',
        ]);

        $ticket = InstallationTicket::create([
            'package_id' => $package->id,
            'user_id' => $owner->id,
            'applicant_name' => 'Warga Aktif',
            'nik' => '3273010101010001',
            'address' => 'Jl. Aktif No. 1',
            'phone' => '081200000001',
            'lat' => -7.797068,
            'lng' => 110.370529,
            'status' => 'completed',
            'created_by' => $owner->id,
        ]);

        Customer::create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'customer_code' => $customerCode,
            'initial_meter_reading' => 10,
        ]);

        return $ticket;
    }

    /**
     * Kolom "ID" di UI diisi `mapRow()` dengan `customer_code || id`, dan
     * handleEdit() memakainya untuk membuka /customers/{id}. Untuk pelanggan
     * aktif itu berarti customer_code, sementara show() mencari tiket dengan
     * findOrFail($id). Akibatnya tombol Ubah melompat ke 404.
     */
    #[Test]
    public function tombol_ubah_pelanggan_aktif_tidak_menghasilkan_404(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $this->pelangganAktif();

        $row = $this->getJson('/api/customers')->json('data.data.0');

        // Replikasi mapRow() di usePelanggan.js: `id: c.customer_code || c.id`.
        $editId = $row['customer_code'] ?: $row['id'];
        $this->assertSame(
            '005.0001.100.1',
            $editId,
            'Baris daftar untuk pelanggan aktif memakai customer_code — inilah link ke form Ubah.'
        );

        $this->getJson('/api/customers/'.$editId)
            ->assertOk('Form Ubah gagal dibuka untuk pelanggan aktif (id dari daftar = customer_code).');
    }

    /**
     * Pelanggan aktif punya tagihan. Hapus harus berhenti dengan 409 + pesan
     * yang bisa dibaca, bukan diam-diam menghapus riwayat tagihan.
     */
    #[Test]
    public function hapus_pelanggan_aktif_tidak_menghapus_tagihannya(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $ticket = $this->pelangganAktif();
        $customer = Customer::where('ticket_id', $ticket->id)->first();

        MonthlyBill::create([
            'customer_id' => $customer->id,
            'billing_period_year' => 2026,
            'billing_period_month' => 7,
            'meter_reading_start' => 10,
            'meter_reading_end' => 25,
            'usage_m3' => 15,
            'usage_charge' => 45_000,
            'abodemen' => 15_000,
            'penalty_amount' => 0,
            'total_amount' => 60_000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->deleteJson('/api/customers/'.$ticket->id);
        $response->assertStatus(409);

        $this->assertDatabaseHas('monthly_bills', ['customer_id' => $customer->id]);
    }

    /**
     * Form Ubah mengosongkan password dengan sengaja
     * ("Kosongkan jika tidak diubah"). update() harus membiarkan password
     * lama utuh — bukan mengubahnya jadi apa pun yang dikirim form.
     */
    #[Test]
    public function password_kosong_di_form_ubah_tidak_mengubah_password_lama(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $ticket = $this->pelangganAktif();
        $original = Hash::make('rahasia123');
        $ticket->user->update(['password' => $original]);

        // Nilainya datang dari PelangganEdit.vue yang mengosongkan password.
        $this->putJson('/api/customers/'.$ticket->id, [
            'nik' => '3273010101010001',
            'nama_lengkap' => 'Warga Aktif Diperbarui',
            'email' => $ticket->user->email,
            'password' => '',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-05-06',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000001',
            'alamat_lengkap' => 'Jl. Aktif No. 1',
        ])->assertOk();

        $this->assertTrue(
            Hash::check('rahasia123', $ticket->user->fresh()->password),
            'Password pelanggan berubah padahal form Ubah sengaja dikosongkan.'
        );
    }

    /**
     * Kolom tanggal lahir kosong tidak boleh jadi '-'/epoch. Form Ubah
     * menampilkan placeholder "Pilih Tanggal" dan boleh dibiarkan kosong.
     */
    #[Test]
    public function tanggal_lahir_kosong_di_form_ubah_tidak_jadi_tahun_1970(): void
    {
        Sanctum::actingAs($this->admin(), ['*']);

        $ticket = $this->pelangganAktif();

        $this->putJson('/api/customers/'.$ticket->id, [
            'nik' => '3273010101010001',
            'nama_lengkap' => 'Warga Aktif',
            'email' => $ticket->user->email,
            'password' => '',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '',
            'jenis_kelamin' => 'Laki-laki',
            'no_telp' => '081200000001',
            'alamat_lengkap' => 'Jl. Aktif No. 1',
        ])->assertOk();

        $this->assertNull(
            $ticket->fresh()->birth_date,
            'Tanggal lahir kosong tersimpan sebagai epoch/1970 — data yang tidak ada jadi terlihat terisi.'
        );
    }
}
