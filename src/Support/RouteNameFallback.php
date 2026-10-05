<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Support;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Routing\UrlGenerator;

/**
 * Keeps the pre-0.1.x bare route names (`installer-web.*`) resolving after the
 * routes moved to `laranail-installer-web.*`.
 *
 * It hooks `URL::resolveMissingNamedRoutesUsing()`, which Laravel consults only
 * when a name is NOT found, so it cannot shadow an application's own route. The
 * generator holds a single resolver, so a resolver registered before this one is
 * captured and consulted for every name this class does not own — registering
 * here never silently discards another package's fallback. That resolver's
 * answer is trusted only when it is a string; anything else reads as "not
 * resolved", since passing it through is a `TypeError` under strict types.
 *
 * Whether the scoped route exists is asked of the public `Router`, not read out
 * of the generator's protected route collection.
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

    /**
     * @param Router|null $router the router whose routes the generator builds from;
     *                            null resolves the container's `router` (the
     *                            single-argument form callers used before)
     */
    public static function register(UrlGenerator $url, ?Router $router = null): void
    {
        $router ??= Container::getInstance()->make('router');
        $previous = self::previousResolver($url);

        $url->resolveMissingNamedRoutesUsing(
            static function (string $name, mixed $parameters, ?bool $absolute) use ($url, $router, $previous): ?string {
                if (str_starts_with($name, self::BARE_PREFIX)) {
                    $scoped = self::SCOPED_PREFIX . substr($name, strlen(self::BARE_PREFIX));

                    if ($router->has($scoped)) {
                        trigger_error(sprintf(
                            'Route name "%s" (laranail/installer-web) is deprecated and will be removed in the next minor after 0.1; use "%s".',
                            $name,
                            $scoped,
                        ), E_USER_DEPRECATED);

                        return $url->route($scoped, $parameters ?? [], $absolute ?? true);
                    }
                }

                if (! is_callable($previous)) {
                    return null;
                }

                $resolved = $previous($name, $parameters, $absolute);

                return is_string($resolved) ? $resolved : null;
            },
        );
    }

    /**
     * The generator exposes no getter for its current resolver, so chaining it
     * means reading the protected property; this is the one place that does.
     */
    private static function previousResolver(UrlGenerator $url): mixed
    {
        return Closure::bind(
            static fn (UrlGenerator $generator): mixed => $generator->missingNamedRouteResolver,
            null,
            UrlGenerator::class,
        )($url);
    }
}
