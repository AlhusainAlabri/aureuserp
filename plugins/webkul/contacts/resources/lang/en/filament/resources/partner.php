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
            'title' => ':name addresses',
        ],
        'manage-contacts' => [
            'title' => ':name contacts',
        ],
    ],

    'global-search' => [
        'project-manager' => 'Project Manager',
        'customer'        => 'Customer',
    ],
];
