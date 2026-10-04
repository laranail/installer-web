<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Support;

use Closure;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollectionInterface;

/**
 * Keeps the pre-0.1.x bare route names (`installer-web.*`) resolving after the
 * routes moved to `laranail-installer-web.*`.
 *
 * It hooks `URL::resolveMissingNamedRoutesUsing()`, which Laravel consults only
 * when a name is NOT found, so it cannot shadow an application's own route. The
 * generator holds a single resolver, so a resolver registered before this one is
 * captured and consulted for every name this class does not own — registering
 * here never silently discards another package's fallback.
 *
 * `Route::has('installer-web.*')` does not go through this hook and answers
 * false; ask for the scoped name.
 *
 * The bare names it serves are deprecated and removable in the next minor after
 * 0.1; this class goes with them.
 *
 * @internal
 */
final class RouteNameFallback
{
    public const string SCOPED_PREFIX = 'laranail-installer-web.';

    public const string BARE_PREFIX = 'installer-web.';

    public static function register(UrlGenerator $url): void
    {
        $previous = self::read($url, 'missingNamedRouteResolver');

        $url->resolveMissingNamedRoutesUsing(
            static function (string $name, mixed $parameters, ?bool $absolute) use ($url, $previous): ?string {
                if (str_starts_with($name, self::BARE_PREFIX)) {
                    $scoped = self::SCOPED_PREFIX . substr($name, strlen(self::BARE_PREFIX));

                    $routes = self::read($url, 'routes');

                    if ($routes instanceof RouteCollectionInterface && $routes->hasNamedRoute($scoped)) {
                        trigger_error(sprintf(
                            'Route name "%s" (laranail/installer-web) is deprecated and will be removed in the next minor after 0.1; use "%s".',
                            $name,
                            $scoped,
                        ), E_USER_DEPRECATED);

                        return $url->route($scoped, $parameters ?? [], $absolute ?? true);
                    }
                }

                return is_callable($previous) ? $previous($name, $parameters, $absolute) : null;
            },
        );
    }

    /** Reads a protected UrlGenerator property; the generator exposes neither through a getter. */
    private static function read(UrlGenerator $url, string $property): mixed
    {
        return Closure::bind(
            static fn (UrlGenerator $generator): mixed => $generator->{$property},
            null,
            UrlGenerator::class,
        )($url);
    }
}
