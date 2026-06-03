<?php

return [
    'correspondence'      => 'Correspondence',
    'correspondences'     => 'Correspondences',
    'outgoing'            => 'Outgoing',
    'incoming'            => 'Incoming',
    'reference_number'    => 'Reference Number',
    'direction'           => 'Direction',
    'subject'             => 'Subject',
    'body'                => 'Body',
    'sender_name'         => 'Sender Name',
    'sender_entity'       => 'Sender Entity',
    'external_entity'     => 'External Entity',
    'from_department'     => 'From Department',
    'to_department'       => 'To Department',
    'to_user'             => 'To Employee',
    'recipient'           => 'Recipient',
    'to_external_email'   => 'External Email',
    'received_at'         => 'Received Date',
    'sent_at'             => 'Sent At',
    'due_date'            => 'Due Date',
    'project'             => 'Project',
    'meeting'             => 'Meeting',
    'purchase_request'    => 'Purchase Request',
    'reply'               => 'Reply',
    'reply_subject'       => 'Reply to: :subject',
    'thread'              => 'Thread',
    'followers'           => 'Followers',
    'send_correspondence' => 'Send Correspondence',
    'email_sent'          => 'Email sent',
    'email_failed'        => 'Email sending failed. Please try again later.',
    'overdue'             => 'Overdue',
    'attachments'         => 'Attachments',
    'details'             => 'Details',
    'date'                => 'Date',
    'creator'             => 'Creator',
    'user'                => 'User',
    'file'                => 'File',
    'file_name'           => 'File Name',
    'file_size'           => 'File Size',
    'mime_type'           => 'MIME Type',
    'yes'                 => 'Yes',
    'no'                  => 'No',

    'navigation' => [
        'group'     => 'Correspondence',
        'dashboard' => 'Correspondence Dashboard',
    ],

    'directions' => [
        'outgoing' => 'Outgoing',
        'incoming' => 'Incoming',
    ],

    'type' => [
        'label'    => 'Type',
        'official' => 'Official',
        'internal' => 'Internal',
        'external' => 'External',
    ],

    'types' => [
        'official' => 'Official',
        'internal' => 'Internal',
        'external' => 'External',
    ],

    'priority' => [
        'label'        => 'Priority',
        'normal'       => 'Normal',
        'urgent'       => 'Urgent',
        'confidential' => 'Confidential',
    ],

    'priorities' => [
        'normal'       => 'Normal',
        'urgent'       => 'Urgent',
        'confidential' => 'Confidential',
    ],

    'status' => [
        'label'            => 'Status',
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'sent'             => 'Sent',
        'received'         => 'Received',
        'archived'         => 'Archived',
    ],

    'statuses' => [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'sent'             => 'Sent',
        'received'         => 'Received',
        'archived'         => 'Archived',
    ],

    'form' => [
        'sections' => [
            'type'    => 'Correspondence Type',
            'parties' => 'Parties',
            'content' => 'Content',
            'links'   => 'Links',
        ],
    ],

    'filters' => [
        'from'           => 'From',
        'until'          => 'Until',
        'received_from'  => 'Received From',
        'received_until' => 'Received Until',
        'due_from'       => 'Due From',
        'due_until'      => 'Due Until',
    ],

    'actions' => [
        'view'       => 'View',
        'archive'    => 'Archive',
        'unarchive'  => 'Unarchive',
        'download'   => 'Download',
        'export_pdf' => 'Export PDF',
        'create_task'=> 'Create Follow-up Task',
    ],

    'departments' => [
        'navigation'            => 'Departments',
        'model'                 => 'Department',
        'plural'                => 'Departments',
        'section'               => 'Department Details',
        'name'                  => 'Name',
        'code'                  => 'Code',
        'manager'               => 'Manager',
        'company'               => 'Company',
        'employees_department'  => 'Linked HR Department',
    ],

    'tasks' => [
        'navigation'  => 'Follow-up Tasks',
        'create'      => 'Create Task',
        'title'       => 'Task Title',
        'deadline'    => 'Deadline',
        'assignee'    => 'Assignee',
        'created'     => 'Follow-up task created',
        'empty'       => 'No follow-up tasks linked to correspondence yet.',
    ],

    'tabs' => [
        'archived' => 'Archived',
    ],

    'relations' => [
        'approvals'                 => 'Approvals Log',
        'project_correspondences'   => 'Linked Correspondence',
        'meeting_correspondences'   => 'Linked Correspondence',
    ],

    'approvals' => [
        'default_flow' => 'Default Outgoing Correspondence Approval',
        'steps'        => [
            'department_manager' => 'Department Manager',
            'admin_manager'      => 'Admin Manager',
        ],
    ],

    'notify' => [
        'submitted' => [
            'title' => 'Outgoing correspondence awaiting your approval',
            'body'  => ':reference — :subject',
        ],
        'approved' => [
            'title' => 'Correspondence approved',
            'body'  => ':reference is ready to send',
        ],
        'rejected' => [
            'title'     => 'Correspondence rejected',
            'body'      => ':reference — :reason',
            'no_reason' => 'No reason provided',
        ],
        'sent' => [
            'title' => 'Correspondence sent',
            'body'  => ':reference was sent to :target',
        ],
        'received' => [
            'title' => 'New incoming correspondence',
            'body'  => 'From: :sender — :subject',
        ],
        'overdue' => [
            'title' => 'Overdue correspondence',
            'body'  => ':reference passed the deadline :date',
        ],
        'reply' => [
            'title' => 'New reply to your correspondence',
            'body'  => ':reference — :subject',
        ],
    ],

    'empty' => [
        'no_records'             => 'No correspondence',
        'no_records_description' => 'Correspondence will appear here when available.',
    ],

    'dashboard' => [
        'stats' => [
            'outgoing_month'   => 'Outgoing This Month',
            'incoming_month'   => 'Incoming This Month',
            'pending_approval' => 'Pending Approval',
            'overdue'          => 'Overdue Correspondence',
            'my_approvals'     => 'My Pending Approvals',
        ],
        'sections' => [
            'incoming'         => 'Latest Incoming',
            'pending_outgoing' => 'Pending Outgoing',
            'my_approvals'     => 'My Pending Approvals',
            'urgent'           => 'Urgent Correspondence',
        ],
    ],

    'pdf' => [
        'official_title' => 'Official Correspondence',
        'internal_title' => 'Internal Correspondence',
        'signature'      => 'Sender Signature / Entity Stamp',
        'created_by'     => 'Created by :user on :date',
    ],

    'exceptions' => [
        'send_before_approval' => 'Correspondence cannot be sent before approvals are complete.',
        'task_create_failed'   => 'Unable to create the follow-up task. Check that a task stage exists.',
    ],

    'commands' => [
        'overdue_complete' => 'Overdue correspondence notifications sent.',
    ],

    'install' => [
        'success' => 'Correspondence plugin installed successfully.',
    ],
];
