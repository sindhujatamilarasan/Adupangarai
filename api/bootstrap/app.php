<?php

use App\Http\Middleware\SetLocale;
use App\Support\AiUnavailable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [SetLocale::class]);
        // In production Caddy sits in front and terminates HTTPS.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions); // error alerts, only when SENTRY_LARAVEL_DSN is set
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
        $exceptions->dontReport(AiUnavailable::class);
        $exceptions->render(fn (AiUnavailable $e) => response()->json(['message' => $e->getMessage()], 503));
    })->create();
