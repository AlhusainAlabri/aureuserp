<?php

return [
    'navigation' => [
        'title' => 'Contacts',
        'group' => 'Contact',
    ],

    'model' => [
        'single' => 'Contact',
    ],

    'relations' => [
        'contacts'  => 'Contacts',
        'addresses' => 'Addresses',
    ],

    'pages' => [
        'manage-addresses' => [
            'title'        => ':name addresses',
            'create-modal' => [
                'heading' => 'Add address',
            ],
            'empty' => [
                'heading'     => 'No addresses',
                'description' => 'Add an address to get started.',
            ],
        ],
        'manage-contacts' => [
            'title' => ':name contacts',
            'empty' => [
                'heading'     => 'No sub-contacts',
                'description' => 'Add a contact to get started.',
            ],
        ],
    ],

    'global-search' => [
        'project-manager' => 'Project Manager',
        'customer'        => 'Customer',
    ],
];
