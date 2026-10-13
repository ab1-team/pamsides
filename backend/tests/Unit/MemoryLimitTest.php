<?php

namespace Tests\Unit;

use App\Support\MemoryLimit;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kontrak `App\Support\MemoryLimit::ensure()` (Bug B).
 *
 * Dipakai `LegacyMigrateFastCommand` + 3 command `sync` untuk menaikkan
 * `memory_limit` proses dari default container 128M ke target yang aman.
 *
 * Kontrak yang HARUS dijaga:
 *   - hanya MENAIKKAN, tidak pernah MENURUNKAN;
 *   - nilai "-1" (unlimited) tidak pernah diperkecil;
 *   - idempoten (ensure target yang sama = tidak berubah).
 *
 * Kenapa Unit/ meski extends Tests\TestCase (bootstrap Laravel)?
 * Yang diuji adalah efek samping `ini_set` pada proses PHP CLI, sifatnya unit.
 * Bootstrap Laravel + koneksi DB tetap dibutuhkan hanya untuk BERJAGA-JAGA:
 * tes ini menolak berjalan pada DB produksi (`pamsides`), supaya kalau suatu
 * saat suite dibungkus RefreshDatabase tidak ada risiko menyentuh data
 * produksi. `tearDown()` mengembalikan `memory_limit` persis ke nilai awal.
 */
class MemoryLimitTest extends TestCase
{
    private string $originalMemoryLimit = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Pengaman keras: jangan pernah jalankan atas DB produksi.
        // DB test dijalankan dengan DB_DATABASE=pamsides_test.
        $this->assertNotSame(
            'pamsides',
            (string) DB::connection()->getDatabaseName(),
            'Tes MemoryLimit menolak berjalan pada DB produksi `pamsides`; pakai DB_DATABASE=pamsides_test.'
        );

        $this->originalMemoryLimit = (string) ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        // WAJIB: kembalikan memory_limit ke nilai semula supaya tidak
        // merusak tes/tooling lain yang berjalan setelahnya di proses ini.
        ini_set('memory_limit', $this->originalMemoryLimit);

        parent::tearDown();
    }

    private function setLimit(string $value): void
    {
        ini_set('memory_limit', $value);
        $this->assertSame($value, (string) ini_get('memory_limit'), 'prasyarat: setup memory_limit gagal');
    }

    #[Test]
    public function ensure_tidak_menurunkan_memory_limit(): void
    {
        $this->setLimit('512M');

        $result = MemoryLimit::ensure('128M');

        $this->assertFalse($result['changed'], 'ensure() dengan target lebih kecil tidak boleh mengubah apa pun');
        $this->assertSame('512M', (string) ini_get('memory_limit'), 'memory_limit tidak boleh turun');
    }

    #[Test]
    public function ensure_menaikkan_memory_limit_ke_target_yang_diberikan(): void
    {
        $this->setLimit('128M');

        $result = MemoryLimit::ensure('512M');

        $this->assertTrue($result['changed'], 'ensure() HARUS menaikkan saat target lebih besar');
        $this->assertSame('512M', (string) ini_get('memory_limit'));
        $this->assertSame('128M', $result['before']);
        $this->assertSame(512 * 1024 * 1024, $result['value']);
    }

    #[Test]
    public function ensure_tidak_menurunkan_dari_unlimited(): void
    {
        // "-1" = unlimited: tidak ada target berhingga yang boleh dianggap
        // lebih besar darinya, jadi nilai harus tetap "-1".
        $this->setLimit('-1');

        $result = MemoryLimit::ensure('512M');

        $this->assertFalse($result['changed']);
        $this->assertSame('-1', (string) ini_get('memory_limit'));
    }

    #[Test]
    public function ensure_idempoten_bila_target_sudah_terpasang(): void
    {
        $this->setLimit('512M');

        $result = MemoryLimit::ensure('512M');

        $this->assertFalse($result['changed'], 'target sama = tidak diubah');
        $this->assertSame('512M', (string) ini_get('memory_limit'));
    }
}
