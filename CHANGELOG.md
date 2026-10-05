# Changelog

All notable changes to `laranail/installer-web` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `NamingConventionTest`, which reads the live route, rate-limiter, middleware,
  Artisan and Livewire registries and fails on any bare name the package owns.
  It now runs on package-tools' `AssertsRegisteredNames` and also covers view
  namespaces and Blade components.

### Changed

- `laravel/framework ^13.0` is now declared in `require`. `src/` uses `FormRequest` from `Illuminate\Foundation`, which no `illuminate/*` component ships, so the dependency only arrived through the host application.
- Route names are now `laranail-installer-web.*` (were `installer-web.*`). Every
  internal `route()` / `redirectRoute()` call and shipped view uses the scoped names.
- Rate limiters are now `laranail-installer-web.wizard` and
  `laranail-installer-web.gate` (were `installer` and `installer-gate`); the wizard
  and gate routes throttle through the scoped names.
- The wizard-step Livewire component is now `laranail-installer-web.wizard-step`
  (was `installer-wizard-step`).
- The deprecated bare route names are resolved by package-tools' shared
  `BareRouteNameAliases`, declared with `hasDeprecatedRouteNames()`. It keeps the
  chaining, string-only and public-router behaviour, and raises the
  `E_USER_DEPRECATED` once per bare name per process rather than on every call.
- Views are registered as `laranail/installer-web::` (canonical) beside
  `laranail-installer-web::`, and the package's own views, controllers, Blade
  component directory and `denied_view` / `gate_view` / `setup_view` defaults use
  the slash form. The hyphen form resolves the same files, including views a host
  published under `resources/views/vendor/laranail-installer-web/`, so a published
  config naming it keeps working. Blade tags keep `<x-laranail-installer-web::… />`.
- Requires `laranail/package-tools ^0.1.3`, the first release with
  `BareRouteNameAliases`, the shared naming assertions, and the slash form
  registered beside a hyphen view namespace.
- `composer.json` no longer declares a `vcs` repository for `laranail/db-tools`. Nothing in the
  `require` closure needs it: `laranail/installer-headless` carries db-tools in `require-dev` only,
  which Composer never resolves for a dependency (`composer why laranail/db-tools` finds nothing
  after a fresh update).

### Deprecated

- The bare route names `installer-web.*`. They still resolve, through
  `URL::resolveMissingNamedRoutesUsing()`, and emit `E_USER_DEPRECATED`.
  `Route::has('installer-web.*')` now answers `false`; ask for the scoped name.
- The bare rate limiters `installer` and `installer-gate`. Still registered, they
  delegate to the scoped limiters and emit `E_USER_DEPRECATED`.
- The bare Livewire name `installer-wizard-step`. Still registered, it mounts the
  same component and emits `E_USER_DEPRECATED`.
- `RouteNameFallback::register()`. It emits `E_USER_DEPRECATED` and delegates to
  `BareRouteNameAliases::install()` with the same prefixes, so a caller still gets
  a working fallback.
- All are removable in the next minor after 0.1.

### Fixed

- `docs/architecture.md` named the middleware aliases `installer.guard` and
  `installer.installed`, and both docs pages described a `throttle:60,1` the
  routes do not carry.
- `RouteNameFallback` passed the previously installed missing-route resolver's
  answer through unchecked, so a foreign resolver returning anything but a
  string (an object, an int, an array) raised a `TypeError` inside `route()`
  under `strict_types`. A non-string answer now reads as "not resolved".
- `RouteNameFallback` asks the public `Router::has()` whether the scoped route
  exists instead of reading the URL generator's protected route collection
  through a bound closure. `register()` takes the router as an optional second
  argument; the single-argument call still works.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/installer-web/compare/v0.1.0...HEAD
