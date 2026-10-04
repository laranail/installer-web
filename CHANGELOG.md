# Changelog

All notable changes to `laranail/installer-web` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `NamingConventionTest`, which reads the live route, rate-limiter, middleware,
  Artisan and Livewire registries and fails on any bare name the package owns.

### Changed

- Route names are now `laranail-installer-web.*` (were `installer-web.*`). Every
  internal `route()` / `redirectRoute()` call and shipped view uses the scoped names.
- Rate limiters are now `laranail-installer-web.wizard` and
  `laranail-installer-web.gate` (were `installer` and `installer-gate`); the wizard
  and gate routes throttle through the scoped names.
- The wizard-step Livewire component is now `laranail-installer-web.wizard-step`
  (was `installer-wizard-step`).

### Deprecated

- The bare route names `installer-web.*`. They still resolve, through
  `URL::resolveMissingNamedRoutesUsing()`, and emit `E_USER_DEPRECATED`.
  `Route::has('installer-web.*')` now answers `false`; ask for the scoped name.
- The bare rate limiters `installer` and `installer-gate`. Still registered, they
  delegate to the scoped limiters and emit `E_USER_DEPRECATED`.
- The bare Livewire name `installer-wizard-step`. Still registered, it mounts the
  same component and emits `E_USER_DEPRECATED`.
- All are removable in the next minor after 0.1.

### Fixed

- `docs/architecture.md` named the middleware aliases `installer.guard` and
  `installer.installed`, and both docs pages described a `throttle:60,1` the
  routes do not carry.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/installer-web/compare/v0.1.0...HEAD
