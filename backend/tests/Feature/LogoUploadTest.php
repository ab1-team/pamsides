<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Uji logo lembaga: unggah, penyimpanan, dan penyajian sebagai gambar.
 *
 * Latar belakang (regresi yang pernah terjadi):
 *   Logo lembaga diunggah lewat "Pengaturan > Personalisasi (SOP) >
 *   Logo & Branding". Simpan terlihat berhasil, tapi logo tidak pernah
 *   muncul di kop laporan — hanya tampil sebagai broken image.
 *
 *   Dua sebab yang tertangkap test di sini:
 *   1. Logo disajikan lewat symlink `public/storage`. Symlink itu TIDAK ikut
 *      ter-deploy ke server produksi, sehingga request `/storage/...` jatuh
 *      ke fallback SPA dan mengembalikan HTML, bukan gambar. Karena itu logo
 *      kini disajikan lewat route API yang membaca file langsung dari disk.
 *   2. `<img crossorigin="anonymous">` pada kop laporan mensyaratkan header
 *      CORS. Route yang berada di dalam `api/*` otomatis mendapatkannya;
 *      ini juga diverifikasi di sini.
 */
class LogoUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin.logo@test'],
            [
                'name' => 'Admin Logo',
                'password' => Hash::make('rahasia123'),
                'role' => 'admin',
            ],
        );
    }
    #[Test]
    public function admin_bisa_mengunggah_logo_dan_nama_file_tersimpan(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->postJson('/api/settings/sop/logo', [
                'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $fileName = $response->json('data.logo');

        $this->assertNotEmpty($fileName);
        Storage::disk('public')->assertExists('sop/logo/'.$fileName);

        // Nama file harus disimpan di settings sebagai penanda "logo tersedia".
        $this->assertSame($fileName, Setting::first()->logo);
    }
    #[Test]
    public function nama_file_logo_disanitasi_supaya_tidak_bisa_path_traversal(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->postJson('/api/settings/sop/logo', [
                'logo' => UploadedFile::fake()->create('Logo Baru.png', 10, 'image/png'),
            ]);

        $response->assertOk();

        $fileName = $response->json('data.logo');

        $this->assertStringNotContainsString(' ', $fileName);
        $this->assertStringNotContainsString('/', $fileName);
        $this->assertStringNotContainsString('..', $fileName);
        Storage::disk('public')->assertExists('sop/logo/'.$fileName);
    }
    #[Test]
    public function logo_disajikan_sebagai_gambar_dengan_header_cors(): void
    {
        Storage::fake('public');

        $fileName = 'logo-uji.png';
        Storage::disk('public')->put('sop/logo/'.$fileName, 'isi-gambar');

        $response = $this->get('/api/settings/sop/logo/'.$fileName);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');

        // Tanpa header ini, `<img crossorigin="anonymous">` di kop laporan
        // ditolak browser dan logo tampil sebagai broken image.
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }
    #[Test]
    public function endpoint_logo_terbuka_tanpa_login(): void
    {
        Storage::fake('public');

        $fileName = 'logo-publik.png';
        Storage::disk('public')->put('sop/logo/'.$fileName, 'isi-gambar');

        // Tag <img> tidak bisa mengirim header Authorization. Kalau route ini
        // butuh auth, logo akan selalu gagal dimuat di kop laporan.
        $this->get('/api/settings/sop/logo/'.$fileName)->assertOk();
    }
    #[Test]
    public function logo_yang_tidak_ada_mengembalikan_404_bukan_html(): void
    {
        Storage::fake('public');

        $this->get('/api/settings/sop/logo/tidak-ada.png')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }
    #[Test]
    public function path_traversal_pada_nama_file_ditolak(): void
    {
        Storage::fake('public');

        $this->get('/api/settings/sop/logo/..%2F..%2F.env')->assertNotFound();
    }
    #[Test]
    public function pengaturan_mengembalikan_url_logo_yang_langsung_bisa_dipakai(): void
    {
        Storage::fake('public');

        $fileName = 'logo-url.png';
        Storage::disk('public')->put('sop/logo/'.$fileName, 'isi-gambar');

        Setting::firstOrNew([])->fill(['key' => 'sop', 'logo' => $fileName])->save();

        $response = $this->actingAs($this->admin())
            ->getJson('/api/settings/sop');

        $response->assertOk();

        // Frontend memakai `logo_url` supaya tidak perlu menyusun URL sendiri.
        // Nilainya path RELATIF, bukan host absolut: `APP_URL` di server
        // produksi bisa jadi `http://localhost`, dan kalau URL-nya absolut
        // logo akan dicoba dimuat dari localhost sehingga gagal tampil.
        $this->assertSame(
            route('settings.sop.logo', ['file' => $fileName], absolute: false),
            $response->json('data.logo.logo_url'),
        );
    }
    #[Test]
    public function payload_laporan_membawa_url_logo(): void
    {
        Storage::fake('public');

        $fileName = 'logo-laporan.png';
        Storage::disk('public')->put('sop/logo/'.$fileName, 'isi-gambar');

        Setting::firstOrNew([])->fill(['key' => 'sop', 'logo' => $fileName])->save();

        $response = $this->actingAs($this->admin())
            ->postJson('/api/pelaporan/preview', [
                'tahun' => '2026',
                'bulan' => '10',
                'nama_laporan' => 'neraca',
            ]);

        $response->assertOk();

        $this->assertSame(
            route('settings.sop.logo', ['file' => $fileName], absolute: false),
            $response->json('payload.lembaga.logo_url'),
        );
    }
    #[Test]
    public function logo_lama_dihapus_hanya_setelah_logo_baru_berhasil_disimpan(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())
            ->postJson('/api/settings/sop/logo', [
                'logo' => UploadedFile::fake()->image('baru.png', 100, 100),
            ]);

        $response->assertOk();

        $newName = $response->json('data.logo');

        // Unggah kedua: file lama harus hilang, file baru tetap ada.
        $second = $this->actingAs($this->admin())
            ->postJson('/api/settings/sop/logo', [
                'logo' => UploadedFile::fake()->image('baru2.png', 100, 100),
            ]);

        $second->assertOk();

        $latestName = $second->json('data.logo');

        Storage::disk('public')->assertMissing('sop/logo/'.$newName);
        Storage::disk('public')->assertExists('sop/logo/'.$latestName);
        $this->assertSame($latestName, Setting::first()->logo);
    }
}
