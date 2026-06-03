<?php

return [
    'title' => 'Warning Types',

    'navigation' => [
        'title' => 'Warning Types',
        'group' => 'Employee',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General Information',
                'fields' => [
                    'name'        => 'Name',
                    'description' => 'Description',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'           => 'Name',
            'description'    => 'Description',
            'warnings-count' => 'Warnings Count',
        ],
        'filters' => [
            'name'        => 'Name',
            'description' => 'Description',
            'created-at'  => 'Created At',
            'updated-at'  => 'Updated At',
        ],
        'groups' => [
            'name'       => 'Name',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],
        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Warning type deleted',
                    'body'  => 'The warning type has been deleted successfully.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Warning type restored',
                    'body'  => 'The warning type has been restored successfully.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Warning types deleted',
                    'body'  => 'The warning types have been deleted successfully.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Warning types restored',
                    'body'  => 'The warning types have been restored successfully.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Warning types permanently deleted',
                    'body'  => 'The warning types have been permanently deleted.',
                ],
            ],
        ],
        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Warning type created',
                    'body'  => 'The warning type has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'entries' => [
                    'name'           => 'Name',
                    'description'    => 'Description',
                    'warnings-count' => 'Warnings Count',
                ],
            ],
        ],
    ],
];
