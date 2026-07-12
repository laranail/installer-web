<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    app(InstallationState::class)->clear();
});

afterEach(fn () => app(InstallationState::class)->clear());

it('runs a step via the controller and advances to the next', function (): void {
    $this->post(route('installer-web.store', ['step' => 'welcome']), ['locale' => 'en'])
        ->assertRedirect(route('installer-web.show', ['step' => 'requirements']));

    expect(app(InstallationState::class)->isStepComplete('welcome'))->toBeTrue();
});
