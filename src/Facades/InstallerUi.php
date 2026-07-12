<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Installer\Headless\Contracts\Step;
use Simtabi\Laranail\Installer\Web\InstallerUi as InstallerUiManager;

/**
 * @method static InstallerUiManager view(string $step, string $view)
 * @method static InstallerUiManager component(string $step, string $component)
 * @method static InstallerUiManager layout(string $view)
 * @method static InstallerUiManager section(string $name, string|Closure $content)
 * @method static InstallerUiManager fieldType(string $type, string $view)
 * @method static InstallerUiManager step(Step $step)
 * @method static InstallerUiManager before(string $key, Step $step)
 * @method static InstallerUiManager after(string $key, Step $step)
 * @method static InstallerUiManager removeStep(string $key)
 *
 * @see InstallerUiManager
 */
final class InstallerUi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return InstallerUiManager::class;
    }
}
