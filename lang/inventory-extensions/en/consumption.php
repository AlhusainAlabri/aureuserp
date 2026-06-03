<?php

return [
    'title'            => 'Record consumption',
    'description'      => 'Quickly record material consumption via internal transfer.',
    'bulk_link'        => 'Multi-line internal transfer',
    'bulk_description' => 'Use internal transfers for bulk or multi-product consumption.',
    'fields'           => [
        'product'         => 'Product',
        'quantity'        => 'Quantity',
        'department'      => 'Department',
        'project'         => 'Project',
        'purpose'         => 'Purpose',
        'source_location' => 'Source location',
    ],
    'notifications' => [
        'recorded'      => 'Consumption recorded',
        'recorded_body' => 'Internal transfer :name created.',
        'failed'        => 'Could not complete transfer',
    ],
    'plugin_missing'    => 'Inventory plugin is not installed.',
    'no_operation_type' => 'No internal transfer operation type configured.',
    'no_destination'    => 'No consumption location found.',
    'operation_name'    => 'Consumption: :product',
    'demo_purpose'      => 'Demo consumption for department supplies',
];
