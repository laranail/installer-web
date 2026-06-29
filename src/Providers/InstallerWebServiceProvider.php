<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Override;
use Simtabi\Laranail\Installer\Web\Http\Middleware\EnforceInstallerAccess;
use Simtabi\Laranail\Installer\Web\Http\Middleware\EnsureInstalled;
use Simtabi\Laranail\Installer\Web\Http\Middleware\InstallerSecurityHeaders;
use Simtabi\Laranail\Installer\Web\Http\Middleware\RedirectIfInstalled;
use Simtabi\Laranail\Installer\Web\Http\Middleware\RequireInstallerToken;
use Simtabi\Laranail\Installer\Web\Http\Middleware\UseInstallerStores;
use Simtabi\Laranail\Installer\Web\InstallerUi;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Installer\Web\Support\WebUiRegistry;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Service provider for the install wizard web UI.
 *
 * Registers the wizard routes, views, the install-once guard middleware and the
 * generic Livewire wizard-step component. It holds NO install logic and declares
 * NO validation rules — every action and the rules come from the headless engine
 * (laranail/installer-headless).
 */
final class InstallerWebServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/installer-web')
            ->hasConfigFile('installer-web')
            ->withoutConfigNamespacing()
            ->hasViews('installer-web')
            ->hasRoute('web')
            ->registerMiddlewareAliases([
                'installer.guard' => RedirectIfInstalled::class,
                'installer.installed' => EnsureInstalled::class,
                'installer.stores' => UseInstallerStores::class,
                'installer.headers' => InstallerSecurityHeaders::class,
                'installer.security' => EnforceInstallerAccess::class,
                'installer.token' => RequireInstallerToken::class,
            ]);
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(WebUiRegistry::class);
        $this->app->singleton(InstallerUi::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        Livewire::component('installer-wizard-step', WizardStep::class);

        // Enables the reusable <x-installer-web::field /> component in consumer views.
        Blade::anonymousComponentNamespace('installer-web::components', 'installer-web');

        $this->registerRateLimiters();
    }

    /**
     * Named limiters for the wizard and (more strictly) the token gate, configurable
     * via `installer.security.throttle`. Keyed by client IP (resolved through the
     * app's TrustProxies).
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('installer', fn (Request $request): Limit => Limit::perMinutes(
            (int) config('installer.security.throttle.decay_minutes', 1),
            (int) config('installer.security.throttle.max_attempts', 60),
        )->by((string) $request->ip()));

        RateLimiter::for('installer-gate', fn (Request $request): Limit => Limit::perMinutes(
            (int) config('installer.security.throttle.gate_lockout_minutes', 15),
            (int) config('installer.security.throttle.gate_max_attempts', 5),
        )->by((string) $request->ip()));
    }
}
