<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * LogoService - pusat pengelolaan logo lembaga PAMSIDES.
 *
 * Kenapa service ini ada (bukan sekadar `asset('storage/...')`):
 *
 *   1. Link symlink `public/storage` TIDAK ikut ter-deploy ke server produksi.
 *      `backend/.gitignore` mengabaikan `/public/storage`, dan workflow
 *      `.github/workflows/deploy.yml` tidak menjalankan `php artisan
 *      storage:link`. Akibatnya request ke `/storage/sop/logo/x.png` jatuh
 *      ke `try_files ... /index.php` milik nginx dan mengembalikan HTML SPA —
 *      bukan gambar. Browser kemudian menampilkan "broken image" (logo hilang).
 *
 *   2. File berada di disk `public` (storage/app/public) yang TIDAK otomatis
 *      bisa dibaca lewat HTTP. Endpoint di bawah membacanya langsung dari
 *      disk dan streamed ke klien, jadi tidak butuh symlink sama sekali.
 *
 *   3. `config/cors.php` hanya mengizinkan `api/*`. `<img crossorigin="anonymous">`
 *      pada kop laporan (BaseReportLayout) mensyaratkan header CORS. Dengan
 *      file dilayani lewat route di dalam `api/*`, header CORS otomatis ikut
 *      sehingga `<img>` tidak ditolak browser.
 *
 * Alur logo:
 *   - Unggah: POST /api/settings/sop/logo  -> `SopController::updateLogo`
 *   - Baca  : GET  /api/settings/sop/logo  -> `SopController::showLogo`
 *   - URL   : `route('settings.sop.logo')` -> dipakai frontend & payload laporan.
 */
class LogoService
{
    public const DISK = 'public';

    public const DIR = 'sop/logo';

    /**
     * MIME type yang diizinkan untuk logo.
     */
    private const MIME_MAP = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
    ];

    /**
     * Path relatif file logo di dalam disk `public`.
     */
    public function path(?string $fileName): ?string
    {
        $fileName = $this->sanitizeFileName($fileName);

        if ($fileName === null) {
            return null;
        }

        return self::DIR.'/'.$fileName;
    }

    /**
     * Cek apakah file logo benar-benar ada di disk.
     */
    public function exists(?string $fileName): bool
    {
        $path = $this->path($fileName);

        if ($path === null) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Bangun response HTTP yang streamed dari disk.
     *
     * Return null kalau nama file tidak valid atau file tidak ada, supaya
     * controller bisa membalas 404 dengan rapi.
     */
    public function response(?string $fileName): ?Response
    {
        $path = $this->path($fileName);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        $mime = self::MIME_MAP[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        $response = new Response($disk->get($path), SymfonyResponse::HTTP_OK, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);

        // Logo boleh lintas origin karena frontend (SPA) dan backend sering
        // berada di origin berbeda (mis. localhost:5173 vs 127.0.0.1:8000).
        // Tanpa ini, `<img crossorigin="anonymous">` di kop laporan akan
        // ditolak dan tampil sebagai broken image.
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', '*');

        return $response;
    }

    /**
     * Nama file logo yang aman untuk dipakai di URL.
     *
     * Mencegah path traversal (`../`) dan mengembalikan null untuk input
     * kosong/tidak valid.
     */
    public function sanitizeFileName(?string $fileName): ?string
    {
        if ($fileName === null) {
            return null;
        }

        $fileName = trim($fileName);

        if ($fileName === '') {
            return null;
        }

        // Hanya izinkan nama file sederhana (tanpa direktori).
        $base = basename(str_replace('\\', '/', $fileName));

        if ($base === '' || $base === '.' || $base === '..') {
            return null;
        }

        // Tolak karakter yang bisa dipakai untuk menyelinap path.
        if (preg_match('/[^A-Za-z0-9._-]/', $base)) {
            return null;
        }

        return $base;
    }

    /**
     * URL logo untuk ditampilkan di laporan/cetak.
     *
     * Mengembalikan path RELATIF (tanpa host), bukan URL absolut. Alasannya:
     * `route()` absolut bergantung pada `APP_URL`, yang di server produksi
     * bisa jadi `http://localhost` — kalau begitu logo akan dicoba dimuat
     * dari localhost dan gagal tampil. Path relatif membuat frontend
     * menyusunnya terhadap origin API yang sedang dipakai, jadi selalu
     * benar di lokal maupun produksi.
     *
     * Kalau file tidak ada di disk, kembalikan null supaya frontend bisa
     * memakai fallback (inisial) alih-alih menampilkan broken image.
     */
    public function url(?string $fileName): ?string
    {
        $path = $this->path($fileName);

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return route('settings.sop.logo', ['file' => basename($path)], absolute: false);
    }
}
