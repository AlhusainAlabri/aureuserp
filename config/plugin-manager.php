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

];
