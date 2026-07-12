<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simtabi\Laranail\Installer\Headless\Exceptions\InstallerException;
use Simtabi\Laranail\Installer\Headless\InstallerEngine;

/**
 * Generic wizard step component: renders any core step's declared fields, binds
 * them to `$data`, validates against the step's **core rules** (pass-through — it
 * declares none of its own), and submits to the headless engine, which performs
 * the work and advances. The base extension point consumers reuse for custom steps:
 * subclass it (register via `InstallerUi::component()`) and override the
 * {@see saving()}/{@see saved()} hooks to react without forking.
 */
class WizardStep extends Component
{
    public string $step = '';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(string $step): void
    {
        $this->step = $step;
        $this->data = app(InstallerEngine::class)->values($step);
    }

    public function save(): void
    {
        $engine = app(InstallerEngine::class);

        // Pure pass-through of the core rules, namespaced to the bound `data` array.
        $rules = [];
        $attributes = [];

        foreach ($engine->rules($this->step, $this->data) as $name => $rule) {
            $rules["data.{$name}"] = $rule;
        }

        foreach ($engine->fields($this->step) as $field) {
            $attributes["data.{$field->name}"] = $field->label;
        }

        $this->validate($rules, [], $attributes);

        $this->saving();

        try {
            $next = $engine->submit($this->step, $this->data);
        } catch (InstallerException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        }

        $this->saved($next);

        $next === null
            ? $this->redirect((string) config('installer.redirect_to', '/'))
            : $this->redirectRoute('installer-web.show', ['step' => $next]);
    }

    /** Hook: after validation, before the step executes. Override to react/augment `$data`. */
    protected function saving(): void {}

    /** Hook: after the step executed successfully (`$next` = the next step key, or null when finished). */
    protected function saved(?string $next): void {}

    public function render(): View
    {
        return view('installer-web::livewire.wizard-step', [
            'fields' => app(InstallerEngine::class)->fields($this->step),
        ]);
    }
}
