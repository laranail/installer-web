<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\Installer\Headless\InstallerEngine;

/**
 * Validates a wizard step's POST using the step's **core-defined** rules.
 *
 * This is a pure translation layer: it declares no rules of its own — it returns
 * the exact `rules()` the core step exposes for the current input. The single
 * validation source lives in the headless engine.
 */
final class StepFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $engine = app(InstallerEngine::class);
        $step = (string) $this->route('step');

        // Unknown step → no rules; the controller redirects gracefully instead of
        // this FormRequest 500-ing during request resolution.
        if (! $engine->steps()->has($step)) {
            return [];
        }

        return $engine->rules($step, $this->all());
    }
}
