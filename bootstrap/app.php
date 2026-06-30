<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Send unauthenticated GTR participants to the runner login page.
        $middleware->redirectGuestsTo(fn (Request $request) => route('gtr.login'));

        // Alias middleware untuk modul race-timing (API admin only).
        $middleware->alias([
            'api.admin' => \App\Http\Middleware\EnsureApiAdmin::class,
        ]);

        // Rate limit semua request web.
        $middleware->appendToGroup('web', 'throttle:web');

        // Midtrans webhook must skip CSRF verification.
        $middleware->validateCsrfTokens(except: [
            'api/midtrans/notification',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
