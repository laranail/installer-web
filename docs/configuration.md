# Configuration

Web-specific keys live under `installer-web.*` (`config/installer-web.php`):

| Key | Default | Purpose |
|---|---|---|
| `prefix` | `'install'` | URL prefix the wizard is served under. |
| `middleware` | `['web']` | Middleware applied to wizard routes (the install-once guard and `throttle:60,1` are always appended). |

Behavioral configuration — locales, requirements, user model/fields, license,
the step pipeline, the post-install redirect (`installer.redirect_to`) and the
`.env` paths — all live in the **headless** package's `installer.*` config. See
the [installer-headless configuration docs](https://opensource.simtabi.com/installer-headless/docs/configuration).

[← Docs index](../README.md#documentation)
