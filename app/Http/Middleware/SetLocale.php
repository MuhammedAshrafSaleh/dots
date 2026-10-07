<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the {locale} route segment, applies it to the app, Carbon and the
 * URL generator, and shares `locale`/`dir` with every view. Unsupported
 * locales 404 (the route pattern normally blocks them first).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! Locale::isSupported($locale)) {
            abort(404);
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        // Keep the locale prefix on every url()/route() the request generates.
        url()->defaults(['locale' => $locale]);

        view()->share('locale', $locale);
        view()->share('dir', Locale::dir($locale));

        return $next($request);
    }
}
