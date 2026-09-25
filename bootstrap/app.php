<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         // =========================================================
        // MIDDLEWARE GLOBALES (se ejecutan en TODAS las rutas web)
        // =========================================================
        $middleware->web(append: [
            \App\Http\Middleware\RequiereAceptarAviso::class,  // <-- NUEVO
        ]);

        // =========================================================
        // ALIAS DE MIDDLEWARE (para usar como 'auth', 'verified', etc.)
        // =========================================================
        $middleware->alias([
            'requiere.consentimiento' => \App\Http\Middleware\RequiereAceptarAviso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
