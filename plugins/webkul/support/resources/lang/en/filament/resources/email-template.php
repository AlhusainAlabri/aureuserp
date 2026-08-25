<?php

return [
    'navigation' => [
        'title' => 'Email Templates',
        'group' => 'Settings',
    ],

    'form' => [
        'sections' => [
            'general' => 'Template details',
        ],
        'fields' => [
            'code'        => 'Code',
            'name'        => 'Name',
            'subject'     => 'Subject',
            'sender_name' => 'Sender name',
            'layout'      => 'Layout',
            'is_active'   => 'Active',
            'description' => 'Description',
            'content'     => 'Content',
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'code'       => 'Code',
            'subject'    => 'Subject',
            'is_active'  => 'Active',
            'updated_at' => 'Updated at',
        ],
        'filters' => [
            'is_active' => 'Active',
        ],
    ],

    'pages' => [
        'list' => [
            'create' => 'New template',
        ],
    ],

    'notifications' => [
        'created' => [
            'title' => 'Template created',
            'body'  => 'The email template has been created successfully.',
        ],
        'updated' => [
            'title' => 'Template updated',
            'body'  => 'The email template has been updated successfully.',
        ],
        'deleted' => [
            'title' => 'Template deleted',
            'body'  => 'The email template has been deleted successfully.',
        ],
    ],
];
