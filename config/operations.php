<?php

return [

    'cache' => [
        'latest_snapshot'     => 'operations:health:latest',
        'scheduler_heartbeat' => 'operations:scheduler:last_heartbeat',
        'ttl_seconds'         => 300,
    ],

    'backup' => [
        'path_prefix' => 'backups',
        'database'    => [
            'mysqldump_binary' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
        ],
        'files' => [
            'include' => [
                'private',
            ],
            'exclude_patterns' => [
                'backups',
                'framework/cache',
                'framework/sessions',
                'framework/views',
                'logs',
            ],
        ],
        'never_include' => [
            '.env',
            'vendor',
            'node_modules',
        ],
    ],

    'health' => [
        'slow_database_ms' => (int) env('OPERATIONS_SLOW_DATABASE_MS', 500),
        'error_log_lines'  => 50,
    ],

    'public_health' => [
        'token' => env('HEALTH_CHECK_TOKEN'),
    ],

];
