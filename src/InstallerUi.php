<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web;

use Closure;
use Simtabi\Laranail\Installer\Headless\Contracts\Step;
use Simtabi\Laranail\Installer\Headless\Facades\Installer;
use Simtabi\Laranail\Installer\Web\Support\WebUiRegistry;

/**
 * Fluent web-decoration DSL — the web counterpart of the headless {@see Installer}
 * facade. Reshape any step's presentation (view, Livewire component, slots, field
 * types, layout/branding) from a consumer provider's boot() with no package edit.
 *
 * Step add/remove/update is the headless engine's concern; this manager forwards
 * `step/before/after/removeStep` to {@see Installer} so a web consumer can do
 * everything through one facade. Every method returns $this for chaining.
 */
final readonly class InstallerUi
{
    public function __construct(private WebUiRegistry $registry) {}

    /** Render this step with a custom Blade view (field steps own embedding the component). */
    public function view(string $step, string $view): static
    {
        $this->registry->setView($step, $view);

        return $this;
    }

    /**
     * Swap the Livewire component for a (field-collecting) step. The component must
     * `mount(string $step)`.
     *
     * @param  class-string  $component
     */
    public function component(string $step, string $component): static
    {
        $this->registry->setComponent($step, $component);

        return $this;
    }

    /** Override the wizard layout view (else config `installer-web.layout`, else the shipped layout). */
    public function layout(string $view): static
    {
        $this->registry->setLayout($view);

        return $this;
    }

    /** Inject content at a layout slot (`head`, `before-content`, `after-content`, `footer`). */
    public function section(string $name, string|Closure $content): static
    {
        $this->registry->addSection($name, $content);

        return $this;
    }

    /** Register a Blade view that renders a custom field `$type` in the generic form. */
    public function fieldType(string $type, string $view): static
    {
        $this->registry->setFieldType($type, $view);

        return $this;
    }

    // --- one-stop step ops (forwarded to the headless Installer DSL) ---

    public function step(Step $step): static
    {
        Installer::step($step);

        return $this;
    }

    public function before(string $key, Step $step): static
    {
        Installer::before($key, $step);

        return $this;
    }

    public function after(string $key, Step $step): static
    {
        Installer::after($key, $step);

        return $this;
    }

    public function removeStep(string $key): static
    {
        Installer::removeStep($key);

        return $this;
    }
}
