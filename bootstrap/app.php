<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureStoreIsActive;
use App\Http\Middleware\SetLocale;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Carrier webhooks live here so they skip CSRF, sessions and the
        // storefront maintenance gate.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Shared hosting terminates TLS in front of PHP, so the request that
         * reaches Laravel looks like plain http. Every generated URL then came
         * out as http:// on an https:// page: browsers quietly upgrade an
         * <img>, but they block the XHR the upload field uses to read a file,
         * which left the image picker spinning forever.
         *
         * Trusting the forwarded headers also restores the visitor's real IP,
         * which login throttling and the activity log had been recording as
         * the proxy's address for everyone.
         */
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
            EnsureStoreIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();