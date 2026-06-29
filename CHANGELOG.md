# Changelog

All notable changes to `laranail/installer-web` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-06-29

### Added

- **Access lockdown enforcement:** `installer.headers` (security response headers),
  `installer.security` (IP/host/HTTPS/window via the headless policy → generic 403 +
  audit event), and `installer.token` (header/query/signed-link/session gate) middleware,
  a `GateController` with neutral gate/denied views, configurable named rate-limiters
  (`installer` + stricter `installer-gate`), and conditional route registration that 404s
  the wizard once installed.
- **Shared-hosting support:** `installer.stores` middleware forces file session/cache for
  installer requests (so the wizard works pre-migration on db-backed stores), and a
  `SetupController` + view to set the gate password / lock-to-IP from the browser
  (written to `.env`) — no shell required.

## [0.1.0] - 2026-06-28

First public release. The pre-1.0 development history below is folded into this
initial release.

### Added

- **Per-product wizard routes** — `/{prefix}/p/{product}/{step}` drives the wizard
  through `InstallerEngine::forProduct($product)` (each product its own pipeline +
  isolated state). The default `/{prefix}/{step}` routes are unchanged; all
  orchestration stays in the headless core.
- The generic field partial now renders `textarea` and pass-through input types
  (`number`/`url`/`tel`/`date`) so consumer-declared custom fields render correctly.
- **Web decoration layer** — the `InstallerUi` facade + `WebUiRegistry`: reshape any
  step's presentation from a consumer provider with no package edit — per-step view
  override, per-step Livewire component swap, layout slots (`head`/`before-content`/
  `after-content`/`footer`), config-driven branding (title/logo/theme) + layout override,
  a custom field-type registry, and forwarded step add/remove/update. `WizardStep` is now
  non-final with `saving()`/`saved()` hooks. Ships a reusable `<x-installer-web::field>`
  Blade component. See `docs/decorating.md`.

### Changed

- Default pipeline step key `admin` → `user` (the headless package generalized admin
  creation to a configurable user). The web layer is generic; no consumer-facing API
  changed beyond the step key.

### Initial build

### Added

- Tailwind + Blade install wizard driving `laranail/installer-headless`.
- `BaseInstallerController` + `WizardController` + routes (configurable prefix) that
  forward step input to the headless engine; no install logic in the web layer.
- **Generic Livewire 4 `WizardStep`** that renders any core step's fields and
  validates against the step's **core rules** (the web layer declares none of its
  own). The environment step pre-fills from the existing `.env` (edit-in-place).
- **`StepFormRequest`** deriving its rules from the core step (pure pass-through).
- Middleware: `installer.guard` (install-once) and opt-in `installer.installed`
  (app-route guard); route rate limiting; CSRF.
- Pest feature tests, PHPStan level 8, Pint and Rector configuration.
