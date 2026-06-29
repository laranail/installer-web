<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\Installer\Headless\Events\UnauthorizedInstallerAccess;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    app(InstallationState::class)->clear();
});

afterEach(fn () => app(InstallationState::class)->clear());

it('serves the wizard unrestricted by default (no security configured)', function (): void {
    $this->get(route('installer-web.show', ['step' => 'welcome']))->assertOk();
});

it('adds hardening headers to installer responses', function (): void {
    $this->get(route('installer-web.show', ['step' => 'welcome']))
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('denies a request from a disallowed IP with a generic 403', function (): void {
    config()->set('installer.security.allowed_ips', ['203.0.113.0/24']);
    Event::fake([UnauthorizedInstallerAccess::class]);

    $this->call('GET', route('installer-web.show', ['step' => 'welcome']), server: ['REMOTE_ADDR' => '198.51.100.5'])
        ->assertForbidden()
        ->assertSee('Access denied');

    Event::assertDispatched(UnauthorizedInstallerAccess::class, fn ($e): bool => $e->reason === 'ip');
});

it('allows a request from an allowlisted IP', function (): void {
    config()->set('installer.security.allowed_ips', ['198.51.100.0/24']);

    $this->call('GET', route('installer-web.show', ['step' => 'welcome']), server: ['REMOTE_ADDR' => '198.51.100.5'])
        ->assertOk();
});

it('denies plain HTTP when HTTPS is required', function (): void {
    config()->set('installer.security.require_https', true);

    $this->get(route('installer-web.show', ['step' => 'welcome']))->assertForbidden();
});

it('redirects to the gate when a token is configured and absent', function (): void {
    config()->set('installer.security.token', 'sekret-token');

    $this->get(route('installer-web.show', ['step' => 'welcome']))
        ->assertRedirect(route('installer-web.gate'));
});

it('lets a valid token through (via query) and authorizes the session', function (): void {
    config()->set('installer.security.token', 'sekret-token');

    $this->get(route('installer-web.show', ['step' => 'welcome']) . '?token=sekret-token')
        ->assertOk();
});

it('accepts a valid token at the gate and rejects an invalid one', function (): void {
    config()->set('installer.security.token', 'sekret-token');

    $this->post(route('installer-web.gate.store'), ['token' => 'wrong'])
        ->assertSessionHasErrors('token');

    $this->post(route('installer-web.gate.store'), ['token' => 'sekret-token'])
        ->assertRedirect(route('installer-web.index'))
        ->assertSessionMissing('errors');

    expect(session('installer.authorized'))->toBeTrue();
});
