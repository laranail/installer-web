<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Providers;

use Override;
use Livewire\Livewire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Installer\Web\InstallerUi;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Installer\Web\Support\WebUiRegistry;
use Simtabi\Laranail\Installer\Web\Livewire\LegacyWizardStep;
use Simtabi\Laranail\Installer\Web\Support\RouteNameFallback;
use Simtabi\Laranail\Installer\Web\Http\Middleware\EnsureInstalled;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Installer\Web\Http\Middleware\UseInstallerStores;
use Simtabi\Laranail\Installer\Web\Http\Middleware\RedirectIfInstalled;
use Simtabi\Laranail\Installer\Web\Http\Middleware\RequireInstallerToken;
use Simtabi\Laranail\Installer\Web\Http\Middleware\EnforceInstallerAccess;
use Simtabi\Laranail\Installer\Web\Http\Middleware\InstallerSecurityHeaders;

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
    public const string WIZARD_LIMITER = 'laranail-installer-web.wizard';

    public const string GATE_LIMITER = 'laranail-installer-web.gate';

    public const string WIZARD_STEP_COMPONENT = 'laranail-installer-web.wizard-step';

    /**
     * Deprecated bare names, still registered as aliases of the scoped ones above.
     * Each emits E_USER_DEPRECATED when used; removable in the next minor after 0.1.
     */
    private const string LEGACY_WIZARD_LIMITER = 'installer';

    private const string LEGACY_GATE_LIMITER = 'installer-gate';

    private const string LEGACY_WIZARD_STEP_COMPONENT = 'installer-wizard-step';

    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/installer-web')
            ->hasConfigFile('installer-web')
            ->withoutConfigNamespacing()
            ->hasViews('laranail-installer-web')
            ->hasRoute('web')
            ->registerMiddlewareAliases([
                'laranail-installer-web.guard'     => RedirectIfInstalled::class,
                'laranail-installer-web.installed' => EnsureInstalled::class,
                'laranail-installer-web.stores'    => UseInstallerStores::class,
                'laranail-installer-web.headers'   => InstallerSecurityHeaders::class,
                'laranail-installer-web.security'  => EnforceInstallerAccess::class,
                'laranail-installer-web.token'     => RequireInstallerToken::class,
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
        // Scoped name first: Livewire derives a class's canonical name from the first
        // registration, so WizardStep round-trips as the scoped name.
        Livewire::component(self::WIZARD_STEP_COMPONENT, WizardStep::class);
        Livewire::component(self::LEGACY_WIZARD_STEP_COMPONENT, LegacyWizardStep::class);

        // Enables the reusable <x-laranail-installer-web::field /> component in consumer views.
        Blade::anonymousComponentNamespace('laranail-installer-web::components', 'laranail-installer-web');

        $this->registerRateLimiters();

        RouteNameFallback::register($this->app->make('url'));
    }

    /**
     * Named limiters for the wizard and (more strictly) the token gate, configurable
     * via `installer.security.throttle`. Keyed by client IP (resolved through the
     * app's TrustProxies).
     *
     * The bare `installer` / `installer-gate` names are still registered, as
     * deprecated aliases that delegate to the scoped limiter and emit a deprecation
     * each time a route throttled by them is hit.
     */
    private function registerRateLimiters(): void
    {
        $wizard = static fn (Request $request): Limit => Limit::perMinutes(
            (int) config('installer.security.throttle.decay_minutes', 1),
            (int) config('installer.security.throttle.max_attempts', 60),
        )->by((string) $request->ip());

        $gate = static fn (Request $request): Limit => Limit::perMinutes(
            (int) config('installer.security.throttle.gate_lockout_minutes', 15),
            (int) config('installer.security.throttle.gate_max_attempts', 5),
        )->by((string) $request->ip());

        RateLimiter::for(self::WIZARD_LIMITER, $wizard);
        RateLimiter::for(self::GATE_LIMITER, $gate);

        $legacy = [
            self::LEGACY_WIZARD_LIMITER => [self::WIZARD_LIMITER, $wizard],
            self::LEGACY_GATE_LIMITER   => [self::GATE_LIMITER, $gate],
        ];

        foreach ($legacy as $bare => [$scoped, $limit]) {
            RateLimiter::for($bare, static function (Request $request) use ($bare, $scoped, $limit): Limit {
                trigger_error(sprintf(
                    'Rate limiter "%s" (laranail/installer-web) is deprecated and will be removed in the next minor after 0.1; use "%s".',
                    $bare,
                    $scoped,
                ), E_USER_DEPRECATED);

                return $limit($request);
            });
        }
    }
}
