<?php

declare(strict_types=1);

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;
use Simtabi\Laranail\Installer\Web\Http\Middleware\UseInstallerStores;

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    app(InstallationState::class)->clear();

    $this->dir = sys_get_temp_dir() . '/installer-sh-' . uniqid();
    mkdir($this->dir, 0755, true);
    config()->set('installer.env.path', $this->dir . '/.env');
});

afterEach(function (): void {
    app(InstallationState::class)->clear();
    @unlink($this->dir . '/.env');
    @rmdir($this->dir);
});

it('forces file session/cache stores for installer requests (G1)', function (): void {
    config()->set('session.driver', 'database');
    config()->set('cache.default', 'database');
    config()->set('installer.environment.session_store', 'file');
    config()->set('installer.environment.cache_store', 'file');

    (new UseInstallerStores)->handle(Request::create('/install'), fn ($r): ResponseFactory|Response => response('ok'));

    expect(config('session.driver'))->toBe('file')
        ->and(config('cache.default'))->toBe('file');
});

it('leaves the app stores untouched when overrides are null', function (): void {
    config()->set('session.driver', 'database');
    config()->set('installer.environment.session_store');
    config()->set('installer.environment.cache_store');

    (new UseInstallerStores)->handle(Request::create('/install'), fn ($r): ResponseFactory|Response => response('ok'));

    expect(config('session.driver'))->toBe('database');
});

it('sets the gate password via the web setup screen, no SSH (G5)', function (): void {
    $this->post(route('installer-web.setup.store'), ['password' => 'super-secret-pw'])
        ->assertRedirect(route('installer-web.index'));

    expect((string) file_get_contents($this->dir . '/.env'))->toContain('INSTALLER_TOKEN_HASH=');
});

it('locks the installer to the current IP via the setup screen', function (): void {
    $this->post(route('installer-web.setup.store'), ['lock_ip' => '1'])
        ->assertRedirect(route('installer-web.index'));

    expect((string) file_get_contents($this->dir . '/.env'))->toContain('INSTALLER_ALLOWED_IPS=');
});

it('redirects setup to the wizard once a token is already configured', function (): void {
    config()->set('installer.security.token', 'already-set');

    $this->get(route('installer-web.setup'))->assertRedirect(route('installer-web.index'));
});
