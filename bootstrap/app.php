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

        // ✅ ENABLE API CORS (THIS FIXES FLUTTER WEB)
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // ✅ DO NOT redirect API users to login page
        $middleware->redirectGuestsTo(fn () => null);
        
        $middleware->alias([
            'is.admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
