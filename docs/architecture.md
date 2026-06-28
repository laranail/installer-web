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
- **Middleware:** `RedirectIfInstalled` (alias `installer.guard`) guards the
  wizard; `EnsureInstalled` (alias `installer.installed`) is an opt-in guard for
  your app routes that redirects to the wizard until installed. Routes also carry
  `throttle:60,1` and CSRF (the `web` group).
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

[← Docs index](../README.md#documentation)
