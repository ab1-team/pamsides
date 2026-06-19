<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileHelper
{
    public static function uploadPhoto(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'public');
    }

    public static function uploadPhotoWithUrl(UploadedFile $file, string $folder): array
    {
        $path = $file->store($folder, 'public');

        return [
            'path' => $path,
            'url' => Storage::url($path),
        ];
    }

    public static function deletePhoto(?string $urlOrPath): void
    {
        if (empty($urlOrPath)) {
            return;
        }

        $path = parse_url($urlOrPath, PHP_URL_PATH) ?: $urlOrPath;
        $path = preg_replace('#^/?storage/#', '', $path);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function toUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return Storage::url($path);
    }
}
