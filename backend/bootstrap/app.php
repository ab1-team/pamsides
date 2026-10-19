<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnforcePasswordChange;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);

        // Aktifkan throttle bawaan untuk seluruh grup `api`. Tanpa ini
        // tidak ada limiter yang berlaku pada route di routes/api.php.
        $middleware->throttleApi();

        // Wajib ganti password: ditaruh di grup `api` SETELAH `auth:sanctum`
        // milik route, sehingga hanya request ter-auth yang dinilai (request
        // login publik tanpa user tetap lolos). Lihat EnforcePasswordChange
        // untuk daftar endpoint yang dikecualikan.
        $middleware->appendToGroup('api', [
            EnforcePasswordChange::class,
        ]);

        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
