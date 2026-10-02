<?php

namespace App\Services;

use App\Models\SignatureSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * SignatureService - pusat manajemen tanda tangan digital PAMSIDES.
 *
 * Konsep identik dengan SIUPK-Next:
 *   - Template HTML per laporan (rich text editor dengan tabel Mengetahui/Dibuat/Bendahara).
 *   - Image tanda tangan (PNG/JPG/WebP, max 2 MB) yang otomatis diinjeksi ke template.
 *
 * Image disimpan di storage/app/public/signatures/{report_key}.{ext} dengan
 * symlink public/storage.
 */
class SignatureService
{
    private const DISK = 'public';

    private const DIR = 'signatures';

    private const MIME_MAP = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    private const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct() {}

    /* ====================== TEMPLATE ====================== */

    /**
     * Ambil semua template (default value kalau record belum ada).
     *
     * @return array<string, string>
     */
    public function templates(): array
    {
        $rows = SignatureSetting::query()->get()->keyBy('report_key');

        $out = [];
        foreach (array_keys(SignatureSetting::REPORT_TYPES) as $key) {
            $row = $rows->get($key);
            $out[$key] = $row && $row->template_html ? (string) $row->template_html : '';
        }

        return $out;
    }

    /**
     * Ambil template untuk satu report; fallback ke 'default' kalau belum di-set.
     */
    public function template(string $reportKey): string
    {
        $all = $this->templates();
        $html = $all[$reportKey] ?? '';
        if ($html !== '') {
            return $html;
        }
        if ($reportKey !== 'default') {
            return $all['default'] ?? '';
        }

        return '';
    }

    /**
     * Simpan template untuk beberapa report sekaligus.
     *
     * @param  array<string, string|null>  $templates
     */
    public function saveTemplates(array $templates): void
    {
        DB::transaction(function () use ($templates) {
            foreach (array_keys(SignatureSetting::REPORT_TYPES) as $key) {
                $raw = $templates[$key] ?? '';
                if (! is_string($raw)) {
                    $raw = '';
                }
                $clean = $this->sanitizeHtml($raw);

                $row = SignatureSetting::firstOrNew(['report_key' => $key]);
                $row->report_key = $key;
                $row->label = SignatureSetting::REPORT_TYPES[$key];
                $row->template_html = $clean;
                $row->save();
            }
        });
    }

    /* ====================== IMAGE ====================== */

    /**
     * Ambil path image untuk beberapa report sekaligus (key => path|null).
     *
     * @return array<string, string|null>
     */
    public function imagePaths(): array
    {
        $rows = SignatureSetting::query()->get()->keyBy('report_key');

        $out = [];
        foreach (array_keys(SignatureSetting::REPORT_TYPES) as $key) {
            $row = $rows->get($key);
            $out[$key] = $row && $row->image_path ? (string) $row->image_path : null;
        }

        return $out;
    }

    public function imagePath(string $reportKey): ?string
    {
        $all = $this->imagePaths();
        $path = $all[$reportKey] ?? null;
        if ($path === null && $reportKey !== 'default') {
            $path = $all['default'] ?? null;
        }

        return $path;
    }

    /**
     * Simpan tanda tangan gambar (data URI inline base64) untuk report tertentu.
     */
    public function storeImage(string $reportKey, string $dataUri): string
    {
        $this->assertReportKey($reportKey);

        [$binary, $extension] = $this->decodeDataUri($dataUri);
        $this->assertValidImage($binary, $extension);

        $this->deleteImage($reportKey, false);

        $path = self::DIR.'/'.$reportKey.'.'.$extension;
        Storage::disk(self::DISK)->put($path, $binary);

        $row = SignatureSetting::firstOrNew(['report_key' => $reportKey]);
        $row->report_key = $reportKey;
        $row->label = SignatureSetting::REPORT_TYPES[$reportKey];
        $row->image_path = $path;
        $row->image_mime = self::MIME_MAP[$extension] ?? 'image/png';
        $row->image_size = strlen($binary);
        $row->save();

        return $path;
    }

    public function deleteImage(string $reportKey, bool $persist = true): void
    {
        $this->assertReportKey($reportKey);

        $path = $this->imagePath($reportKey);
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }

        if (! $persist) {
            return;
        }

