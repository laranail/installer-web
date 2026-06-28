<?php

declare(strict_types=1);

use Simtabi\Laranail\Installer\Headless\Steps\AbstractStep;
use Simtabi\Laranail\Installer\Headless\Steps\StepRegistry;
use Simtabi\Laranail\Installer\Headless\Support\InstallerContext;
use Simtabi\Laranail\Installer\Web\Facades\InstallerUi;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;
use Simtabi\Laranail\Installer\Web\Support\WebUiRegistry;

it('registers a custom step view via the InstallerUi facade', function (): void {
    InstallerUi::view('welcome', 'my::welcome');

    expect(app(WebUiRegistry::class)->view('welcome'))->toBe('my::welcome');
});

it('swaps a step Livewire component via the InstallerUi facade', function (): void {
    $component = new class extends WizardStep {};

    InstallerUi::component('user', $component::class);

    expect(app(WebUiRegistry::class)->component('user'))->toBe($component::class);
});

it('forwards step registration to the headless engine', function (): void {
    $step = new class extends AbstractStep
    {
        protected string $key = 'extra-web';

        public function run(InstallerContext $context): void {}
    };

    InstallerUi::step($step);

    expect(app(StepRegistry::class)->has('extra-web'))->toBeTrue();
});

it('renders a consumer-registered view for a step (end to end)', function (): void {
    $dir = sys_get_temp_dir() . '/iv-' . uniqid();
    mkdir($dir);
    file_put_contents($dir . '/custom.blade.php', 'CUSTOM-WELCOME-VIEW');
    app('view')->addNamespace('tmp', $dir);

    InstallerUi::view('welcome', 'tmp::custom');

    $this->get(route('installer-web.show', ['step' => 'welcome']))
        ->assertOk()
        ->assertSee('CUSTOM-WELCOME-VIEW');

    array_map(unlink(...), (array) glob($dir . '/*'));
    rmdir($dir);
});
