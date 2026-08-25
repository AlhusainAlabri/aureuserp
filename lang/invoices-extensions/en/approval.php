<?php

return [
    'flow_name'        => 'Invoice Approval Flow',
    'flow_description' => '3-step sequential approval flow for customer invoices.',
    'steps'            => [
        'manager'          => 'Department Manager Review',
        'finance_manager'  => 'Finance Manager Review',
        'general_manager'  => 'General Manager Approval',
    ],
];
