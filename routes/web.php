<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Installer\Headless\Support\InstallationState;
use Simtabi\Laranail\Installer\Web\Http\Controllers\GateController;
use Simtabi\Laranail\Installer\Web\Http\Controllers\SetupController;
use Simtabi\Laranail\Installer\Web\Http\Controllers\WizardController;

// Hardened auto-disable: once installed, don't register the installer routes at all
// (they 404 — stronger than a redirect, and nothing remains to probe/re-run). The
// install-once redirect guard still covers the brief installing→installed window.
if ((bool) config('installer.security.disable_after_install', true) && app(InstallationState::class)->isInstalled()) {
    return;
}

$base = (array) config('installer-web.middleware', ['web']);
$prefix = (string) config('installer-web.prefix', 'install');

// Token gate — registered before the wildcard `/{step}` route so `/install/gate`
// never resolves as a step. Not behind `installer.token` (it IS the entry point);
// access policy (IP/host/window/HTTPS) + headers still apply, with a strict limiter.
Route::middleware(array_merge(['installer.stores'], $base, ['installer.headers', 'installer.guard', 'installer.security', 'throttle:installer-gate']))
    ->prefix($prefix)
    ->name('installer-web.')
    ->group(function (): void {
        Route::get('gate', [GateController::class, 'show'])->name('gate');
        Route::post('gate', [GateController::class, 'store'])->name('gate.store');

        // No-SSH security setup (set the gate password / lock-to-IP, written to .env).
        Route::get('setup', [SetupController::class, 'show'])->name('setup');
        Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    });

// Wizard. Full stack: security headers → install-once guard → access policy →
// token gate → throttle.
Route::middleware(array_merge(['installer.stores'], $base, ['installer.headers', 'installer.guard', 'installer.security', 'installer.token', 'throttle:installer']))
    ->prefix($prefix)
    ->name('installer-web.')
    ->group(function (): void {
        Route::get('/', [WizardController::class, 'index'])->name('index');
        Route::get('/{step}', [WizardController::class, 'show'])->name('show');
        Route::post('/{step}', [WizardController::class, 'store'])->name('store');

        // Per-product pipelines, under a distinct `p/{product}` segment so they never
        // collide with the default `/{step}` routes above.
        Route::prefix('p/{product}')->name('product.')->group(function (): void {
            Route::get('/', [WizardController::class, 'productIndex'])->name('index');
            Route::get('/{step}', [WizardController::class, 'productShow'])->name('show');
            Route::post('/{step}', [WizardController::class, 'productStore'])->name('store');
        });
    });
