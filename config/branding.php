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

    'default_logo' => env('BRANDING_DEFAULT_LOGO', 'images/brand/default-logo.svg'),

    'default_logo_compact' => env('BRANDING_DEFAULT_LOGO_COMPACT', 'images/brand/default-mark.svg'),

];
