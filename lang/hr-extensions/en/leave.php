<?php

return [
    'substitute_section'  => 'Leave substitute',
    'substitute_employee' => 'Substitute employee',
    'substitute_helper'   => 'Select a colleague who will cover your duties.',
    'handover_notes'      => 'Handover notes',
    'handover_helper'     => 'Brief instructions for the substitute.',
    'substitute_pending'  => 'Pending acceptance',
    'substitute_accepted' => 'Accepted',
    'substitute_declined' => 'Declined',
    'actions'             => [
        'accept_substitute'  => 'Accept',
        'decline_substitute' => 'Decline',
        'view_leave'         => 'View leave request',
    ],
    'infolist' => [
        'substitute_status' => 'Substitute status',
    ],
    'notifications'       => [
        'substitute_request' => [
            'title' => 'You are requested as a substitute',
            'body'  => ':employee requests you cover their duties from :start to :end',
        ],
        'substitute_accepted' => [
            'title' => 'Substitute accepted',
            'body'  => ':substitute accepted to cover your leave.',
        ],
        'substitute_declined' => [
            'title' => 'Substitute declined',
            'body'  => ':substitute declined to cover your leave.',
        ],
        'pending_approvals_title' => 'Pending leave approvals',
        'pending_approvals_body'  => 'You have :count leave request(s) awaiting approval.',
    ],
    'covering_for' => 'You are covering for :employee from :start to :end',
];
