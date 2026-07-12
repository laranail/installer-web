# Installation

## Requirements

| Requirement | Constraint |
|---|---|
| PHP | `^8.4.1` (8.4, 8.5) |
| Laravel | `^13.0` |
| Livewire | `^4.2` |
| Depends on | `laranail/installer-headless`, `laranail/package-tools` |

## Install

```bash
composer require laranail/installer-web
```

The `InstallerWebServiceProvider` (and the headless engine's provider) are
auto-discovered.

## Publishing

```bash
php artisan vendor:publish --tag=laranail/installer-web::config   # route prefix, middleware
php artisan vendor:publish --tag=laranail/installer-web::views    # customize the wizard views
```

## Assets

The shipped views use Tailwind via CDN for a zero-build experience. For
production, build Tailwind into your own bundle and override the layout view.

## Dependency resolution (local dev vs CI)

For source development this package uses named `path` repositories pointing at the
sibling laranail checkouts (`../headless`, `../../package/tools`, …). CI has no
siblings, so the workflows override each by name with its public VCS source before
`composer update` (see `.github/workflows/*.yml`):

```bash
composer config repositories.installer-headless vcs https://github.com/laranail/installer-headless
composer config repositories.package-tools vcs https://github.com/laranail/package-tools
# … console, license-verifier
```

Once the packages are on Packagist, both the `path` repos and the CI override can
be removed.

[← Docs index](../README.md#documentation)
