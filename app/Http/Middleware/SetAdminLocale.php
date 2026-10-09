<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin locale. The admin area is not locale-prefixed (unlike the public
 * site); instead the chosen locale lives in the session and the language
 * switch flips it. Applies the locale to the app, Carbon and views so the
 * shared admin layout renders in the right direction.
 */
class SetAdminLocale
{
    public const SESSION_KEY = 'admin_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY);

        if (! Locale::isSupported($locale)) {
            $locale = Locale::DEFAULT;
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        view()->share('locale', $locale);
        view()->share('dir', Locale::dir($locale));

        return $next($request);
    }
}
