<?php

return [
    'install' => [
        'success' => 'Assets plugin installed successfully.',
    ],
    'all'        => 'All',
    'navigation' => [
        'group'  => 'Asset Management',
        'assets' => 'Assets',
    ],
    'models' => [
        'asset' => 'Asset',
    ],
    'statuses' => [
        'available'   => 'Available',
        'borrowed'    => 'Borrowed',
        'maintenance' => 'Maintenance',
        'retired'     => 'Retired',
    ],
    'borrowing_statuses' => [
        'pending'           => 'Pending',
        'pending_approval'  => 'Pending approval',
        'active'            => 'Active',
        'returned'          => 'Returned',
        'overdue'           => 'Overdue',
        'rejected'          => 'Rejected',
    ],
    'fields' => [
        'asset_number'     => 'Asset number',
        'name'             => 'Name',
        'description'      => 'Description',
        'category'         => 'Category',
        'serial_number'    => 'Serial number',
        'status'           => 'Status',
        'value'            => 'Value (OMR)',
        'location'         => 'Location',
        'purchased_at'     => 'Purchase date',
        'notes'            => 'Notes',
        'employee'         => 'Employee',
        'borrowed_by'      => 'Borrowed by',
        'borrowed_at'      => 'Borrowed at',
        'due_at'           => 'Due date',
        'returned_at'      => 'Returned at',
        'borrowing_status' => 'Borrowing status',
        'processed_by'     => 'Processed by',
        'employee_search'  => 'Search employees',
    ],
    'form' => [
        'auto_generated' => 'Auto-generated on save',
        'sections'       => [
            'details' => 'Asset details',
        ],
    ],
    'infolist' => [
        'sections' => [
            'details' => 'Asset details',
        ],
    ],
    'pages' => [
        'create_title' => 'Create asset',
        'edit_title'   => 'Edit :name',
        'view_title'   => ':name',
    ],
    'actions' => [
        'create'              => 'Create asset',
        'borrow'              => 'Borrow asset',
        'return'              => 'Return asset',
        'return_confirmation' => 'Mark this asset as returned and set its status to available?',
        'view'                => 'View',
    ],
    'relations' => [
        'borrowings' => 'Borrowing history',
    ],
    'empty' => [
        'no_assets'                 => 'No assets yet',
        'no_assets_description'     => 'Create your first asset record to start tracking physical items.',
        'no_borrowings'             => 'No borrowing records',
        'no_borrowings_description' => 'Borrowing history will appear here after an asset is lent to an employee.',
    ],
    'notifications' => [
        'borrowed' => [
            'title' => 'Asset borrowed',
            'body'  => ':name has been assigned to an employee.',
        ],
        'returned' => [
            'title' => 'Asset returned',
            'body'  => ':name has been returned and is now available.',
        ],
        'no_active_borrowing' => [
            'title' => 'No active borrowing',
        ],
        'overdue' => [
            'title' => 'Overdue asset borrowing',
            'body'  => 'Asset :name (:number) borrowed by :employee was due on :due_at.',
        ],
        'request_submitted' => [
            'title' => 'Borrowing request submitted',
            'body'  => ':employee submitted a request to borrow :name.',
        ],
        'request_approved' => [
            'title' => 'Borrowing request approved',
            'body'  => 'Your request to borrow :name has been approved.',
        ],
        'request_rejected' => [
            'title' => 'Borrowing request rejected',
            'body'  => 'Your request to borrow :name was rejected.',
        ],
    ],
    'commands' => [
        'overdue' => [
            'done'          => 'Queued notifications for :count overdue asset borrowing(s).',
            'table_missing' => 'asset_borrowings table not found.',
        ],
    ],
    'widgets' => [
        'stats' => [
            'unavailable'          => 'Assets',
            'plugin_not_installed' => 'Assets plugin is not installed.',
            'available'            => 'Available',
            'available_hint'       => 'Ready to borrow',
            'borrowed'             => 'Borrowed',
            'borrowed_hint'        => 'Currently assigned',
            'overdue'              => 'Overdue',
            'overdue_hint'         => 'Past due date',
        ],
    ],
];
