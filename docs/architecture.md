# Architecture

`laranail/installer-web` is the presentation layer for the headless installer.
The dependency is one-directional:

```
laranail/installer-web  ──depends on──▶  laranail/installer-headless
```

The web package holds **no install logic and declares no validation rules**. It
contributes only web glue:

- **`BaseInstallerController`** + `WizardController` + routes (`/{prefix}/{step}`)
  — resolve the current step, render it, and forward input to the headless
  `InstallerEngine`. Extend `BaseInstallerController` to customize the HTTP flow.
- A **generic Livewire 4 `WizardStep`** that renders any core step's declared
  `fields()`, validates against the step's **core `rules()`** (pure pass-through —
  no rules here), and submits to the engine. Input-collecting steps (welcome,
  environment, user, license) all use this one component; the environment step
  pre-fills from the existing `.env` because the core step seeds its field
  defaults from it.
- **`StepFormRequest`** for field-less POST steps — its `rules()` returns the
  core step's rules verbatim (no own rules).
- **Middleware:** `RedirectIfInstalled` (alias `laranail-installer-web.guard`)
  guards the wizard; `EnsureInstalled` (alias `laranail-installer-web.installed`)
  is an opt-in guard for your app routes that redirects to the wizard until
  installed. Routes also carry a named rate limiter and CSRF (the `web` group);
  see [Registered names](#registered-names).
- Tailwind + Blade views + a generic field partial (publishable and overridable).
- **Decoration layer** — `InstallerUi` (facade) + `WebUiRegistry` (singleton): the
  controller resolves each step's **view** and **Livewire component** through the
  registry, the layout renders registry **slots** and config **branding**, and the
  field partial consults a **field-type registry**. A consumer reshapes any step's
  presentation from their own provider with no package edit; `InstallerUi` also forwards
  `step/before/after/removeStep` to the headless `Installer` so one facade covers both
  UI and pipeline. See [decorating.md](decorating.md).

Validation, persistence, navigation and all work run through the headless engine,
so the web flow behaves identically to the CLI/headless install.

## Registered names

Every name the package puts into a shared, flat registry carries the vendor and
the package slug, so a sibling package or the application cannot silently replace
it. `tests/Feature/NamingConventionTest.php` reads the live registries of a booted
application and fails on any bare name that is not one of the deprecated aliases
below.

| Registry | Name | Notes |
|---|---|---|
| Route names | `laranail-installer-web.{gate, gate.store, setup, setup.store, index, show, store, product.index, product.show, product.store}` | `route('laranail-installer-web.show', ['step' => 'welcome'])` |
| Rate limiters | `laranail-installer-web.wizard`, `laranail-installer-web.gate` | Tuned by `installer.security.throttle.*` |
| Middleware aliases | `laranail-installer-web.{guard, installed, stores, headers, security, token}` | |
| Livewire component | `laranail-installer-web.wizard-step` | `@livewire('laranail-installer-web.wizard-step', ['step' => $step])` |
| Views | `laranail/installer-web::` (canonical), `laranail-installer-web::` | `view('laranail/installer-web::gate')`; both resolve the same files, including copies published under `resources/views/vendor/laranail-installer-web/` |
| Blade components | `laranail-installer-web::` | `<x-laranail-installer-web::field />` (a Blade tag cannot spell the slash) |
| Artisan commands | none | The `laranail::installer.*` commands belong to `laranail/installer-headless`. |

The config key stays `installer-web.*`: the package opts out of config
namespacing on purpose (`withoutConfigNamespacing()`).

### Deprecated aliases

The names below were shipped before the scoped ones and still work. Each one
emits an `E_USER_DEPRECATED` naming its replacement when used (Laravel logs it to
the `deprecations` channel), and each is removable in the next minor after 0.1.

| Deprecated | Use instead | How it still works |
|---|---|---|
| `installer-web.*` route names | `laranail-installer-web.*` | Resolved through `URL::resolveMissingNamedRoutesUsing()`, so `route()`, `redirect()->route()` and `to_route()` keep working. |
| `throttle:installer` | `throttle:laranail-installer-web.wizard` | Still registered; delegates to the scoped limiter. |
| `throttle:installer-gate` | `throttle:laranail-installer-web.gate` | Still registered; delegates to the scoped limiter. |
| `installer-wizard-step` (Livewire) | `laranail-installer-web.wizard-step` | Still registered; mounts the same component. |
| `RouteNameFallback::register()` | `hasDeprecatedRouteNames()` on the package, or package-tools' `BareRouteNameAliases::install()` | Still installs the same fallback, through `BareRouteNameAliases`, and emits `E_USER_DEPRECATED`. |

> The route fallback is package-tools' `BareRouteNameAliases`, declared on the
> package with `hasDeprecatedRouteNames(prefixes: ['installer-web.' => 'laranail-installer-web.'])`.
> It announces each bare name once per process rather than on every `route()` call.
> `Route::has('installer-web.index')` answers `false`: it reads the route
> collection directly and never reaches the fallback. Ask for the scoped name.
> The route fallback chains to any missing-route resolver registered before it,
> but Laravel holds only one, so a resolver registered *after* this package
> replaces it.

[← Docs index](../README.md#documentation)
