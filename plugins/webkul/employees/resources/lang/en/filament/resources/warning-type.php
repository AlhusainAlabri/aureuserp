<?php

return [
    'navigation' => [
        'title' => 'Warning Types',
        'group' => 'Employees',
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
            'force-delete' => [
                'notification' => [
                    'title' => 'Warning type permanently deleted',
                    'body'  => 'The warning type has been permanently deleted.',
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
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'General Information',
                'entries' => [
                    'name'           => 'Name',
                    'description'    => 'Description',
                    'warnings-count' => 'Warnings Count',
                ],
            ],
        ],
    ],

    'pages' => [
        'list-warning-type' => [
            'header-actions' => [
                'create' => [
                    'label' => 'New Warning Type',
                ],
            ],
            'tabs' => [
                'archived' => 'Archived',
            ],
        ],
        'create-warning-type' => [
            'notification' => [
                'title' => 'Warning type created',
                'body'  => 'The warning type has been created successfully.',
            ],
        ],
        'edit-warning-type' => [
            'notification' => [
                'title' => 'Warning type updated',
                'body'  => 'The warning type has been updated successfully.',
            ],
            'header-actions' => [
                'delete' => [
                    'notification' => [
                        'title' => 'Warning type deleted',
                        'body'  => 'The warning type has been deleted successfully.',
                    ],
                ],
            ],
        ],
        'view-warning-type' => [
            'header-actions' => [
                'delete' => [
                    'notification' => [
                        'title' => 'Warning type deleted',
                        'body'  => 'The warning type has been deleted successfully.',
                    ],
                ],
            ],
        ],
    ],
];
