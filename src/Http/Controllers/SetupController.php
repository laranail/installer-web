<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Simtabi\Laranail\Installer\Headless\Security\InstallerAccessPolicy;
use Simtabi\Laranail\Installer\Headless\Support\EnvWriter;

/**
 * No-SSH security setup: lets the operator set the installer gate password (stored
 * as INSTALLER_TOKEN_HASH) and/or lock the installer to their current IP, written to
 * .env via the atomic EnvWriter — so the access layer can be configured from the
 * browser on hosts without shell access. Only available before a token is configured.
 */
final class SetupController extends Controller
{
    public function __construct(
        private readonly EnvWriter $env,
        private readonly InstallerAccessPolicy $policy,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($this->policy->tokenConfigured()) {
            return redirect()->route('installer-web.index');
        }

        return view((string) config('installer-web.setup_view', 'installer-web::setup'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->policy->tokenConfigured()) {
            return redirect()->route('installer-web.index');
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
            'lock_ip' => ['nullable', 'boolean'],
        ]);

        $values = [];

        if (! empty($validated['password'])) {
            $values['INSTALLER_TOKEN_HASH'] = Hash::make((string) $validated['password']);
        }

        if ($request->boolean('lock_ip')) {
            $values['INSTALLER_ALLOWED_IPS'] = (string) $request->ip();
        }

        if ($values === []) {
            return redirect()->route('installer-web.index');
        }

        $path = (string) (config('installer.env.path') ?: base_path('.env'));
        $this->env->update($path, $values);

        // Apply to the live config so the controls engage on the next request, and
        // authorize this session so the operator who just set the token isn't locked out.
        if (isset($values['INSTALLER_TOKEN_HASH'])) {
            config(['installer.security.token_hash' => $values['INSTALLER_TOKEN_HASH']]);
        }

        if (isset($values['INSTALLER_ALLOWED_IPS'])) {
            config(['installer.security.allowed_ips' => [$values['INSTALLER_ALLOWED_IPS']]]);
        }

        $request->session()->put('installer.authorized', true);

        return redirect()->route('installer-web.index');
    }
}
