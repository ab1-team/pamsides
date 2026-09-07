<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Village extends Model
{
    protected $fillable = [
        'code',
        'village_name',
        'hamlet_name',
        'address',
        'phone',

    ];

    protected static function booted(): void
    {
        static::creating(function (Village $village) {
            if (empty($village->code)) {
                $village->code = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $max = DB::table('villages')
            ->where('code', 'like', '999.%')
            ->selectRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) AS n')
            ->orderByDesc('n')
            ->value('n');

        $next = ((int) ($max ?? 0)) + 1;
        return '999.'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate customer_code untuk desa ini.
     *
     * Format: <kecamatan>.<desa>.<X00>.<counter>
     *   - <kecamatan> : segment ke-1 village.code (mis. "005")
     *   - <desa>      : segment ke-2 village.code (mis. "0001")
     *   - <X00>       : digit terakhir segment ke-3 + "00" (mis. segment3="0001" -> "100")
     *   - <counter>   : urut customer per desa (mulai dari 1, tanpa padding)
     *
     * Contoh village.code = "005.0001.0001":
     *   segment3 = "0001" -> digit terakhir = "1" -> X00 = "100"
     *   customer ke-1   : "005.0001.100.1"
     *   customer ke-2   : "005.0001.100.2"
     *   customer ke-100 : "005.0001.100.100"
     *
     * Untuk village dgn 2 segment saja (mis. "999.001") -> pakai segment2 digit terakhir:
     *   village.code = "999.001" -> segment2 = "001" -> digit terakhir = "1" -> X00 = "100"
     *   customer ke-1: "999.100.1"
     *
     * Counter dihitung dari suffix terbesar customer_code yg sudah ada
     * untuk prefix yg sama.
     */
    public function generateNextCustomerCode(): string
    {
        $segments = explode('.', $this->code);
        if (count($segments) < 2) {
            throw new \RuntimeException("Village code '{$this->code}' tidak punya cukup segment (min: kec.desa)");
        }

        $kecamatan = $segments[0];
        $desa = $segments[1];

        // Ambil "X00" dari digit terakhir segment ketiga (kalau ada) atau segment kedua
        $sumberSegment = $segments[2] ?? $segments[1];
        $lastDigit = substr(trim($sumberSegment), -1);
        $x00 = $lastDigit.'00';

        $prefix = "{$kecamatan}.{$desa}.{$x00}.";

        $maxSuffix = DB::table('customers')
            ->where('customer_code', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(customer_code, '.(strlen($prefix) + 1).') AS UNSIGNED)) AS n')
            ->value('n');

        $next = ((int) ($maxSuffix ?? 0)) + 1;
        $code = $prefix.$next;

        // Defensive: pastikan unik (loop collisions)
        while (DB::table('customers')->where('customer_code', $code)->exists()) {
            $next++;
            $code = $prefix.$next;
        }

        return $code;
    }

    public static function generateCustomerCodeForVillageId(int $villageId): string
    {
        $village = static::find($villageId);
        if (! $village) {
            throw new \RuntimeException("Village id={$villageId} not found");
        }
        return $village->generateNextCustomerCode();
    }

    public function kecamatan()
    {
        return $this->belongsTo(Setting::class, 'setting_id');
    }

    public function tickets()
    {
        return $this->hasMany(InstallationTicket::class);
    }
}

