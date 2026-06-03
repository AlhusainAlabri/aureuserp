<?php

return [
    'navigation' => 'Training',
    'types'      => [
        'internal'      => 'Internal',
        'external'      => 'External',
        'online'        => 'Online',
        'workshop'      => 'Workshop',
        'conference'    => 'Conference',
        'certification' => 'Certification',
    ],
    'statuses' => [
        'planned'     => 'Planned',
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ],
    'fields' => [
        'course_name'             => 'Course name',
        'provider'                => 'Provider',
        'type'                    => 'Type',
        'status'                  => 'Status',
        'start_date'              => 'Start date',
        'end_date'                => 'End date',
        'duration_hours'          => 'Duration (hours)',
        'cost'                    => 'Cost',
        'certificate'             => 'Certificate',
        'certificate_expiry_date' => 'Certificate expiry',
        'notes'                   => 'Notes',
    ],
    'actions' => [
        'view_certificate'     => 'View certificate',
        'download_certificate' => 'Download certificate',
        'close'                => 'Close',
        'add'                  => 'Add training',
    ],
    'empty_heading'     => 'No training records',
    'empty_description' => 'Add courses, certifications, and training history for this employee.',
    'duration_unknown'  => 'Not specified',
    'duration_hours'    => ':count hours',
    'duration_days'     => ':count days',
    'notifications'     => [
        'expiring' => [
            'title' => 'Training certificate expiring soon',
            'body'  => ':employee — :course expires :date',
        ],
    ],
];
