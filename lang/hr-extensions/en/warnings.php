<?php

return [
    'navigation'        => 'My Warnings',
    'empty_heading'     => 'No warnings',
    'empty_description' => 'You have no disciplinary notices on record.',
    'fields'            => [
        'type'            => 'Warning Type',
        'subject'         => 'Subject',
        'issued_at'       => 'Warning Date',
        'acknowledged'    => 'Acknowledged',
        'signed_document' => 'Signed Document',
        'notes'           => 'Notes',
    ],
    'actions' => [
        'acknowledge' => 'Acknowledge Warning',
    ],
    'notifications' => [
        'acknowledged' => 'Warning acknowledged successfully.',
    ],
    'mail' => [
        'acknowledged_subject' => 'Warning acknowledged by :employee',
        'acknowledged_heading' => 'Employee Warning Acknowledged',
        'acknowledged_body'    => ':employee acknowledged the warning ":subject" issued on :date.',
        'thanks'               => 'This is an automated notification for HR records.',
    ],
];
