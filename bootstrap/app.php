<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'webhook/mollie',     // конкретный маршрут
            'api/*',              // все маршруты, начинающиеся с api/
            'payment/callback',   // другой конкретный маршрут
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
