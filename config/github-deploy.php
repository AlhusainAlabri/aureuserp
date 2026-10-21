<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GitHub pull deploy
    |--------------------------------------------------------------------------
    |
    | Off until the server is prepared. The admin page and the scheduled
    | update check do nothing while this is false.
    |
    | Enable only on the server that should receive releases. Do not enable
    | it on a developer checkout.
    |
    */

    'enabled' => (bool) env('GITHUB_DEPLOY_ENABLED', false),

    'remote' => env('GITHUB_DEPLOY_REMOTE', 'origin'),

    'branch' => env('GITHUB_DEPLOY_BRANCH', ''),

    /*
    | Leave empty to use the CLI php binary. Set a full path when the web
    | server's PATH cannot see php, for example /usr/bin/php.
    */
    'php_binary' => env('GITHUB_DEPLOY_PHP', ''),

    'composer_binary' => env('GITHUB_DEPLOY_COMPOSER', 'composer'),

    'maintenance_secret' => env('GITHUB_DEPLOY_MAINTENANCE_SECRET'),

    'backup_before_deploy' => (bool) env('GITHUB_DEPLOY_BACKUP', true),

    'run_shield' => (bool) env('GITHUB_DEPLOY_SHIELD', true),

    'optimize' => (bool) env('GITHUB_DEPLOY_OPTIMIZE', true),

    /*
    | Filament component cache is for APP_ENV=production only.
    */
    'cache_filament' => (bool) env('GITHUB_DEPLOY_FILAMENT_CACHE', false),

    'directory' => storage_path('app/private/deploy'),

    /*
    | Tests set this true so the deployer can run against a fake process
    | runner. It is not an environment variable.
    */
    'allow_in_tests' => false,

];
