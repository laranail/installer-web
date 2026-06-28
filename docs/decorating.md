# Decorating the web wizard

The wizard's presentation is fully reshapeable from your own service provider's
`boot()` — no fork, no edits to this package. The entry point is the `InstallerUi`
facade (the web counterpart of the headless [`Installer`](https://opensource.simtabi.com/installer-headless/docs/) DSL).

```php
use Closure;
use Simtabi\Laranail\Installer\Web\Facades\InstallerUi;

public function boot(): void
{
    InstallerUi::view('welcome', 'app.install.welcome')      // custom Blade view for a step
        ->component('user', App\Livewire\FancyUserStep::class) // swap the Livewire component
        ->layout('app.install.layout')                        // custom layout
        ->section('before-content', view('app.install.banner'))// inject content at a slot
        ->fieldType('color', 'app.install.fields.color')       // render a custom field type
        ->step(new App\Install\TermsStep)                      // add a step (forwarded to the engine)
        ->before('user', new App\Install\ProfileStep);         // reorder (forwarded)
}
```

Everything is opt-in; with no calls the wizard renders exactly as shipped.

## What you can decorate

| DSL | Effect |
|---|---|
| `InstallerUi::view($step, $view)` | Render a step with your own Blade view (wins over the convention view / generic form). |
| `InstallerUi::component($step, Class)` | Swap the Livewire component for a field-collecting step. The class must `mount(string $step)`. |
| `InstallerUi::layout($view)` | Use your own layout for the whole wizard (also `installer-web.layout` config). |
| `InstallerUi::section($name, $content)` | Inject content at a layout slot. `$content` = a view name, raw HTML, or a `Closure`. |
| `InstallerUi::fieldType($type, $view)` | Render a custom `Field` type in the generic form. |
| `InstallerUi::step / before / after / removeStep` | Add/insert/remove steps — **forwarded to the headless engine**, so one facade does both UI and pipeline. |

### Layout slots

The shipped layout renders registered content (and a Blade `@stack` of the same name) at:
`head`, `before-content`, `after-content`, `footer`. Use a slot for headers, banners,
analytics tags, or a footer without replacing the layout:

```php
InstallerUi::section('footer', '<a href="/support">Need help?</a>');
InstallerUi::section('head', fn () => '<link rel="stylesheet" href="/css/install.css">');
```

### Branding (no view publish needed)

`config/installer-web.php`:

```php
'layout' => null,            // or your own layout view
'branding' => [
    'title' => 'Acme Setup', // null → "<app name> installer"
    'logo'  => '/img/logo.svg',
    'theme' => '#0ea5e9',    // accent colour (CSS var --installer-accent)
],
```

Optional per-step nav `icon`/`description` come from the headless step config
(`installer.steps.<key>.icon` / `.description`).

## Custom step views & the field component

A controller-rendered step view receives `$current`, `$steps`, `$next`, `$progress`,
`$product`, `$layout` and `$component` — typically it just `@extends($layout)` and embeds
the resolved component: `@livewire($component, ['step' => $current])`.

Inside a (custom) Livewire component view you get `$fields` + `$data`; reuse the built-in
field renderer so your inputs match the default styling and validation display:

```blade
<div>
    <form wire:submit="save" class="space-y-5">
        @foreach ($fields as $field)
            @if ($field->isVisible($data))
                <x-installer-web::field :field="$field" />
            @endif
        @endforeach
        <button type="submit">Continue</button>
    </form>
</div>
```

## Custom Livewire component (hooks)

`WizardStep` is non-final with `saving()`/`saved()` hooks — subclass to react without
forking, then register the subclass:

```php
class FancyUserStep extends \Simtabi\Laranail\Installer\Web\Livewire\WizardStep
{
    protected function saving(): void  { /* mutate $this->data before the step runs */ }
    protected function saved(?string $next): void { /* fire analytics, etc. */ }
}

InstallerUi::component('user', FancyUserStep::class);
```

## Publish-to-override (still supported)

Every view is publishable; replacing the published copy overrides it too:

```bash
php artisan vendor:publish --tag=laranail/installer-web::views
```

The `InstallerUi` registry takes precedence, then a published view, then the package
view. Prefer the registry for programmatic, conditional, or per-product decoration; use
publishing for wholesale redesigns.

[← Docs index](../README.md#documentation)
