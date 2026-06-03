<?php

return [
    'navigation' => [
        'title' => 'Overview',
    ],

    'title' => 'Employee Overview',

    'banner' => [
        'inactive-title' => 'This employee is no longer active.',
        'departed-on'    => 'Departed on :date',
    ],

    'summary' => [
        'expired-docs'      => 'Expired Docs',
        'expiring-soon'     => 'Expiring Soon',
        'active-warnings'   => 'Active Warnings',
        'compliance-issues' => 'Compliance Issues',
    ],

    'info' => [
        'heading'          => 'Employee Information',
        'manager'          => 'Manager',
        'department'       => 'Department',
        'job-position'     => 'Job Position',
        'work-email'       => 'Work Email',
        'work-phone'       => 'Work Phone',
        'employment-type'  => 'Employment Type',
        'civil-id'         => 'Civil ID',
        'civil-id-expires' => 'Expires',
    ],

    'documents' => [
        'heading' => 'Document Alerts',
        'columns' => [
            'type'        => 'Type',
            'name'        => 'Document Name',
            'expiry-date' => 'Expiry Date',
            'status'      => 'Status',
        ],
    ],

    'compliance' => [
        'heading'      => 'Compliance Alerts',
        'visa-expire'  => 'Visa Expiry',
        'work-permit'  => 'Work Permit Expiry',
        'civil-id'     => 'Civil ID Expiry',
    ],

    'warnings' => [
        'heading'   => 'Unacknowledged Warnings',
        'issued-on' => 'Issued on :date',
    ],

    'status' => [
        'expired'          => 'Expired',
        'expires-in-days'  => 'In :days day(s)',
        'valid'            => 'Valid',
    ],

    'header-actions' => [
        'edit'           => 'Edit Employee',
        'add-document'   => 'Add Document',
        'issue-warning'  => 'Issue Warning',
    ],

    'all-clear' => [
        'title'       => 'All Clear',
        'description' => 'No compliance alerts, document issues, or active warnings for this employee.',
    ],
];
