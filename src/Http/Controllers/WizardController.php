<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\Installer\Headless\Exceptions\InstallerException;
use Simtabi\Laranail\Installer\Headless\Support\InstallerContext;
use Simtabi\Laranail\Installer\Headless\Support\RequirementsChecker;
use Simtabi\Laranail\Installer\Web\Http\Requests\StepFormRequest;

/**
 * Drives the install wizard. Renders each step and forwards collected input to
 * the headless engine — it performs no installation work itself. Steps that
 * collect input render the generic Livewire component (which validates against the
 * core step's rules); field-less steps (requirements/final) post here.
 */
final class WizardController extends BaseInstallerController
{
    // Default (single-product) routes.
    public function index(): RedirectResponse
    {
        return $this->doIndex(null);
    }

    public function show(string $step): View|RedirectResponse
    {
        return $this->doShow($step, null);
    }

    public function store(StepFormRequest $request, string $step): RedirectResponse
    {
        return $this->doStore($request, $step, null);
    }

    // Per-product routes — params declared in URI order ({product}/{step}) to avoid
    // positional route-binding ambiguity.
    public function productIndex(string $product): RedirectResponse
    {
        return $this->doIndex($product);
    }

    public function productShow(string $product, string $step): View|RedirectResponse
    {
        return $this->doShow($step, $product);
    }

    public function productStore(StepFormRequest $request, string $product, string $step): RedirectResponse
    {
        return $this->doStore($request, $step, $product);
    }

    private function doIndex(?string $product): RedirectResponse
    {
        $this->ensurePipeline($product);

        return $this->stepRedirect($this->firstStep($product), $product);
    }

    private function doShow(string $step, ?string $product): View|RedirectResponse
    {
        $this->ensurePipeline($product);

        if (! $this->isKnownStep($step, $product)) {
            return $this->indexRedirect($product);
        }

        $data = $this->baseViewData($step, $product);

        if ($step === 'requirements') {
            $data['report'] = app(RequirementsChecker::class)->all();
        }

        // The view is decoration-aware: a consumer-registered view, else the generic
        // Livewire form (field steps) or the per-step convention view (field-less).
        return view($this->stepView($step, $product), $data);
    }

    private function doStore(StepFormRequest $request, string $step, ?string $product): RedirectResponse
    {
        $this->ensurePipeline($product);

        if (! $this->isKnownStep($step, $product)) {
            return $this->indexRedirect($product);
        }

        try {
            $this->engine($product)->runStep($step, InstallerContext::fromInput($request->except('_token')));
        } catch (InstallerException $exception) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['installer' => $exception->getMessage()]);
        }

        $next = $this->nextStep($step, $product);

        return $next === null
            ? redirect((string) config('installer.redirect_to', '/'))
            : $this->stepRedirect($next, $product);
    }

    /** Guard against an empty/all-unknown pipeline (avoids an index↔show redirect loop). */
    private function ensurePipeline(?string $product): void
    {
        if ($this->engine($product)->orderedSteps() === []) {
            abort(404, 'No installable steps are configured for this product.');
        }
    }

    private function stepRedirect(string $step, ?string $product): RedirectResponse
    {
        return $product !== null
            ? redirect()->route('installer-web.product.show', ['product' => $product, 'step' => $step])
            : redirect()->route('installer-web.show', ['step' => $step]);
    }

    private function indexRedirect(?string $product): RedirectResponse
    {
        return $product !== null
            ? redirect()->route('installer-web.product.index', ['product' => $product])
            : redirect()->route('installer-web.index');
    }
}
