<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Providers;

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Override;
use Simtabi\Laranail\Installer\Web\Http\Middleware\EnsureInstalled;
use Simtabi\Laranail\Installer\Web\Http\Middleware\RedirectIfInstalled;
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
    }
}
