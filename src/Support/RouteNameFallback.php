<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Support;

use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Routing\UrlGenerator;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;

/**
 * The prefixes that keep the pre-0.1.x bare route names (`installer-web.*`)
 * resolving after the routes moved to `laranail-installer-web.*`.
 *
 * The provider declares them with `hasDeprecatedRouteNames()`, and
 * package-tools' {@see BareRouteNameAliases} installs the fallback at boot: it
 * hooks `URL::resolveMissingNamedRoutesUsing()`, which Laravel consults only
 * when a name is NOT found, so it cannot shadow an application's own route; it
 * chains a resolver registered before it, trusts that resolver's answer only
 * when it is a string, and asks the public `Router` whether the scoped route
 * exists.
 *
 * `Route::has('installer-web.*')` does not go through this hook and answers
 * false; ask for the scoped name.
 *
 * The bare names it serves are deprecated and removable in the next minor after
 * 0.1.
 *
 * @internal
 */
final class RouteNameFallback
{
    public const string SCOPED_PREFIX = 'laranail-installer-web.';

    public const string BARE_PREFIX = 'installer-web.';

    /**
     * Install the bare-name fallback by hand.
     *
     * @deprecated since 0.1, removable no earlier than the next minor after 0.1.
     *             The provider declares `hasDeprecatedRouteNames(prefixes:
     *             [BARE_PREFIX => SCOPED_PREFIX])` and package-tools installs the
     *             fallback at boot; call {@see BareRouteNameAliases::install()}
     *             to install one by hand.
     *
     * @param Router|null $router the router whose routes the generator builds from;
     *                            null resolves the container's `router` (the
     *                            single-argument form callers used before)
     */
    public static function register(UrlGenerator $url, ?Router $router = null): void
    {
        trigger_error(sprintf(
            '%s::register() (laranail/installer-web) is deprecated and will be removed no earlier than the next minor after 0.1; '
            . 'the provider installs the fallback through hasDeprecatedRouteNames(), or call %s::install().',
            self::class,
            BareRouteNameAliases::class,
        ), E_USER_DEPRECATED);

        BareRouteNameAliases::install(
            router: $router ?? Container::getInstance()->make('router'),
            url: $url,
            package: 'laranail/installer-web',
            prefixes: [self::BARE_PREFIX => self::SCOPED_PREFIX],
        );
    }
}
