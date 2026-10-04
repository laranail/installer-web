<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Livewire;

use Override;

/**
 * The component behind the bare `installer-wizard-step` Livewire name.
 *
 * It is {@see WizardStep} in every respect; it exists only so mounting the
 * component by its pre-0.1.x name emits a deprecation, which a plain second
 * registration of `WizardStep` could not do.
 *
 * The bare name is deprecated in favour of `laranail-installer-web.wizard-step`
 * and removable in the next minor after 0.1; this class goes with it.
 *
 * @internal
 */
final class LegacyWizardStep extends WizardStep
{
    #[Override]
    public function mount(string $step): void
    {
        trigger_error(
            'Livewire component "installer-wizard-step" (laranail/installer-web) is deprecated and will be removed in the next minor after 0.1; use "laranail-installer-web.wizard-step".',
            E_USER_DEPRECATED,
        );

        parent::mount($step);
    }
}
