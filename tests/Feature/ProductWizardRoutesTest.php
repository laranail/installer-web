<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;

beforeEach(function (): void {
    app(InstallationState::class)->clear();
    config()->set('installer.products.addon', ['steps' => ['requirements', 'final']]);
});

afterEach(function (): void {
    app(InstallationState::class)->clear();
    app(InstallationState::class)->forProduct('addon')->clear();
});

it('redirects a product index to the product first step', function (): void {
    $this->get(route('installer-web.product.index', ['product' => 'addon']))
        ->assertRedirect(route('installer-web.product.show', ['product' => 'addon', 'step' => 'requirements']));
});

it('renders a product-scoped step resolved via forProduct', function (): void {
    $this->get(route('installer-web.product.show', ['product' => 'addon', 'step' => 'requirements']))
        ->assertOk();
});

it('redirects a step not in the product pipeline back to the product index', function (): void {
    // `welcome` is not part of addon's [requirements, final] pipeline.
    $this->get(route('installer-web.product.show', ['product' => 'addon', 'step' => 'welcome']))
        ->assertRedirect(route('installer-web.product.index', ['product' => 'addon']));
});

it('leaves the default (product-less) routes working', function (): void {
    $this->get('/install')->assertRedirect(route('installer-web.show', ['step' => 'welcome']));
});

it('404s a product whose steps are all unknown (no redirect loop)', function (): void {
    config()->set('installer.products.broken', ['steps' => ['nope-1', 'nope-2']]);

    $this->get(route('installer-web.product.index', ['product' => 'broken']))->assertNotFound();

    app(InstallationState::class)->forProduct('broken')->clear();
});

it('redirects (does not 500) a POST to an unknown step', function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->post(route('installer-web.store', ['step' => 'does-not-exist']), [])
        ->assertRedirect(route('installer-web.index'));
});

it('guards the product wizard on the product install state, not the app', function (): void {
    config()->set('installer.redirect_to', '/home');
    app(InstallationState::class)->markInstalled(); // app installed, product is not

    // The product wizard is still reachable (install an add-on after the app).
    $this->get(route('installer-web.product.show', ['product' => 'addon', 'step' => 'requirements']))
        ->assertOk();

    // Once THIS product is installed, its wizard is blocked.
    app(InstallationState::class)->forProduct('addon')->markInstalled();

    $this->get(route('installer-web.product.show', ['product' => 'addon', 'step' => 'requirements']))
        ->assertRedirect('/home');
});
