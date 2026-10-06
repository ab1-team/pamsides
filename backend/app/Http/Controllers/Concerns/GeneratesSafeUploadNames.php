<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Pembuat nama file yang aman untuk upload.
 *
 * Pola lama `time().'_'.$file->getClientOriginalName()` punya dua masalah:
 *
 *  1. `getClientOriginalName()` dikontrol penuh oleh klien. Nama berisi
 *     `../` atau karakter aneh berisiko, dan tidak ada sumber nama file
 *     yang konsisten antar fitur.
 *  2. `time()` hanya presisi 1 detik, jadi dua unggahan bernama sama dari
 *     dua orang bisa menimpa file satu sama lain tanpa error.
 *
 * Catatan kontrak: fungsi ini mengembalikan NAMA FILE SAJA (bukan path
 * lengkap), karena frontend membentuk URL-nya sendiri sebagai
 * `storage/<directory>/<photo_url>`. Simpan path lengkap di sini akan
 * menghasilkan URL ganda (`survey-photos/survey-photos/...`) dan membuat
 * foto tidak tampil.
 */
trait GeneratesSafeUploadNames
{
    /**
     * Simpan gambar dan kembalikan nama file-nya (tanpa direktori).
     *
     * @param  string  $directory  Folder pada disk public.
     */
    protected function storeUploadedImage(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';

        $fileName = Str::random(40).'.'.$extension;

        $file->storeAs($directory, $fileName, 'public');

        return $fileName;
    }
}
