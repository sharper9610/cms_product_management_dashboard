<?php

use App\Http\Middleware\DynamicApiAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\StaticApiPasswordAuth;
use App\Http\Middleware\VerifyWebhookSignature;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);
        $middleware->alias([
            'api.static-password' => StaticApiPasswordAuth::class,
            'storefront.auth' => \App\Http\Middleware\StorefrontApiAuth::class,
            'api.dynamic-password'=> DynamicApiAuth::class,
            '2fa' => \PragmaRX\Google2FALaravel\Middleware::class,
            'enforce2fa' => \App\Http\Middleware\Enforce2FA::class,
            'webhook.signature' => VerifyWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
