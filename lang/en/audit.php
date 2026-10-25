<?php

return [
    'navigation' => [
        'group' => 'System',
        'label' => 'Audit Log',
    ],

    'title' => 'Audit Log',

    'entry_is_read_only' => 'Audit entries cannot be edited or deleted.',

    'events' => [
        'created'             => 'Created :model',
        'updated'             => 'Updated :model',
        'deleted'             => 'Deleted :model',
        'soft_deleted'        => 'Archived :model',
        'hard_deleted'        => 'Permanently deleted :model',
        'restored'            => 'Restored :model',
        'login'               => 'Signed in',
        'login_failed'        => 'Failed sign-in attempt',
        'lock_override'       => 'Posted in a locked period',
        'lock_date_changed'   => 'Accounting lock date changed',
        'permissions_changed' => 'Permissions changed',
    ],
];
