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
        $middleware->encryptCookies(except: [
            'access_token',
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/ai/*',
            'bkk/api/ai/*',
        ]);

        $middleware->alias([
            'verify.auth' => \App\Http\Middleware\VerifyAuthToken::class,
            'mitra.auth'  => \App\Http\Middleware\EnsureMitraAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
