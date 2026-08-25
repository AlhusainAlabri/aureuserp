<?php

return [
    'navigation' => [
        'title' => 'Email Logs',
        'group' => 'Settings',
    ],

    'status' => [
        'queued' => 'Queued',
        'sent'   => 'Sent',
        'failed' => 'Failed',
    ],

    'table' => [
        'columns' => [
            'recipient_email' => 'Recipient email',
            'recipient_name'  => 'Recipient name',
            'subject'         => 'Subject',
            'status'          => 'Status',
            'mail_class'      => 'Mail class',
            'sender'          => 'Sender',
            'template'        => 'Template',
            'sent_at'         => 'Sent at',
            'created_at'      => 'Created at',
            'error_message'   => 'Error message',
            'variables'       => 'Variables',
        ],
        'filters' => [
            'status' => 'Status',
        ],
    ],

    'infolist' => [
        'sections' => [
            'details' => 'Email details',
        ],
    ],
];
