<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Application-layer flood protection: caps how fast any single
        // client (by IP) can hit the app at all — 200 requests/minute is
        // far above normal human+Inertia usage but stops a single abusive
        // client or a basic scripted flood. This is NOT real DDoS
        // mitigation: a distributed volumetric attack has to be stopped
        // upstream, at the host/CDN/WAF level, before traffic ever reaches
        // PHP. The login route and public payment pages layer a tighter
        // limit of their own on top of this (see routes/auth.php, pay.php).
        $middleware->web(append: 'throttle:200,1');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // SSLCommerz POSTs to these callback URLs itself and cannot send a
        // Laravel CSRF token (Section 90) — the routes are otherwise
        // completely unauthenticated already, gated only by tran_id/val_id
        // lookups and server-to-server validation, not by CSRF.
        $middleware->validateCsrfTokens(except: [
            'pay/callback/success',
            'pay/callback/fail',
            'pay/callback/cancel',
            'pay/callback/ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
