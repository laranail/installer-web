# laranail/installer-web

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/installer-web.svg)](https://packagist.org/packages/laranail/installer-web)
[![Tests](https://github.com/laranail/installer-web/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/installer-web/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/installer-web/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/installer-web/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> A Tailwind + Blade + **Livewire 4** install wizard for [`laranail/installer-headless`](https://opensource.simtabi.com/documentation/laranail/installer-headless/) — pure presentation and input collection; every operation is delegated to the headless engine (no install logic, never touches files or the database directly).

Requires PHP `^8.4.1`, Laravel `^13`, Livewire `^4.2`, and `laranail/installer-headless` (+ `laranail/package-tools`).

## Install

```bash
composer require laranail/installer-web
```

Publish the assets/config, then visit the install route — see the docs for the exact steps.

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/installer-web](https://opensource.simtabi.com/documentation/laranail/installer-web/)** — installation, usage, how the wizard drives the engine, theming, and what gets generated.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
