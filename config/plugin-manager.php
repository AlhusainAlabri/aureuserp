<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hide Plugins UI
    |--------------------------------------------------------------------------
    |
    | When enabled, the Filament plugin manager page (/admin/plugins) is hidden
    | from navigation and blocked for all users. Manage plugins via SSH instead
    | (e.g. php artisan plugins:install).
    |
    */

    'hide_ui' => (bool) env('HIDE_PLUGINS_UI', false),

];
