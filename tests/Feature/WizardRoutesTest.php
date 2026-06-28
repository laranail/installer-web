<?php

declare(strict_types=1);

use Simtabi\Laranail\Installer\Headless\Support\InstallationState;

beforeEach(fn () => app(InstallationState::class)->clear());

afterEach(fn () => app(InstallationState::class)->clear());

it('redirects the index to the first step', function (): void {
    $this->get('/install')->assertRedirect(route('installer-web.show', ['step' => 'welcome']));
});

it('renders the welcome step when not installed', function (): void {
    $this->get(route('installer-web.show', ['step' => 'welcome']))
        ->assertOk()
        ->assertSee('Welcome');
});

it('redirects an unknown step back to the index', function (): void {
    $this->get(route('installer-web.show', ['step' => 'does-not-exist']))
        ->assertRedirect(route('installer-web.index'));
});

it('blocks the wizard once installed (install-once guard)', function (): void {
    config()->set('installer.redirect_to', '/home');
    app(InstallationState::class)->markInstalled();

    $this->get(route('installer-web.show', ['step' => 'welcome']))->assertRedirect('/home');
});
