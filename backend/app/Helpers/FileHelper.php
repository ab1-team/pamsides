<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileHelper
{
    public static function uploadPhoto(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder, 'public');

        return url(Storage::url($path));
    }

    public static function deletePhoto(string $url): void
    {
        $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
        Storage::disk('public')->delete($path);
    }
}
