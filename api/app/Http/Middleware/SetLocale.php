<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Language for messages and labels: the app's Accept-Language, else the user's saved choice. */
class SetLocale
{
    public const SUPPORTED = ['en', 'ta'];

    public function handle(Request $request, Closure $next): Response
    {
        $header = strtolower(substr((string) $request->header('Accept-Language'), 0, 2));
        $locale = in_array($header, self::SUPPORTED, true) ? $header : ($request->user()?->locale ?? 'en');
        app()->setLocale($locale);

        return $next($request);
    }
}
