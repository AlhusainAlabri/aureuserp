<?php

return [
    'navigation' => [
        'title' => 'Department Report',
        'group' => 'Purchases',
    ],

    'form' => [
        'fields' => [
            'month'           => 'Month',
            'year'            => 'Year',
            'department'      => 'Department',
            'all-departments' => 'All Departments',
        ],
    ],

    'cards' => [
        'total-purchases'   => 'Total Purchases This Month',
        'total-amount'      => 'Total Amount',
        'missing-receipts'  => 'Missing Receipts',
    ],

    'table' => [
        'columns' => [
            'department'         => 'Department',
            'purchases-count'    => 'Purchase Count',
            'total-amount'       => 'Total Amount',
            'receipts-uploaded'  => 'Receipts Uploaded',
            'receipts-missing'   => 'Missing Receipts',
        ],
    ],

    'actions' => [
        'export-csv' => 'Export CSV',
    ],
];
