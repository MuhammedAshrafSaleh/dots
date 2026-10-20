<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security response headers to every web response (T9.4).
 *
 * A strict script CSP is intentionally omitted: Livewire and Alpine both rely
 * on inline handlers / Function() evaluation, so a locked-down script-src would
 * break the storefront. We ship the headers that add value without that cost
 * (clickjacking, MIME sniffing, referrer leakage, feature gating) plus HSTS on
 * HTTPS only. See Decisions log in PROGRESS.md.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), browsing-topics=()',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // HSTS only over HTTPS (sending it on plain HTTP is ignored and risky
        // behind proxies). 1 year, include subdomains.
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
