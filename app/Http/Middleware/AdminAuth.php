<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the admin area. Requires an authenticated, active admin on the
 * `admin` guard, then makes that guard the default for the rest of the
 * request so the Gate (and therefore every `can:` permission check and
 * spatie's Gate::before) resolves against the admin user.
 *
 * Unauthenticated visitors — including a logged-in customer — are redirected
 * to the admin login. A deactivated admin is logged out immediately.
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check()) {
            return redirect()->guest(route('admin.login'));
        }

        $user = $guard->user();

        if (! ($user->is_active ?? false)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => __('auth.inactive')]);
        }

        // Make `admin` the default guard so Gate / can: / spatie resolve it.
        Auth::shouldUse('admin');

        return $next($request);
    }
}
