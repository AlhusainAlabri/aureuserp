<?php

return [
    'navigation' => [
        'title' => 'Submissions',
        'group' => 'Human Resources',
    ],

    'global-search' => [
        'type'     => 'Type',
        'status'   => 'Status',
        'employee' => 'Employee',
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

    'priorities' => [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
    ],

    'tabs' => [
        'all'          => 'All',
        'open'         => 'Open',
        'under_review' => 'Under Review',
        'resolved'     => 'Resolved',
        'closed'       => 'Closed',
    ],

    'table' => [
        'columns' => [
            'ticket-number' => 'Ticket',
            'type'          => 'Type',
            'subject'       => 'Subject',
            'submitter'     => 'Submitter',
            'department'    => 'Department',
            'status'        => 'Status',
            'priority'      => 'Priority',
            'replies'       => 'Replies',
            'created-at'    => 'Submitted',
        ],
        'filters' => [
            'type'       => 'Type',
            'status'     => 'Status',
            'priority'   => 'Priority',
            'department' => 'Department',
            'no-replies' => 'No replies yet',
        ],
        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Submission deleted',
                    'body'  => 'The submission has been deleted successfully.',
                ],
            ],
        ],
        'bulk-actions' => [
            'mark-under-review' => 'Mark Under Review',
            'mark-resolved'     => 'Mark as Resolved',
            'mark-closed'       => 'Mark as Closed',
            'delete'            => [
                'notification' => [
                    'title' => 'Submissions deleted',
                    'body'  => 'The submissions have been deleted successfully.',
                ],
            ],
        ],
    ],

    'form' => [
        'fields' => [
            'status'   => 'Status',
            'priority' => 'Priority',
        ],
    ],

    'infolist' => [
        'sections' => [
            'details' => [
                'title'   => 'Submission Details',
                'entries' => [
                    'ticket-number' => 'Ticket Number',
                    'type'          => 'Type',
                    'priority'      => 'Priority',
                    'subject'       => 'Subject',
                    'body'          => 'Body',
                    'submitter'     => 'Submitted By',
                    'department'    => 'Department',
                    'created-at'    => 'Submitted At',
                    'status'        => 'Status',
                ],
            ],
        ],
    ],

    'pages' => [
        'view-submission' => [
            'actions' => [
                'change-status'    => 'Change Status',
                'set-priority'     => 'Set Priority',
                'delete'           => 'Delete',
                'mark-under-review'=> 'Mark Under Review',
                'mark-resolved'    => 'Mark as Resolved',
                'close-ticket'     => 'Close Ticket',
            ],
            'notifications' => [
                'status-updated' => [
                    'title' => 'Status updated',
                    'body'  => 'The submission status has been updated.',
                ],
                'priority-updated' => [
                    'title' => 'Priority updated',
                    'body'  => 'The submission priority has been updated.',
                ],
                'reply-sent' => [
                    'title' => 'Reply sent',
                    'body'  => 'Your reply has been sent successfully.',
                ],
            ],
            'sections' => [
                'details'       => 'Submission Details',
                'replies'       => 'Reply Thread',
                'info'          => 'Information',
                'timeline'      => 'Status Timeline',
                'quick-actions' => 'Quick Actions',
            ],
            'attachments'         => 'Attachments',
            'internal-note-label' => 'Internal note — not visible to employee',
            'hr-team'             => 'HR Team',
            'no-replies'          => 'No replies yet.',
            'reply-placeholder'   => 'Type your reply...',
            'internal-toggle'     => 'Internal note (not visible to employee)',
            'send-reply'          => 'Send Reply',
        ],
    ],

    'notifications' => [
        'new-submission' => [
            'title' => 'New :type received',
            'body'  => 'Ticket :ticket — :subject',
        ],
        'reply' => [
            'title' => 'Response received for your submission',
            'body'  => 'Ticket :ticket — :subject',
        ],
        'resolved' => [
            'title' => 'Your submission has been resolved',
            'body'  => 'Ticket :ticket is marked as resolved.',
        ],
        'unresolved' => [
            'title' => ':count submissions still open',
            'body'  => 'Oldest: :ticket — :days days old',
        ],
    ],
];
