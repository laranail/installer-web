<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Simtabi\Laranail\Installer\Headless\Facades\Installer;
use Simtabi\Laranail\Installer\Headless\Wizard\Field;
use Simtabi\Laranail\Installer\Web\Facades\InstallerUi;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;

it('applies branding and renders registered slots', function (): void {
    config()->set('installer-web.branding.theme', '#abcdef');
    config()->set('installer-web.branding.title', 'My Installer');
    InstallerUi::section('footer', 'FOOTER-SLOT-CONTENT');

    $this->get(route('installer-web.show', ['step' => 'welcome']))
        ->assertOk()
        ->assertSee('#abcdef', false)
        ->assertSee('My Installer')
        ->assertSee('FOOTER-SLOT-CONTENT');
});

it('renders a registered custom field type', function (): void {
    $dir = sys_get_temp_dir() . '/ift-' . uniqid();
    mkdir($dir);
    file_put_contents($dir . '/color.blade.php', 'CUSTOM-COLOR-FIELD');
    app('view')->addNamespace('tmpf', $dir);

    InstallerUi::fieldType('color', 'tmpf::color');
    Installer::field('welcome', new Field('brand_color', 'Brand colour', 'color'));

    Livewire::test(WizardStep::class, ['step' => 'welcome'])
        ->assertSee('CUSTOM-COLOR-FIELD');

    array_map(unlink(...), (array) glob($dir . '/*'));
    rmdir($dir);
});

it('exposes a reusable <x-installer-web::field> component', function (): void {
    $field = new Field('nickname', 'Nickname', 'text');
    view()->share('errors', new ViewErrorBag); // Laravel shares this per-request; bare render needs it

    $html = Blade::render('<x-installer-web::field :field="$field" />', ['field' => $field]);

    expect($html)->toContain('Nickname')->toContain('data.nickname');
});
