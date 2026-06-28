# laranail/installer-web

[![Tests](https://github.com/laranail/installer-web/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/installer-web/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Tailwind + Blade + **Livewire 4** install wizard for
> [`laranail/installer-headless`](https://opensource.simtabi.com/installer-headless/).
> Pure presentation and input collection — every operation is delegated to the
> headless engine. It holds no install logic and never touches files or the
> database directly.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [How it works](#how-it-works)
- [Documentation](#documentation)
- [License](#license)

## Requirements

| Requirement | Constraint |
|---|---|
| PHP | `^8.4.1` (8.4, 8.5) |
| Laravel | `^13.0` |
| Livewire | `^4.2` |
| Depends on | `laranail/installer-headless`, `laranail/package-tools` |

## Installation

```bash
composer require laranail/installer-web
```

Both providers auto-discover. Publish the config to change the route prefix:

```bash
php artisan vendor:publish --tag=laranail/installer-web::config
```

## Usage

Visit `/install` (configurable prefix). The wizard walks through welcome →
requirements → environment → migrate → user → (license) → final, and redirects
to `config('installer.redirect_to')` when complete. Once installed, the
install-once guard blocks the wizard.

## How it works

- `WizardController` renders each step and forwards collected input to the
  headless `InstallerEngine` — it performs no install work itself.
- The **environment/database step is a Livewire 4 component** that pre-fills from
  the existing `.env` (edit-in-place) and hands values to the engine, which runs
  the connection test and the atomic `.env` write.
- Routes are guarded by the install-once middleware and rate limiting; the `web`
  group provides CSRF.

## Documentation

Hosted at `opensource.simtabi.com/installer-web/docs/`; the same pages live under
`docs/`:

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Architecture](docs/architecture.md)
- [Decorating the wizard](docs/decorating.md) — the `InstallerUi` DSL: per-step views/components, slots, branding, custom field types

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
