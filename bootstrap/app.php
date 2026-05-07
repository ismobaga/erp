<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureApiScope;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // ── Security Middleware ────────────────────────────────────────────
        $middleware->appendToGroup('web', VerifyCsrfToken::class);
        $middleware->appendToGroup('web', SetSecurityHeaders::class);
        $middleware->alias([
            'auth.api' => AuthenticateApiToken::class,
            'api.scope' => EnsureApiScope::class,
            'api.audit' => LogApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
