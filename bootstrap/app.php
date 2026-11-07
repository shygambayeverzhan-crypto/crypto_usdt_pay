<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\EnsureUserApproved;
use App\Http\Middleware\VerifyTelegramSecretToken;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Доверяем прокси (например, Cloudflare/Nginx), чтобы корректно определялась схема HTTPS
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Отключаем CSRF для dev callback sandbox
        $middleware->validateCsrfTokens(except: [
            'dev/callback-sandbox',
            'telegram/webhook',
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->throttleApi('api.v1');

        $middleware->alias([
            'api.key' => ApiKeyAuth::class,
            'approved' => EnsureUserApproved::class,
            'telegram.secret' => VerifyTelegramSecretToken::class,
        ]);
    })
    ->withEvents()
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
