<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Services\Boot\BootReport;

it('boots with no degraded package builders', function (): void {
    $report = app(BootReport::class);
    expect($report->isHealthy())->toBeTrue(
        'package booted degraded: '.json_encode($report->degraded()),
    );
});
