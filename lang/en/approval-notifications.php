<?php

return [
    'actions' => [
        'view' => 'View record',
    ],

    'fallback' => [
        'record' => 'Record',
        'step'   => 'Approval step',
    ],

    'mail' => [
        'footer' => 'Thank you for using :app.',
    ],

    'approvals' => [
        'requested' => [
            'title' => 'Approval requested: :step',
            'body'  => 'You have a pending approval for :record at step :step.',
        ],
        'sla_warning' => [
            'title' => 'Approval SLA warning: :step',
            'body'  => 'The approval for :record at step :step is approaching its deadline (:deadline).',
        ],
        'escalated' => [
            'title' => 'Approval escalated',
            'body'  => 'The approval for :record has been escalated and requires your attention.',
        ],
        'rejected' => [
            'title' => 'Approval rejected',
            'body'  => 'Your approval request for :record was rejected.',
        ],
        'completed' => [
            'title' => 'Approval completed',
            'body'  => 'Your approval request for :record has been fully approved.',
        ],
    ],
];
