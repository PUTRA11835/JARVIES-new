<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'jarvies.auth' => \App\Http\Middleware\JarviesAuth::class,
            'jarvies.guest' => \App\Http\Middleware\JarviesGuest::class,
            'api.auth'     => \App\Http\Middleware\ApiAuth::class,
        ]);

        // Encrypt cookies for security
        $middleware->encryptCookies(except: [
            // Cookies to exclude from encryption (if any)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling can be added here
    })
    ->create();