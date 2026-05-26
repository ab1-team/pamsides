<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user || ! in_array(trim($user->role), array_map('trim', $roles))) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role Anda: ' . ($user->role ?? 'Guest') . ', Butuh salah satu dari: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
