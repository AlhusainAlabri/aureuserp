<?php

return [
    'navigation'        => 'Employment Contracts',
    'empty_heading'     => 'No contracts recorded',
    'empty_description' => 'Add employment contracts with start/end dates and attachments.',
    'fields'            => [
        'contract_type'      => 'Contract Type',
        'start_date'         => 'Contract Start Date',
        'end_date'           => 'Contract End Date',
        'renewal_date'       => 'Renewal Date',
        'first_joining_date' => 'First Joining Date',
        'wage'               => 'Salary / Wage',
        'contract_file'      => 'Employment Contract File',
        'notes'              => 'Notes',
        'is_active'          => 'Active Contract',
    ],
    'types' => [
        'permanent'  => 'Permanent',
        'fixed_term' => 'Fixed Term',
        'temporary'  => 'Temporary',
        'probation'  => 'Probation',
    ],
    'actions' => [
        'add'       => 'Add Contract',
        'view_file' => 'View Contract',
    ],
    'notifications' => [
        'expiring_title' => 'Contract expiring soon',
        'expiring_body'  => ':employee\'s contract expires on :end_date.',
    ],
];
