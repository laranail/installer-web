<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Route prefix
    |--------------------------------------------------------------------------
    |
    | URL prefix the install wizard is served under (e.g. /install).
    |
    */

    'prefix' => env('INSTALLER_WEB_PREFIX', 'install'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the wizard routes. The install-once guard is always
    | appended by the package.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The Blade layout the wizard extends. Override to fully re-skin the wizard
    | (or use `InstallerUi::layout(...)` at runtime). null = the shipped layout.
    |
    */

    'layout' => null,

    /*
    |--------------------------------------------------------------------------
    | Security views
    |--------------------------------------------------------------------------
    |
    | Views rendered by the access layer: the generic access-denied page (IP/host/
    | window/HTTPS failures) and the token gate form. Override to re-skin them.
    |
    */

    'denied_view' => 'installer-web::denied',
    'gate_view' => 'installer-web::gate',
    'setup_view' => 'installer-web::setup',

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Lightweight theming applied by the shipped layout without overriding views:
    | the heading title, an optional logo URL, and the primary accent colour
    | (any CSS colour). For deeper changes, publish the views or register slots
    | via `InstallerUi::section(...)`.
    |
    */

    'branding' => [
        'title' => null, // null → "<app name> installer"
        'logo' => null,  // URL to a logo image, shown above the title
        'theme' => env('INSTALLER_WEB_THEME', '#4f46e5'), // accent colour
    ],

];
