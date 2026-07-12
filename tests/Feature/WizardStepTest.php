<?php

declare(strict_types=1);

use Livewire\Livewire;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;
use Simtabi\Laranail\Installer\Web\Livewire\WizardStep;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/installer-web-' . uniqid();
    mkdir($this->dir, 0755, true);
    config()->set('installer.env.path', $this->dir . '/.env');
    config()->set('installer.env.example', $this->dir . '/.env.example');
    file_put_contents($this->dir . '/.env.example', "APP_NAME=Example\nDB_CONNECTION=sqlite\n");

    $state = app(InstallationState::class);
    $state->clear();
    // Reach the environment step: earlier steps complete (ordering guard).
    $state->markStepComplete('welcome');
    $state->markStepComplete('requirements');
});

afterEach(function (): void {
    app(InstallationState::class)->clear();
    foreach (array_merge(glob($this->dir . '/*') ?: [], glob($this->dir . '/.*') ?: []) as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    @rmdir($this->dir);
});

it('writes the .env through the core engine and redirects', function (): void {
    $dbFile = $this->dir . '/db.sqlite';

    Livewire::test(WizardStep::class, ['step' => 'environment'])
        ->set('data.app_name', 'My Site')
        ->set('data.app_url', 'http://my.test')
        ->set('data.database_driver', 'sqlite')
        ->set('data.database_name', $dbFile)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('installer-web.show', ['step' => 'migrate']));

    $env = file_get_contents($this->dir . '/.env');

    expect($env)->toContain('APP_NAME="My Site"')
        ->and($env)->toContain('DB_CONNECTION=sqlite')
        ->and($env)->toContain('DB_DATABASE=' . $dbFile);
});

it('pre-fills from an existing .env (edit in place)', function (): void {
    file_put_contents($this->dir . '/.env', "APP_NAME=\"Existing App\"\nDB_CONNECTION=pgsql\nDB_DATABASE=existing_db\n");

    Livewire::test(WizardStep::class, ['step' => 'environment'])
        ->assertSet('data.app_name', 'Existing App')
        ->assertSet('data.database_driver', 'pgsql')
        ->assertSet('data.database_name', 'existing_db');
});

it('validates required fields via the core rules (none declared in webui)', function (): void {
    Livewire::test(WizardStep::class, ['step' => 'environment'])
        ->set('data.database_driver', 'sqlite')
        ->set('data.app_name', '')
        ->set('data.database_name', '')
        ->call('save')
        ->assertHasErrors(['data.app_name', 'data.database_name']);
});
