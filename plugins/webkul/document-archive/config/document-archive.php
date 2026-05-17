<?php

return [
    'max_file_size_mb' => 50,

    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'mp4', 'zip', 'txt', 'csv',
    ],

    'storage_disk' => 'local',

    'share_link_base_url' => env('APP_URL', 'http://localhost').'/share/',

    'password_lock_minutes' => 15,

    'password_max_attempts' => 5,

    'session_unlock_minutes' => 30,
];
