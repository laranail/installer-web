<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\Installer\Headless\Events\UnauthorizedInstallerAccess;
use Simtabi\Laranail\Installer\Headless\Security\InstallerAccessPolicy;

/**
 * The token/password gate: a neutral entry form for the secret installer token.
 * Failed attempts are rate-limited and, after `gate_max_attempts`, locked out for
 * `gate_lockout_minutes` — each rejection/lockout records an audit event so the
 * optional security alert fires.
 */
final class GateController extends Controller
{
    public function __construct(private readonly InstallerAccessPolicy $policy) {}

    public function show(): View|RedirectResponse
    {
        if ($this->policy->unrestricted() || ! $this->policy->tokenConfigured() || session('installer.authorized') === true) {
            return redirect()->route('installer-web.index');
        }

        return view((string) config('installer-web.gate_view', 'installer-web::gate'));
    }

    public function store(Request $request): RedirectResponse
    {
        $key = 'installer-gate:' . $request->ip();
        $maxAttempts = (int) config('installer.security.throttle.gate_max_attempts', 5);
        $lockoutSeconds = (int) config('installer.security.throttle.gate_lockout_minutes', 15) * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            UnauthorizedInstallerAccess::dispatch('token', $request->ip(), $request->path());

            throw ValidationException::withMessages([
                'token' => __('Too many attempts. Please try again later.'),
            ]);
        }

        $token = $request->input('token');

        if (! is_string($token) || ! $this->policy->tokenValid($token)) {
            RateLimiter::hit($key, $lockoutSeconds);
            UnauthorizedInstallerAccess::dispatch('token', $request->ip(), $request->path());

            throw ValidationException::withMessages(['token' => __('The token is invalid.')]);
        }

        RateLimiter::clear($key);
        $request->session()->put('installer.authorized', true);

        return redirect()->route('installer-web.index');
    }
}
