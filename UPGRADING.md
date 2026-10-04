# Upgrading

This package follows [Semantic Versioning](https://semver.org). Breaking changes
and their migration steps are documented here per major/minor version.

## Unreleased

Nothing breaks: the old names keep working as deprecated aliases and emit
`E_USER_DEPRECATED`. Move to the scoped names before the next minor after 0.1,
which may remove the aliases.

| Replace | With |
|---|---|
| `route('installer-web.<name>')` | `route('laranail-installer-web.<name>')` |
| `throttle:installer` | `throttle:laranail-installer-web.wizard` |
| `throttle:installer-gate` | `throttle:laranail-installer-web.gate` |
| `@livewire('installer-wizard-step', ...)` | `@livewire('laranail-installer-web.wizard-step', ...)` |

`Route::has('installer-web.<name>')` now answers `false`. Change any such guard to
the scoped name.

## 0.1.0

Initial release — nothing to upgrade from.
