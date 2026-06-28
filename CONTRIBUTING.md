# Contributing

Thanks for helping improve `laranail/installer-web`.

## Workflow

1. Fork and branch from `main`.
2. Make your change with tests.
3. Ensure the suite is green: `composer test` and `composer lint`.
4. Open a pull request describing the change and the why.

## Conventions

- PHP `^8.4.1`, Laravel `^13`, Livewire `^4`. `declare(strict_types=1)` everywhere.
- Code style: Laravel Pint (`composer format`). PHPStan/Larastan level 8.
- **No install logic in this package.** It is presentation + input collection
  only; all work is delegated to `laranail/installer-headless`'s engine. If you
  find yourself writing install logic here, it belongs in the headless package.
- Livewire actions return `void` and use `$this->redirect()/redirectRoute()`.
