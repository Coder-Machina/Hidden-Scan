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
        $middleware->trustProxies(at: '*');
        
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Cookie de fuseau horaire posé en JS par le navigateur (non chiffré)
        $middleware->encryptCookies(except: [
            'hs_tz',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckScheduledChaptersMiddleware::class,
            \App\Http\Middleware\EnsureNotBannedMiddleware::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'staff' => \App\Http\Middleware\EnsureIsStaff::class,
            'throttle.comments' => \App\Http\Middleware\RateLimitComments::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
