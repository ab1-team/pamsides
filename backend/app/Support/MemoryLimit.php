<?php

namespace App\Support;

/**
 * Helper `memory_limit` lintas-command.
 *
 * Command migrasi/sync legacy memuat dataset besar (puluhan ribu baris) dan
 * default container `memory_limit=128M` tidak cukup. Nilai `memory_limit`
 * adalah PHP_INI_ALL, jadi bisa dinaikkan runtime dari CLI TANPA menyentuh
 * `php.ini` global.
 *
 * Kontrak: hanya MENAIKKAN (tidak pernah menurunkan), tidak menyentuh
 * `php.ini`, dan mengembalikan deskripsi "sebelum/sesudah" untuk dicetak.
 */
final class MemoryLimit
{
    /**
     * Naikkan memory_limit proses ke $target bila nilai sekarang lebih kecil.
     *
     * @return array{before:string,after:string,value:int,changed:bool} nilai "-1" = unlimited
     */
    public static function ensure(string $target): array
    {
        $before = (string) ini_get('memory_limit');
        $beforeBytes = self::parseBytes($before);
        $targetBytes = self::parseBytes($target);

        $changed = false;
        // -1 (unlimited) jangan pernah diturunkan; hanya naikkan bila benar-benar lebih kecil.
        if ($targetBytes > 0 && $beforeBytes !== -1 && $beforeBytes < $targetBytes) {
            ini_set('memory_limit', $target);
            $changed = true;
        }

        return [
            'before' => $before,
            'after' => (string) ini_get('memory_limit'),
            'before_bytes' => $beforeBytes,
            'value' => self::parseBytes((string) ini_get('memory_limit')),
            'changed' => $changed,
        ];
    }

    /**
     * Baris ringkas untuk output command, mis. "memory_limit: 512M (semula 128M)".
     */
    public static function describe(array $result): string
    {
        return $result['changed']
            ? 'memory_limit: '.$result['after'].' (semula '.self::formatBytes($result['before_bytes']).')'
            : 'memory_limit: '.$result['after'].' (tidak diubah)';
    }

    /** Ubah "512M"/"1G"/"1024K"/angka → byte. "-1" = unlimited. */
    public static function parseBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    /** Format byte → string ringkas, mis. 134217728 → "128M". */
    public static function formatBytes(int $bytes): string
    {
        return round($bytes / 1048576).'M';
    }
}
