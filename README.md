# laranail/installer-web

[![Tests](https://github.com/laranail/installer-web/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/installer-web/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/installer-web/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/installer-web/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/installer-web` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

> A Tailwind + Blade + **Livewire 4** install wizard for [`laranail/installer-headless`](https://opensource.simtabi.com/documentation/laranail/installer-headless/) — pure presentation and input collection; every operation is delegated to the headless engine (no install logic, never touches files or the database directly).

Requires PHP `^8.4.1`, Laravel `^13`, Livewire `^4.2`, and `laranail/installer-headless` (+ `laranail/package-tools`).

## Install

```bash
composer require laranail/installer-web
```

Publish the assets/config, then visit the install route — see the docs for the exact steps.

## Quick start guide and usage

### Getting started

Nothing to configure: the `InstallerWebServiceProvider` and the headless engine's provider are
auto-discovered, and the wizard is served at `/install`. To serve it elsewhere, set the prefix in
`.env`:

```dotenv
INSTALLER_WEB_PREFIX=install
```

### Usage

```php
// app/Providers/AppServiceProvider.php
use Simtabi\Laranail\Installer\Web\Facades\InstallerUi;

public function boot(): void
{
    // The wizard is already served at /install; this adds a footer link to every step
    InstallerUi::section('footer', '<a href="/support">Need help installing?</a>');
}
```

Render a step with your own Blade view, or replace the whole layout:

```php
InstallerUi::view('welcome', 'app.install.welcome')   // custom Blade view for a step
    ->layout('app.install.layout');                    // custom layout
```

The full walkthrough is in [Decorating the web wizard](docs/decorating.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/installer-web](https://opensource.simtabi.com/documentation/laranail/installer-web/)** — installation, usage, how the wizard drives the engine, theming, and what gets generated.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
