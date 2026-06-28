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
