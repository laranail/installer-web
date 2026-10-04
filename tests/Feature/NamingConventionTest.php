<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Installer\Web\Support\RouteNameFallback;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Every name this package registers into a framework-owned registry.
 *
 * Route names, rate limiters, Artisan commands, middleware aliases and Livewire
 * component names all live in flat maps keyed by the name, so a second package
 * claiming the same key does not collide loudly: it silently replaces the first.
 * These assertions read the LIVE registries of the booted application, not the
 * provider source, so they hold whatever the registration code looks like.
 *
 * The bare names this package shipped before 0.1.x was scoped are still
 * registered as deprecated aliases. Those are listed explicitly below; any OTHER
 * bare name fails.
 */
const INSTALLER_WEB_SCOPE = 'laranail-installer-web';

/** Deprecated aliases kept for backwards compatibility — a ceiling, not a licence. */
const INSTALLER_WEB_DEPRECATED_LIMITERS = ['installer', 'installer-gate'];
const INSTALLER_WEB_DEPRECATED_COMPONENTS = ['installer-wizard-step'];

/** @return list<string> */
function installerWebLimiterNames(): array
{
    $limiter = app(RateLimiter::class);

    return array_keys((fn (): array => $this->limiters)->call($limiter));
}

/** @return array<string, class-string> */
function installerWebLivewireComponents(): array
{
    $finder = app('livewire.finder');

    return (fn (): array => $this->classComponents)->call($finder);
}

/** Route names whose action is one of this package's controllers. */
function installerWebRouteNames(): array
{
    $names = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (str_starts_with((string) $route->getActionName(), 'Simtabi\\Laranail\\Installer\\Web\\')) {
            $names[] = (string) $route->getName();
        }
    }

    return $names;
}

it('names every one of its routes laranail-installer-web.*', function (): void {
    $names = installerWebRouteNames();

    // Non-vacuity: the gate, setup and wizard groups register 10 routes. A filter that matches nothing
    // would otherwise pass every assertion below.
    expect($names)->toHaveCount(10);

    foreach ($names as $name) {
        expect($name)->toStartWith(INSTALLER_WEB_SCOPE . '.');
    }

    expect(Route::has('installer-web.index'))->toBeFalse()
        ->and(Route::has('laranail-installer-web.index'))->toBeTrue();
});

it('still resolves the deprecated bare route names, with a deprecation', function (): void {
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

    RouteNameFallback::register($url);

    expect(route('someone-else.page'))->toBe('http://localhost/elsewhere')
        ->and(route('installer-web.index'))->toBe(route('laranail-installer-web.index'));
});

it('registers its rate limiters under laranail-installer-web.*', function (): void {
    $names = installerWebLimiterNames();

    expect($names)->toContain(INSTALLER_WEB_SCOPE . '.wizard')
        ->and($names)->toContain(INSTALLER_WEB_SCOPE . '.gate');

    $bare = array_values(array_filter(
        $names,
        static fn (string $name): bool => ! str_starts_with($name, INSTALLER_WEB_SCOPE . '.'),
    ));

    expect($bare)->toEqualCanonicalizing(INSTALLER_WEB_DEPRECATED_LIMITERS);
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
    $ours = array_filter(
        app('router')->getMiddleware(),
        static fn (string $class): bool => str_starts_with($class, 'Simtabi\\Laranail\\Installer\\Web\\'),
    );

    expect($ours)->toHaveCount(6);

    foreach (array_keys($ours) as $alias) {
        // Hyphens and dots only: `::` would be split by the middleware parser.
        expect($alias)->toStartWith(INSTALLER_WEB_SCOPE . '.')
            ->and($alias)->not->toContain('::');
    }
});

it('registers no bare Artisan command', function (): void {
    $ours = array_filter(
        app(Kernel::class)->all(),
        static fn (object $command): bool => str_starts_with($command::class, 'Simtabi\\Laranail\\Installer\\Web\\'),
    );

    // The package ships no command today; the laranail::installer.* commands in
    // the kernel belong to laranail/installer-headless. Pin both facts.
    expect($ours)->toBe([]);

    $bare = array_filter(
        array_keys(app(Kernel::class)->all()),
        static fn (string $name): bool => str_starts_with($name, 'installer-web') || str_starts_with($name, 'installer:'),
    );

    expect($bare)->toBe([]);
});

it('registers its Livewire component under laranail-installer-web.*', function (): void {
    $components = installerWebLivewireComponents();

    expect($components)->toHaveKey(INSTALLER_WEB_SCOPE . '.wizard-step')
        ->and($components[INSTALLER_WEB_SCOPE . '.wizard-step'])->toBe(WizardStep::class)
        // The canonical name Livewire derives from the class is the scoped one.
        ->and(app('livewire.finder')->normalizeName(WizardStep::class))->toBe(INSTALLER_WEB_SCOPE . '.wizard-step');

    $ours = array_filter(
        $components,
        static fn (string $class): bool => str_starts_with($class, 'Simtabi\\Laranail\\Installer\\Web\\'),
    );

    $bare = array_values(array_filter(
        array_keys($ours),
        static fn (string $name): bool => ! str_starts_with($name, INSTALLER_WEB_SCOPE . '.'),
    ));

    expect($bare)->toEqualCanonicalizing(INSTALLER_WEB_DEPRECATED_COMPONENTS);
});

it('registers its views and Blade components under laranail-installer-web', function (): void {
    expect(array_keys(app('view')->getFinder()->getHints()))->toContain(INSTALLER_WEB_SCOPE)
        ->and(array_keys(app('view')->getFinder()->getHints()))->not->toContain('installer-web');
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
