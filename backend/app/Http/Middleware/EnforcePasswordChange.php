<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blokir seluruh request API ter-auth selama user masih memakai password
 * bawaan (must_change_password = true).
 *
 * Pengecualian (kontrak B3/B4): logout, lihat profil (GET /me), ganti
 * password (PUT /me/password), dan refresh token -- supaya user yang
 * terblokir tetap bisa keluar, melihat identitasnya, dan mengganti password.
 * Request tanpa user (route publik seperti login) selalu lolos.
 */
class EnforcePasswordChange
{
    /**
     * Path yang tetap boleh diakses saat wajib ganti password.
     * Dicocokkan tanpa prefix `api/`.
     */
    private const ALLOWED = [
        'logout' => ['POST'],
        'me' => ['GET'],
        'me/password' => ['PUT'],
        'refresh' => ['POST'],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user || ! (bool) $user->must_change_password) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }
        $allowedMethods = self::ALLOWED[$path] ?? [];

        if (in_array($request->getMethod(), $allowedMethods, true)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Anda wajib mengganti password terlebih dahulu.',
        ], 403);
    }
}
