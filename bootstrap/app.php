<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\EnsureCustomerActive;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'setlocale' => SetLocale::class,
            'setadminlocale' => SetAdminLocale::class,
            'admin.auth' => AdminAuth::class,
            'customer.active' => EnsureCustomerActive::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);

        // The storefront `login` route is locale-prefixed, so the default
        // parameterless route('login') guard redirect would fail. Build it with
        // the current (or default) locale (admin uses its own AdminAuth redirect).
        $middleware->redirectGuestsTo(function (Request $request): string {
            $locale = $request->route('locale') ?? Locale::DEFAULT;

            return route('login', ['locale' => $locale]);
        });

        // AdminAuth makes `admin` the default guard; it must run before the
        // `can:` (Authorize) and permission middleware, which resolve the
        // current user from that guard.
        $middleware->prependToPriorityList(
            before: Authorize::class,
            prepend: AdminAuth::class,
        );

        // Paymob's server→server webhook cannot carry a CSRF token; it is
        // authenticated by HMAC in PaymobWebhookController instead.
        $middleware->validateCsrfTokens(except: [
            'payments/paymob/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
