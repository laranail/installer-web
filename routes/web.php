<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Installer\Web\Http\Controllers\WizardController;

Route::middleware(array_merge((array) config('installer-web.middleware', ['web']), ['installer.guard', 'throttle:60,1']))
    ->prefix((string) config('installer-web.prefix', 'install'))
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
