<?php

return [
    'section_title'    => 'Request details',
    'internal_section' => 'Internal request details',
    'fields'           => [
        'request_type'      => 'Request type',
        'urgency'           => 'Urgency',
        'justification'     => 'Business justification',
        'item_description'  => 'Item or service description',
        'expected_delivery' => 'Expected delivery',
        'quotation'         => 'Quotation or invoice',
        'payment_voucher'   => 'Payment voucher',
        'vendor_hint'       => 'Optional — shop or company name for this purchase',
    ],
    'payment' => [
        'section_title'    => 'Payment tracking',
        'amount_paid'      => 'Amount paid',
        'amount_remaining' => 'Amount remaining',
        'record_payment'   => 'Record payment',
    ],
    'notifications' => [
        'voucher_required' => [
            'title' => 'Payment voucher required',
            'body'  => 'Upload a payment voucher before approving this disbursement.',
        ],
    ],
    'types' => [
        'standard_purchase' => 'Standard purchase',
        'device_request'    => 'Device request',
        'technical_support' => 'Technical support',
        'office_supplies'   => 'Office supplies',
        'maintenance'       => 'Maintenance',
        'other'             => 'Other',
    ],
    'urgency' => [
        'low'      => 'Low',
        'normal'   => 'Normal',
        'high'     => 'High',
        'critical' => 'Critical',
    ],
    'navigation' => [
        'my_requests'       => 'My Requests',
        'internal_requests' => 'Internal Requests',
    ],
    'actions' => [
        'new_request' => 'New request',
    ],
    'create_title'   => 'Create :type',
    'lines'          => [
        'title'       => 'Line items',
        'add_line'    => 'Add line',
        'description' => 'Description',
        'quantity'    => 'Quantity',
        'unit_price'  => 'Unit price (OMR)',
    ],
    'tabs' => [
        'all'               => 'All',
        'standard_purchase' => 'Standard purchase',
        'device_request'    => 'Device requests',
        'technical_support' => 'Tech support',
        'office_supplies'   => 'Office supplies',
        'maintenance'       => 'Maintenance',
    ],
    'currency' => [
        'format' => 'OMR :amount',
    ],
    'email' => [
        'receipt_reminder' => [
            'subject'  => 'Receipt required for :reference',
            'greeting' => 'Hello :name,',
            'body'     => 'Please upload the purchase receipt or invoice for order :reference.',
            'footer'   => 'This is an automated reminder from the purchase system.',
        ],
    ],
];
