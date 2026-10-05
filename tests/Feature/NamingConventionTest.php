<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Installer\Web\Support\RouteNameFallback;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;
use Simtabi\Laranail\Installer\Web\Providers\InstallerWebServiceProvider;

/**
 * Every name this package registers into a framework-owned registry.
 *
 * Route names, rate limiters, Artisan commands, middleware aliases and Livewire
 * component names all live in flat maps keyed by the name, so a second package
 * claiming the same key does not collide loudly: it silently replaces the first.
 * These assertions read the LIVE registries of the booted application, not the
 * provider source, through package-tools' shared AssertsRegisteredNames, so they
 * hold whatever the registration code looks like.
 *
 * The bare names this package shipped before 0.1.x was scoped are still
 * registered as deprecated aliases. Those are listed explicitly below; any OTHER
 * bare name fails.
 */
const INSTALLER_WEB_SCOPE = 'laranail-installer-web';

/** Deprecated aliases kept for backwards compatibility — a ceiling, not a licence. */
const INSTALLER_WEB_DEPRECATED_LIMITERS = ['installer', 'installer-gate'];
const INSTALLER_WEB_DEPRECATED_COMPONENTS = ['installer-wizard-step'];

uses(AssertsRegisteredNames::class);

/**
 * The scope the shared assertions judge names against.
 *
 * The base path is narrowed to one directory because package-tools 0.1.3
 * defaults it to the package root, which holds this checkout's own vendor/ and
 * tests/ and would claim every framework closure binding as the package's. Code
 * lives in src/, views in resources/.
 */
function installerWebScope(string $directory = 'src'): NamingScope
{
    return NamingScope::for(
        package: 'laranail/installer-web',
        ownerNamespace: 'Simtabi\\Laranail\\Installer\\Web\\',
        basePath: dirname(__DIR__, 2) . '/' . $directory,
    );
}

/**
 * Re-run the bare-route-name installation package-tools performs at boot.
 */
function installerWebInstallRouteFallback(): void
{
    app()->getProvider(InstallerWebServiceProvider::class)->package->bootPackageDeprecatedRouteNames(
        app('router'),
        app(UrlGenerator::class),
        static fn (): LoggerInterface => app(LoggerInterface::class),
    );
}

beforeEach(function (): void {
    BareRouteNameAliases::forgetWarnings();
});

it('names every one of its routes laranail-installer-web.*', function (): void {
    // Non-vacuity: the gate, setup and wizard groups register 10 routes. A filter that matches nothing
    // would otherwise pass every assertion below.
    $names = $this->assertRouteNamesScoped(installerWebScope(), atLeast: 10);

    expect($names)->toHaveCount(10)->each->toStartWith(INSTALLER_WEB_SCOPE . '.');

    expect(Route::has('installer-web.index'))->toBeFalse()
        ->and(Route::has('laranail-installer-web.index'))->toBeTrue();
});

it('still resolves the deprecated bare route names, with a deprecation', function (): void {
    $this->assertDeprecatedRouteNamesResolve(
        [
            'installer-web.index'        => 'laranail-installer-web.index',
            'installer-web.show'         => 'laranail-installer-web.show',
            'installer-web.gate'         => 'laranail-installer-web.gate',
            'installer-web.setup'        => 'laranail-installer-web.setup',
            'installer-web.product.show' => 'laranail-installer-web.product.show',
        ],
        parameters: [
            'installer-web.show'         => ['step' => 'welcome'],
            'installer-web.product.show' => ['product' => 'addon', 'step' => 'welcome'],
        ],
    );

    BareRouteNameAliases::forgetWarnings();

    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $bare = route('installer-web.show', ['step' => 'welcome']);
        $productBare = route('installer-web.product.show', ['product' => 'addon', 'step' => 'welcome']);
    } finally {
        restore_error_handler();
    }

    expect($bare)->toBe(route('laranail-installer-web.show', ['step' => 'welcome']))
        ->and($productBare)->toBe(route('laranail-installer-web.product.show', ['product' => 'addon', 'step' => 'welcome']))
        ->and($deprecations)->toHaveCount(2)
        ->and($deprecations[0])->toContain('laranail-installer-web.show');
});

