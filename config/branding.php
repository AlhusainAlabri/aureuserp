<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Branding Fallback Name
    |--------------------------------------------------------------------------
    |
    | Used when no active company name is available. Typically matches APP_NAME
    | from the environment and is not overwritten at runtime.
    |
    */

    'fallback' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Bundled Default Logos
    |--------------------------------------------------------------------------
    |
    | Paths relative to public/, used until a logo is uploaded under
    | Settings > Branding. Point them at a customer's own files with
    | BRANDING_DEFAULT_LOGO and BRANDING_DEFAULT_LOGO_COMPACT.
    |
    */

    'default_logo' => env('BRANDING_DEFAULT_LOGO', 'images/brand/default-logo.png'),

    'default_logo_compact' => env('BRANDING_DEFAULT_LOGO_COMPACT', 'images/brand/default-mark.png'),

    // Optional dark-mode variants. Leave empty to use the bundled white logos (or the light file
    // when the light logo above is a site's own file).
    'default_logo_dark' => env('BRANDING_DEFAULT_LOGO_DARK'),

    'default_logo_compact_dark' => env('BRANDING_DEFAULT_LOGO_COMPACT_DARK'),

    // Browser tab icon until one is uploaded under Settings > Branding (path relative to public/).
    'default_favicon' => env('BRANDING_DEFAULT_FAVICON', 'images/brand/favicon.ico'),

    /*
    |--------------------------------------------------------------------------
    | Primary Color Presets
    |--------------------------------------------------------------------------
    |
    | Ready-made choices on Settings > Branding. The key is the translation key
    | under branding-settings.presets; every colour keeps white text readable
    | (a test checks the contrast). The first entry is the built-in default and
    | is saved as "no custom colour".
    |
    */

    'color_presets' => [
        'green'  => '#16a34a',
        'teal'   => '#0f766e',
        'blue'   => '#1d4ed8',
        'indigo' => '#4338ca',
        'purple' => '#7e22ce',
        'rose'   => '#be123c',
        'orange' => '#c2410c',
        'brown'  => '#92400e',
        'slate'  => '#334155',
    ],

];