        $row = SignatureSetting::firstOrNew(['report_key' => $reportKey]);
        $row->report_key = $reportKey;
        $row->label = SignatureSetting::REPORT_TYPES[$reportKey];
        $row->image_path = null;
        $row->image_mime = null;
        $row->image_size = null;
        $row->save();
    }

    /**
     * Generate data URI inline (base64) untuk disisipkan ke HTML laporan.
     * Aman dipakai tanpa暴露 path storage ke client.
     */
    public function imageDataUri(string $reportKey): ?string
    {
        $path = $this->imagePath($reportKey);
        if ($path === null) {
            return null;
        }
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $binary = Storage::disk(self::DISK)->get($path);
        if ($binary === null || $binary === '') {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME_MAP[$ext] ?? 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * URL publik untuk image tanda tangan (untuk preview UI).
     */
    public function imageUrl(string $reportKey): ?string
    {
        $path = $this->imagePath($reportKey);
        if ($path === null) {
            return null;
        }

        return asset('storage/'.$path);
    }

    /**
     * Inject image tanda tangan ke template HTML.
     * - Jika template memuat placeholder `{ttd_image}`, replace.
     * - Else: cari baris kosong pertama (`<p><br><br></p>` pattern) dan inject di atasnya.
     */
    public function renderForReport(string $reportKey): string
    {
        $html = $this->template($reportKey);
        if ($html === '') {
            return '';
        }

        $uri = $this->imageDataUri($reportKey);
        if ($uri === null) {
            return $html;
        }

        $img = '<img src="'.$uri.'" style="height:50px;max-width:180px;object-fit:contain" alt="Tanda Tangan" />';

        if (str_contains($html, '{ttd_image}')) {
            return str_replace('{ttd_image}', $img, $html);
        }

        // Sisipkan sebelum baris kosong pertama yang berisi pattern "<p><br/><br/></p>".
        $pattern = '/(<p>(<br\s*\/?>\s*)+<\/p>)/i';
        $replaced = preg_replace($pattern, $img.'$1', $html, 1);
        if ($replaced !== null) {
            return $replaced;
        }

        return $html;
    }

    /**
     * Map view_target dari PelaporanController ke signature report_key.
     * Tutup buku, sub_laporan, dll. di-map ke key turunan.
     */
    public function resolveReportKey(?string $viewTarget, ?string $subLaporan = null): string
    {
        if (! $viewTarget) {
            return 'default';
        }

        // Tutup buku: pilih sub_laporan yang sesuai
        if ($viewTarget === 'tutup_buku') {
            return match ($subLaporan) {
                'alokasi_laba'    => 'tutup_buku_alokasi_laba',
                'jurnal_tutup_buku' => 'tutup_buku_jurnal',
                'neraca_tutup_buku' => 'tutup_buku_neraca',
                'laba_rugi_tutup_buku' => 'tutup_buku_laba_rugi',
                'CALK_tutup_buku' => 'tutup_buku_calk',
                default           => 'tutup_buku_neraca',
            };
        }

        // Aliases
        $aliases = [
            'calk'       => 'calkk',
            'LPM'        => 'perubahan_modal',
            'perubahan_modal' => 'perubahan_modal',
        ];

        $key = $aliases[$viewTarget] ?? $viewTarget;

        return array_key_exists($key, SignatureSetting::REPORT_TYPES) ? $key : 'default';
    }

    /* ====================== HELPERS ====================== */

    private function assertReportKey(string $reportKey): void
    {
        if (! array_key_exists($reportKey, SignatureSetting::REPORT_TYPES)) {
            throw new RuntimeException("Jenis laporan tidak dikenal: {$reportKey}");
        }
    }

    /**
     * Decode data URI inline ke binary + extension.
     *
     * @return array{0: string, 1: string}
     */
    private function decodeDataUri(string $dataUri): array
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,(.+)$#is', trim($dataUri), $m)) {
            throw new RuntimeException('Format tanda tangan harus data URI PNG/JPG/WebP base64.');
        }

        $rawType = strtolower($m[1]);
        $extension = $rawType === 'jpeg' ? 'jpg' : $rawType;
        $decoded = base64_decode(str_replace(' ', '+', $m[2]), true);

        if ($decoded === false || $decoded === '') {
            throw new RuntimeException('Payload gambar tanda tangan rusak.');
        }

        return [$decoded, $extension];
    }

    private function assertValidImage(string $binary, string $extension): void
    {
        if (strlen($binary) > self::MAX_BYTES) {
            throw new RuntimeException('Ukuran gambar tanda tangan maksimal 2 MB.');
        }

        $head = substr($binary, 0, 12);
        $valid = match ($extension) {
            'png' => str_starts_with($head, "\x89PNG\r\n\x1A\n"),
            'jpg', 'jpeg' => str_starts_with($head, "\xFF\xD8\xFF"),
            'webp' => strlen($binary) > 12
                && str_starts_with(substr($binary, 0, 4), 'RIFF')
                && str_starts_with(substr($binary, 8, 4), 'WEBP'),
            default => false,
        };

        if (! $valid) {
            throw new RuntimeException('Isi file bukan file gambar yang valid.');
        }
    }

    private function sanitizeHtml(string $html): string
    {
        // Buang seluruh blok <script>...</script> (termasuk inner text) sebelum
        // strip_tags, karena strip_tags hanya membuang tag pembungkus, bukan
        // teks di dalamnya. Sama untuk <style>.
        $withoutDanger = preg_replace('#<script\b[^>]*>.*?</script>#si', '', $html) ?? $html;
        $withoutDanger = preg_replace('#<style\b[^>]*>.*?</style>#si', '', $withoutDanger) ?? $withoutDanger;

        // Allowlist tag — Quill output bisa berisi heading, link, blockquote.
        // href diizinkan hanya untuk http/https/mailto (lihat filter di bawah).
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li>'
            .'<table><thead><tbody><tr><th><td>'
            .'<span><div><img>'
            .'<h1><h2><h3><h4><h5><h6>'
            .'<a>'
            .'<blockquote><pre>';
        $stripped = strip_tags($withoutDanger, $allowed);

        // Drop seluruh atribut non-standar (class, style, id, dll.) — hanya
        // href (untuk <a>) dan src/alt (untuk <img>) yang dipertahankan.
        $stripped = $this->cleanAttributes($stripped);

        // Drop event handlers / javascript: URLs (safety net)
        $stripped = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/\s+(href|src)\s*=\s*("\s*javascript:[^"]*"|\'\s*javascript:[^\']*\'|javascript:[^\s>]+)/i', '', $stripped) ?? $stripped;

        // Validasi href pada <a>: hanya http/https/mailto yang diizinkan.
        // Drop seluruh <a> yang href-nya bukan protocol aman.
        $stripped = preg_replace_callback(
            '#<a\s+[^>]*href\s*=\s*"([^"]*)"[^>]*>.*?</a>#si',
            function ($m) {
                return preg_match('#^(https?:|mailto:)#i', $m[1]) ? $m[0] : '';
            },
            $stripped,
        ) ?? $stripped;

        // Hanya izinkan src data:image/(png|jpeg|jpg|webp);base64,.. pada <img>
        if (preg_match_all('/<img\s+[^>]*src\s*=\s*"([^"]*)"/i', $stripped, $matches)) {
            foreach ($matches[1] as $src) {
                if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $src)) {
                    $stripped = preg_replace('#<img\s+[^>]*src\s*=\s*"'.preg_quote($src, '#').'"[^>]*>#i', '', $stripped) ?? $stripped;
                }
            }
        }

        if (strlen($stripped) > 50_000) {
            $stripped = substr($stripped, 0, 50_000);
        }

        return trim($stripped);
    }

    /**
     * Hapus atribut non-essential dari setiap tag, sisakan hanya href (a),
     * src/alt (img), dan align (p/td/th — untuk layout tabel tanda tangan).
     */
    private function cleanAttributes(string $html): string
    {
        return preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b([^>]*)>/i',
            function ($m) {
                $tag = strtolower($m[1]);
                $attrs = $m[2];

                $allowed = [];
                if ($tag === 'a') {
                    if (preg_match('/\bhref\s*=\s*"([^"]*)"/i', $attrs, $h)) {
                        $allowed[] = 'href="'.htmlspecialchars($h[1], ENT_QUOTES).'"';
                    }
                } elseif ($tag === 'img') {
                    if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $attrs, $s)) {
                        $allowed[] = 'src="'.htmlspecialchars($s[1], ENT_QUOTES).'"';
                    }
                    if (preg_match('/\balt\s*=\s*"([^"]*)"/i', $attrs, $a)) {
                        $allowed[] = 'alt="'.htmlspecialchars($a[1], ENT_QUOTES).'"';
                    }
                } elseif (in_array($tag, ['p', 'td', 'th', 'tr', 'tbody', 'thead', 'table'], true)) {
                    // Layout tabel tanda tangan butuh alignment per cell/row
                    if (preg_match('/\balign\s*=\s*"([^"]*)"/i', $attrs, $al)) {
                        $val = strtolower($al[1]);
                        if (in_array($val, ['left', 'center', 'right', 'justify'], true)) {
                            $allowed[] = 'align="'.$val.'"';
                        }
                    }
                }

                $attrStr = $allowed ? ' '.implode(' ', $allowed) : '';

                return '<'.$m[1].$attrStr.'>';
            },
            $html,
        ) ?? $html;
    }
}