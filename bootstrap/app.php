<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance(except: [
            'login', 'logout', 'admin', 'admin/*', 'payments/midtrans/notification',
        ]);
        $middleware->trimStrings(except: ['smtp_password', 'sandbox_server_key', 'production_server_key']);
        $middleware->preventRequestForgery(except: ['payments/midtrans/notification']);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Pengguna yang belum login diarahkan ke halaman login, dan kembali ke
        // halaman yang tadi dibuka setelah berhasil masuk.
        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['smtp_password', 'sandbox_server_key', 'production_server_key']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'payments/midtrans/notification') || $request->expectsJson(),
        );
    })->create();
