<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to the wizard once installed (install-once guard), redirecting to
 * the configured post-install target. Product-aware: on a `/p/{product}/…` route it
 * checks that product's install state, so additional products can still be installed
 * after the app itself is installed; default routes check app-level state.
 */
final readonly class RedirectIfInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $product = $request->route('product');
        $state = is_string($product) && $product !== ''
            ? $this->state->forProduct($product)
            : $this->state;

        if ($state->isInstalled()) {
            return redirect((string) config('installer.redirect_to', '/'));
        }

        return $next($request);
    }
}
