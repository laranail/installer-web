<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Tests;

use Livewire\LivewireServiceProvider;
use Simtabi\Laranail\Package\Tools\Testing\IsolatedTestCase;
use Simtabi\Laranail\Installer\Web\Providers\InstallerWebServiceProvider;
use Simtabi\Laranail\Installer\Headless\Providers\InstallerServiceProvider;

abstract class TestCase extends IsolatedTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            InstallerServiceProvider::class,
            InstallerWebServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');

        // A host view path holding a copy published under the hyphen view
        // namespace, so NamingConventionTest can prove the slash namespace still
        // finds it.
        $app['config']->set('view.paths', [...(array) $app['config']->get('view.paths', []), __DIR__ . '/fixtures/views']);
    }
}
