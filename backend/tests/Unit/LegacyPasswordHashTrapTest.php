<?php

namespace Tests\Unit;

use App\Console\Commands\CheckLegacyDbCommand;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Uji deteksi jebakan password '#' tanpa tanda kutip di .env.
 *
 * Password DB legacy mengandung '#'. Tanpa tanda kutip, parser .env
 * memperlakukannya sebagai awal komentar sehingga password TERPOTONG, dan
 * MySQL melaporkan "Access denied" -- menyesatkan karena host/user sudah benar.
 *
 * Method yang diuji adalah private pada command, jadi dipanggil lewat reflection.
 */
class LegacyPasswordHashTrapTest extends TestCase
{
    private CheckLegacyDbCommand $cmd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cmd = new CheckLegacyDbCommand;
    }

    /**
     * Buat file .env sementara dengan isi yang diberikan, arahkan command ke sana.
     */
    private function withEnvFile(string $contents, \Closure $assert): void
    {
        $path = tempnam(sys_get_temp_dir(), 'envtest').'.env';
        file_put_contents($path, $contents);

        // environmentFilePath() = environmentPath() . DIRECTORY_SEPARATOR . environmentFile()
        // environmentPath() memakai base_path(), jadi keduanya harus dioverride
        // lewat reflection agar file sementara di sys_get_temp_dir() yang terpakai.
        $app = new Application;
        $this->setProtected($app, 'environmentFile', basename($path));
        $this->setProtected($app, 'environmentPath', dirname($path));
        $this->cmd->setLaravel($app);

        try {
            $assert();
        } finally {
            @unlink($path);
        }
    }

    private function detect(): bool
    {
        $ref = new \ReflectionMethod($this->cmd, 'looksLikeUnquotedHashPassword');

        return (bool) $ref->invoke($this->cmd);
    }

    private function setProtected(object $obj, string $prop, mixed $value): void
    {
        $ref = new \ReflectionProperty($obj, $prop);
        $ref->setValue($obj, $value);
    }

    #[Test]
    public function password_tanpa_kutip_dengan_hash_terdeteksi(): void
    {
        $this->withEnvFile(
            "LEGACY_DB_PASSWORD=abc#def#ghi#jkl#mno#pq\n",
            function () {
                $this->assertTrue(
                    $this->detect(),
                    'Password tanpa kutip yang mengandung # harus terdeteksi'
                );
            }
        );
    }

    #[Test]
    public function password_dengan_kutip_tunggal_aman(): void
    {
        $this->withEnvFile(
            "LEGACY_DB_PASSWORD='abc#def#ghi#jkl#mno#pq'\n",
            function () {
                $this->assertFalse(
                    $this->detect(),
                    'Password yang sudah dibungkus kutip tunggal tidak boleh dilaporkan'
                );
            }
        );
    }

    #[Test]
    public function password_dengan_kutip_ganda_aman(): void
    {
        $this->withEnvFile(
            'LEGACY_DB_PASSWORD="abc#def#ghi"'."\n",
            function () {
                $this->assertFalse($this->detect());
            }
        );
    }

    #[Test]
    public function password_tanpa_hash_aman(): void
    {
        $this->withEnvFile(
            "LEGACY_DB_PASSWORD=rahasia123\n",
            function () {
                $this->assertFalse($this->detect());
            }
        );
    }

    #[Test]
    public function password_kosong_aman(): void
    {
        $this->withEnvFile(
            "LEGACY_DB_PASSWORD=\n",
            function () {
                $this->assertFalse($this->detect());
            }
        );
    }

    #[Test]
    public function hash_di_baris_lain_tidak_ikut_terdeteksi(): void
    {
        $this->withEnvFile(
            "APP_NAME=\"PAMSIDES #1\"\nLEGACY_DB_PASSWORD=rahasia123\n",
            function () {
                $this->assertFalse(
                    $this->detect(),
                    "Karakter '#' di baris lain tidak boleh memicu deteksi"
                );
            }
        );
    }
}
