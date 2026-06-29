<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces filesystem-backed session and cache stores for installer requests.
 *
 * A stock app defaults to `SESSION_DRIVER`/`CACHE_STORE=database`, whose tables
 * don't exist until migrations run — so the wizard's session/CSRF and throttle would
 * 500 on the very first request of a fresh install. Overriding here (only for the
 * installer routes) keeps the installer self-contained. Must run BEFORE the `web`
 * group's StartSession, so it's prepended to the route middleware. Set
 * `installer.environment.session_store`/`cache_store` to null to leave the app's
 * drivers untouched.
 */
final class UseInstallerStores
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = config('installer.environment.session_store');
        $cache = config('installer.environment.cache_store');

        if (is_string($session) && $session !== '') {
            config(['session.driver' => $session]);
        }

        if (is_string($cache) && $cache !== '') {
            config(['cache.default' => $cache]);
        }

        return $next($request);
    }
}
