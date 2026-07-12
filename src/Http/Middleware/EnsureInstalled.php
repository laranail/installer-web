<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in guard for application routes: redirects to the install wizard until the
 * app is installed. Register on your own routes (alias `installer.installed`);
 * the wizard routes themselves are guarded by {@see RedirectIfInstalled}.
 */
final readonly class EnsureInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->state->isInstalled()) {
            return redirect()->route('installer-web.index');
        }

        return $next($request);
    }
}
