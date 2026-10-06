<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MeterReading;
use Illuminate\Http\Request;

/**
 * Pembatas kepemilikan data pencatatan meter.
 *
 * Sebelumnya index/store/show/update/destroy tidak punya cek siapa
 * pencatatnya, jadi teknisi A bisa membaca, mengubah, atau menghapus
 * catatan teknisi B (dan admin). `completed()` sudah punya filter
 * least-privilege, tapi sibling-nya belum — trait ini menyamakan
 * seluruhnya.
 *
 * Admin bebas akses semua. Teknisi hanya boleh data yang ia catat
 * sendiri. Pelanggan tidak pernah sampai ke controller ini karena
 * route-nya dikunci `role:admin,teknisi`.
 */
trait AuthorizesMeterReadings
{
    /**
     * Batasi query hanya ke catatan milik user saat ini (kecuali admin).
     */
    protected function scopeMeterReadingsToOwner($query, Request $request)
    {
        if ($this->isPrivileged($request)) {
            return $query;
        }

        return $query->where('recorded_by', $request->user()?->id);
    }

    /**
     * True bila user boleh menyentuh catatan milik siapa pun.
     */
    protected function isPrivileged(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }

    /**
     * Tolak akses ke catatan milik orang lain dengan 403.
     */
    protected function ensureMeterReadingOwner(Request $request, MeterReading $reading): void
    {
        if ($this->isPrivileged($request)) {
            return;
        }

        if ((int) $reading->recorded_by !== (int) $request->user()?->id) {
            abort(403, 'Akses ditolak. Catatan meter ini milik teknisi lain.');
        }
    }
}
