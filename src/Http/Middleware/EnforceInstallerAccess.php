<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\Installer\Headless\Events\UnauthorizedInstallerAccess;
use Simtabi\Laranail\Installer\Headless\Security\InstallerAccessPolicy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the installer access policy (HTTPS, allowed hosts, IP allowlist, and the
 * availability window) on the wizard routes. On denial it records an audit event with
 * the precise reason and renders a GENERIC 403 page (never revealing which control
 * blocked the request). No-op when the layer is disabled or locally bypassed. The
 * token gate is a separate middleware (it has a form flow).
 */
final readonly class EnforceInstallerAccess
{
    public function __construct(private InstallerAccessPolicy $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $reason = $this->policy->denyReason($this->isSecure($request), $request->getHost(), $request->ip());

        if ($reason !== null) {
            UnauthorizedInstallerAccess::dispatch($reason, $request->ip(), $request->path());

            return response()->view(
                (string) config('installer-web.denied_view', 'installer-web::denied'),
                [],
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }

    /**
     * Resolve the request scheme. Honours the app's TrustProxies via isSecure(); for
     * hosts that can't configure TrustProxies, an opt-in trust of X-Forwarded-Proto
     * (with a documented spoofing caveat) covers TLS terminated at a host proxy.
     */
    private function isSecure(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        return (bool) config('installer.security.trust_forwarded_proto', false)
            && strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';
    }
}
