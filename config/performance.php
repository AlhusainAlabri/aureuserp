<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slow Query Logging
    |--------------------------------------------------------------------------
    |
    | Enable while manually testing pages in local/staging. Each HTTP request
    | is written to storage/logs/performance/ as JSONL for later review via
    | `php artisan performance:report`.
    |
    */

    'slow_query_log' => [
        'enabled' => env('SLOW_QUERY_LOG_ENABLED', false),

        'threshold_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 100),

        'log_all_requests' => env('SLOW_QUERY_LOG_ALL_REQUESTS', true),

        'heavy_query_count' => (int) env('SLOW_QUERY_HEAVY_QUERY_COUNT', 75),

        'directory' => storage_path('logs/performance'),

        'retention_days' => (int) env('SLOW_QUERY_RETENTION_DAYS', 14),
    ],

];
