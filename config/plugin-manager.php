<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hide Plugins UI
    |--------------------------------------------------------------------------
    |
    | When enabled, /admin/plugins is removed from the admin menu and the
    | dashboard, and direct visits return 404. Add ?access=YOUR_SECRET to
    | that URL to open it for the current login. Manage plugins from SSH
    | with php artisan plugins:install when the secret is not set.
    |
    */

    'hide_ui' => filter_var(env('HIDE_PLUGINS_UI', true), FILTER_VALIDATE_BOOLEAN),

    'access_key' => (string) env('PLUGINS_UI_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Plugins Skipped By Default
    |--------------------------------------------------------------------------
    |
    | plugins:install --all (and the non-interactive install) leave these out,
    | together with any plugin that depends on them. Install one on purpose with
    | php artisan plugins:install --only=website
    |
    */

    'skip_by_default' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PLUGINS_SKIP_BY_DEFAULT', 'website,blogs')),
    ))),

];
