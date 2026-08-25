<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Destructive Artisan Commands
    |--------------------------------------------------------------------------
    |
    | When false, commands that wipe or reseed the database are blocked unless
    | the app is running tests against a dedicated test database (_test suffix
    | or SQLite :memory:). Set to true only for intentional recovery (auth.md).
    |
    */

    'allow_destructive_commands' => env('ALLOW_DESTRUCTIVE_ARTISAN_COMMANDS', false),

    /*
    |--------------------------------------------------------------------------
    | Blocked Commands
    |--------------------------------------------------------------------------
    |
    | Exact Artisan command names that must never run against live databases.
    |
    */

    'blocked_commands' => [
        'migrate:fresh',
        'migrate:reset',
        'migrate:refresh',
        'db:wipe',
        'db:seed',
    ],

];
