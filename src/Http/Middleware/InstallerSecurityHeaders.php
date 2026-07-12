<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds hardening headers to every installer response: no caching of credential
 * forms, anti-clickjacking, no MIME sniffing, no referrer leakage, and no search
 * indexing of the installer. Gated by `installer.security.headers` (on by default);
 * applied even under the local bypass since the headers are harmless.
 */
final class InstallerSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! (bool) config('installer.security.headers', true)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