it('leaves an unrelated missing route name missing', function (): void {
    route('no-such-route');
})->throws(RouteNotFoundException::class);

it('delegates to a missing-route resolver registered before it', function (): void {
    // The URL generator holds ONE resolver. Registering ours must not silently
    // discard another package's.
    $url = app('url');
    $url->resolveMissingNamedRoutesUsing(fn (string $name): ?string => $name === 'someone-else.page' ? 'http://localhost/elsewhere' : null);

    installerWebInstallRouteFallback();
    set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);

    try {
        expect(route('someone-else.page'))->toBe('http://localhost/elsewhere')
            ->and(route('installer-web.index'))->toBe(route('laranail-installer-web.index'));
    } finally {
        restore_error_handler();
    }
});

it('treats a non-string answer from the previous resolver as no answer', function (mixed $answer): void {
    $url = app('url');
    $url->resolveMissingNamedRoutesUsing(fn (string $name): mixed => $name === 'someone-else.odd' ? $answer : null);

    installerWebInstallRouteFallback();

    // Unchecked, a foreign object/int/array is a TypeError against the
    // fallback's ?string return under strict_types; it must read as "missing".
    route('someone-else.odd');
})->with([
    'int'           => [42],
    'url generator' => [fn (): UrlGenerator => app('url')],
    'array'         => [['http://localhost/elsewhere']],
])->throws(RouteNotFoundException::class);

it('answers the scoped-route lookup from the public router, not the generator internals', function (): void {
    $url = app('url');
    $router = app('router');

    // Point the generator at an EMPTY collection while the router still holds
    // the real one: a fallback reading UrlGenerator::$routes would find no
    // scoped route; one asking the public Router finds it.
    $real = $router->getRoutes();
    $url->setRoutes(new RouteCollection);

    installerWebInstallRouteFallback();

    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        // The generator then cannot build the URL itself, which surfaces as a
        // RouteNotFoundException naming the SCOPED route: proof the lookup
        // matched through the router and delegated to the scoped name.
        expect(fn (): string => route('installer-web.index'))
            ->toThrow(RouteNotFoundException::class, 'laranail-installer-web.index');
    } finally {
        restore_error_handler();
        $url->setRoutes($real);
    }

    expect($deprecations)->toHaveCount(1);
});

it('registers its rate limiters under laranail-installer-web.*', function (): void {
    expect($this->assertRateLimitersScoped(installerWebScope(), deprecated: INSTALLER_WEB_DEPRECATED_LIMITERS, atLeast: 2))
        ->toEqualCanonicalizing([INSTALLER_WEB_SCOPE . '.wizard', INSTALLER_WEB_SCOPE . '.gate']);
});

it('routes the wizard and gate through the scoped limiters', function (): void {
    $middleware = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), INSTALLER_WEB_SCOPE . '.'))
        ->flatMap(fn ($route): array => $route->gatherMiddleware())
        ->filter(fn (mixed $m): bool => is_string($m) && str_starts_with($m, 'throttle:'))
        ->unique()->values()->all();

    expect($middleware)->toEqualCanonicalizing([
        'throttle:' . INSTALLER_WEB_SCOPE . '.wizard',
        'throttle:' . INSTALLER_WEB_SCOPE . '.gate',
    ]);
});

