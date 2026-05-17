<?php

return [
    'navigation' => [
        'title' => 'My Voice',
    ],

    'form' => [
        'section' => [
            'title'       => 'Share your thoughts',
            'description' => 'Your voice matters. All submissions are reviewed by management.',
        ],
        'info-note'   => 'Your submission will be reviewed by HR and Management.',
        'fields'      => [
            'type'                => 'Type',
            'complaint'           => '💬 Complaint',
            'suggestion'          => '💡 Suggestion',
            'inquiry'             => '❓ Inquiry',
            'feedback'            => '⭐ Feedback',
            'subject'             => 'Subject',
            'subject-placeholder' => 'Brief summary of your submission',
            'body'                => 'Details',
            'body-placeholder'    => 'Describe in detail...',
            'attachments'         => 'Attachments (max 3, 5MB each)',
        ],
        'submit' => 'Submit',
    ],

    'history' => [
        'title'       => 'My Previous Submissions',
        'empty'       => [
            'title'       => 'No submissions yet',
            'description' => 'You haven\'t submitted anything yet. Share your thoughts above.',
        ],
    ],

    'types' => [
        'complaint'  => 'Complaint',
        'suggestion' => 'Suggestion',
        'inquiry'    => 'Inquiry',
        'feedback'   => 'Feedback',
    ],

    'statuses' => [
        'open'         => 'Open',
        'under_review' => 'Under Review',
        'resolved'     => 'Resolved',
        'closed'       => 'Closed',
    ],

    'modal' => [
        'attachments'  => 'Attachments',
        'replies'      => 'Replies',
        'hr-team'      => 'HR Team',
        'close'        => 'Close',
        'timeline'     => [
            'title' => 'Status Timeline',
        ],
    ],

    'notifications' => [
        'no-employee' => [
            'title' => 'Employee record not found',
            'body'  => 'Please contact HR to link your user account.',
        ],
        'submitted' => [
            'title' => 'Submitted successfully!',
            'body'  => 'Your ticket number is :ticket',
        ],
    ],
];
