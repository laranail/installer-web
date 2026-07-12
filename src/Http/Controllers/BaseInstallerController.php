<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Controllers;

use Illuminate\Routing\Controller;
use Simtabi\Laranail\Installer\Headless\Contracts\Step;
use Simtabi\Laranail\Installer\Headless\InstallerEngine;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Installer\Web\Support\WebUiRegistry;

/**
 * Base HTTP plumbing for the install wizard, shared by the shipped controller and
 * any consumer controller. Web-only glue (routing/view resolution) — it holds no
 * install logic; everything is delegated to the headless {@see InstallerEngine}.
 * This is an extension point: subclass it to customise the wizard's HTTP flow.
 */
abstract class BaseInstallerController extends Controller
{
    public function __construct(protected readonly InstallerEngine $engine) {}

    /** The engine scoped to a product's pipeline (or the default engine). */
    protected function engine(?string $product = null): InstallerEngine
    {
        return $product !== null && $product !== '' ? $this->engine->forProduct($product) : $this->engine;
    }

    protected function firstStep(?string $product = null): string
    {
        $steps = $this->engine($product)->orderedSteps();

        return $steps === [] ? 'welcome' : $steps[0]->key();
    }

    protected function nextStep(string $step, ?string $product = null): ?string
    {
        return $this->engine($product)->next($step)?->key();
    }

    protected function isKnownStep(string $step, ?string $product = null): bool
    {
        return array_any($this->engine($product)->orderedSteps(), fn (Step $candidate): bool => $candidate->key() === $step);
    }

    /**
     * Shared view data: the step list, current key, next key, progress, product, the
     * resolved layout and the Livewire component class for field steps (decoration-aware).
     *
     * @return array<string, mixed>
     */
    protected function baseViewData(string $step, ?string $product = null): array
    {
        $engine = $this->engine($product);
        $registry = app(WebUiRegistry::class);

        return [
            'steps' => $engine->orderedSteps(),
            'current' => $step,
            'next' => $this->nextStep($step, $product),
            'progress' => $engine->progress(),
            'product' => $product,
            'layout' => $registry->layout() ?? config('installer-web.layout') ?? 'installer-web::layouts.app',
            'component' => $registry->component($step) ?? WizardStep::class,
        ];
    }

    /**
     * The Blade view for a step: a consumer-registered view, else the generic form
     * (field steps) or the per-step convention view (`installer-web::steps.{step}`).
     */
    protected function stepView(string $step, ?string $product = null): string
    {
        $registered = app(WebUiRegistry::class)->view($step);

        if ($registered !== null) {
            return $registered;
        }

        return $this->engine($product)->fields($step) !== []
            ? 'installer-web::steps.form'
            : "installer-web::steps.{$step}";
    }
}