it('keeps the deprecated bare limiters working, with a deprecation', function (): void {
    $limiter = app(RateLimiter::class);
    $request = Request::create('/install', server: ['REMOTE_ADDR' => '203.0.113.9']);

    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $bare = ($limiter->limiter('installer-gate'))($request);
    } finally {
        restore_error_handler();
    }

    $scoped = ($limiter->limiter(INSTALLER_WEB_SCOPE . '.gate'))($request);

    expect($bare->maxAttempts)->toBe($scoped->maxAttempts)
        ->and($bare->decaySeconds)->toBe($scoped->decaySeconds)
        ->and($deprecations)->toHaveCount(1)
        ->and($deprecations[0])->toContain(INSTALLER_WEB_SCOPE . '.gate');
});

it('registers its middleware aliases under laranail-installer-web', function (): void {
    // D1: `laranail-installer-web.<x>` is a sanctioned vendor-scoped variant.
    $ours = $this->assertMiddlewareAliasesScoped(installerWebScope(), atLeast: 6);

    expect($ours)->toHaveCount(6);

    foreach ($ours as $alias) {
        // Hyphens and dots only: `::` would be split by the middleware parser.
        expect($alias)->toStartWith(INSTALLER_WEB_SCOPE . '.')
            ->and($alias)->not->toContain('::');
    }
});

it('registers no bare Artisan command', function (): void {
    // The package ships no command today; the laranail::installer.* commands in
    // the kernel belong to laranail/installer-headless. Pin both facts.
    expect($this->assertCommandNamesScoped(installerWebScope(), atLeast: 0))->toBe([]);

    $bare = array_filter(
        array_keys(app(Kernel::class)->all()),
        static fn (string $name): bool => str_starts_with($name, 'installer-web') || str_starts_with($name, 'installer:'),
    );

    expect($bare)->toBe([]);
});

it('registers its Livewire component under laranail-installer-web.*', function (): void {
    expect($this->assertLivewireComponentsScoped(installerWebScope(), deprecated: INSTALLER_WEB_DEPRECATED_COMPONENTS, atLeast: 1))
        ->toBe([INSTALLER_WEB_SCOPE . '.wizard-step'])
        // The canonical name Livewire derives from the class is the scoped one.
        ->and(app('livewire.finder')->normalizeName(WizardStep::class))->toBe(INSTALLER_WEB_SCOPE . '.wizard-step');
});

it('registers its views and Blade components under laranail-installer-web', function (): void {
    expect($this->assertViewNamespacesScoped(installerWebScope('resources'), atLeast: 2))
        ->toContain('laranail/installer-web', INSTALLER_WEB_SCOPE)
        ->and(array_keys(app('view')->getFinder()->getHints()))->not->toContain('installer-web')
        ->and($this->assertBladeComponentsScoped(installerWebScope('resources'), atLeast: 1))->not->toBeEmpty();

    expect(view()->exists('laranail/installer-web::gate'))->toBeTrue()
        ->and(view()->exists(INSTALLER_WEB_SCOPE . '::gate'))->toBeTrue();
});

it('finds a view a host published under the hyphen namespace through the slash namespace', function (): void {
    expect(trim(view('laranail/installer-web::host-override')->render()))->toBe('host-override');
});

it('keeps the deprecated RouteNameFallback::register() working, with a deprecation', function (): void {
    app('url')->resolveMissingNamedRoutesUsing(static fn (): ?string => null);

    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        RouteNameFallback::register(app('url'));

        expect(route('installer-web.index'))->toBe(route('laranail-installer-web.index'));
    } finally {
        restore_error_handler();
    }

    expect(implode("\n", $deprecations))
        ->toContain('RouteNameFallback::register() (laranail/installer-web) is deprecated')
        ->toContain('[installer-web.index]');
});

it('still mounts the deprecated bare Livewire name, with a deprecation', function (): void {
    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        Livewire::test('installer-wizard-step', ['step' => 'welcome'])
            ->assertSet('step', 'welcome');
    } finally {
        restore_error_handler();
    }

    expect($deprecations)->toHaveCount(1)
        ->and($deprecations[0])->toContain(INSTALLER_WEB_SCOPE . '.wizard-step');
});
