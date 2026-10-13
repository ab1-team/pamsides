<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal untuk predikat "resume setelah anchor" pada pemindaian
 * transaction legacy (tabel `transactions`).
 *
 * Dipakai oleh App\Console\Commands\ImportLegacyBillPaymentsCommand.
 * Dipisah ke sini agar semantik ordering bisa diuji tanpa koneksi DB.
 *
 * Konteks: pemindaian diurutkan (usage_id, tgl_transaksi, id). Untuk melanjutkan
 * setelah baris terakhir yang sudah diproses, kondisi resume HARUS berbentuk
 * tuple-comparison yang strictly greater -- hanya seperti ini baris yang sudah
 * terakumulasi tidak akan pernah dibaca ulang (aman dari double count), dan grup
 * yang sedang dibangun tetap bisa dilanjutkan utuh setelah koneksi terputus.
 */
class LegacyTransactionCursor
{
    /**
     * Terapkan kondisi "baris setelah anchor" pada query builder.
     *
     * @param  Builder  $q
     * @param  array{0:int,1:?string,2:int}|null  $after  [usage_id, tgl_transaksi, id]
     * @return Builder
     */
    public static function applyAfter($q, ?array $after)
    {
        if ($after === null) {
            return $q;
        }

        [$au, $at, $ai] = $after;

        return $q->where(function ($w) use ($au, $at, $ai) {
            $w->where('usage_id', '>', $au)
                ->orWhere(function ($q2) use ($au, $at, $ai) {
                    $q2->where('usage_id', '=', $au);
                    if ($at === null) {
                        $q2->whereNotNull('tgl_transaksi');
                    } else {
                        $q2->where(function ($q3) use ($at, $ai) {
                            $q3->where('tgl_transaksi', '>', $at)
                                ->orWhere(function ($q4) use ($at, $ai) {
                                    $q4->where('tgl_transaksi', '=', $at)->where('id', '>', $ai);
                                });
                        });
                    }
                });
        });
    }

    /**
     * Padanan PHP dari {@see self::applyAfter()} untuk pengujian & verifikasi.
     *
     * Harus selalu agreeing dengan versi SQL di atas.
     *
     * @param  array<int,array>  $rows
     * @param  array{0:int,1:?string,2:int}|null  $after
     * @return array<int,array> baris yang lolos (urutan asli dipertahankan)
     */
    public static function filterAfter(array $rows, ?array $after): array
    {
        if ($after === null) {
            return $rows;
        }

        [$au, $at, $ai] = $after;

        return array_values(array_filter($rows, function (array $r) use ($au, $at, $ai) {
            if ((int) $r['usage_id'] > $au) {
                return true;
            }
            if ((int) $r['usage_id'] !== $au) {
                return false;
            }
            if ($at === null) {
                return $r['tgl_transaksi'] !== null;
            }
            if ($r['tgl_transaksi'] > $at) {
                return true;
            }

            return $r['tgl_transaksi'] === $at && (int) $r['id'] > $ai;
        }));
    }
}
