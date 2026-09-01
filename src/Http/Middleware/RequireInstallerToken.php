<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\Installer\Headless\Events\UnauthorizedInstallerAccess;
use Simtabi\Laranail\Installer\Headless\Security\InstallerAccessPolicy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Secret token/password gate for the wizard. Passes when no token is configured, the
 * layer is bypassed, the session is already authorized, a valid temporarySignedRoute
 * signature is present (when `signed_links` is on), or a valid token arrives via the
 * configured header or `?token=`. Otherwise it records an audit event and redirects to
 * the gate page (which throttles + locks out brute force).
 */
final readonly class RequireInstallerToken
{
    public function __construct(private InstallerAccessPolicy $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->policy->unrestricted() || ! $this->policy->tokenConfigured()) {
            return $next($request);
        }

        if ($request->session()->get('installer.authorized') === true) {
            return $next($request);
        }

        if ((bool) config('installer.security.signed_links', false) && $request->hasValidSignature()) {
            return $this->authorize($request, $next);
        }

        $header = (string) config('installer.security.token_header', 'X-Installer-Token');
        $token = $request->header($header) ?? $request->query('token');

        if (is_string($token) && $this->policy->tokenValid($token)) {
            return $this->authorize($request, $next);
        }

        UnauthorizedInstallerAccess::dispatch('token', $request->ip(), $request->path());

        return redirect()->route('installer-web.gate');
    }

    private function authorize(Request $request, Closure $next): Response
    {
        $request->session()->put('installer.authorized', true);

        return $next($request);
    }
}
