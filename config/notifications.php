<?php

use App\Filament\Assets\Resources\AssetBorrowingResource;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource;
use Webkul\Accounting\Models\Invoice;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Models\Meeting;

return [
    'defaults' => [
        'database_enabled' => true,
        'email_enabled'    => true,
    ],

    'categories' => [
        'tasks' => [
            'label'        => 'notifications.categories.tasks',
            'description'  => 'notifications.categories.tasks_description',
            'required'     => false,
            'prefixes'     => ['tasks.'],
            'task_offsets' => true,
        ],
        'assets' => [
            'label'       => 'notifications.categories.assets',
            'description' => 'notifications.categories.assets_description',
            'required'    => false,
            'prefixes'    => ['assets.'],
        ],
        'hr' => [
            'label'       => 'notifications.categories.hr',
            'description' => 'notifications.categories.hr_description',
            'required'    => false,
            'prefixes'    => ['hr.'],
        ],
        'inventory' => [
            'label'       => 'notifications.categories.inventory',
            'description' => 'notifications.categories.inventory_description',
            'required'    => false,
            'prefixes'    => ['inventory.'],
        ],
        'notes' => [
            'label'       => 'notifications.categories.notes',
            'description' => 'notifications.categories.notes_description',
            'required'    => false,
            'prefixes'    => ['notes.'],
        ],
        'approvals' => [
            'label'       => 'notifications.categories.approvals',
            'description' => 'notifications.categories.approvals_description',
            'required'    => true,
            'prefixes'    => ['approvals.'],
        ],
        'accounting' => [
            'label'       => 'notifications.categories.accounting',
            'description' => 'notifications.categories.accounting_description',
            'required'    => false,
            'prefixes'    => ['accounting.'],
        ],
    ],

    'approvable_resources' => [
        AssetBorrowing::class => AssetBorrowingResource::class,
        Meeting::class        => MeetingResource::class,
        Correspondence::class => CorrespondenceResource::class,
        Invoice::class        => InvoiceResource::class,
    ],

    'operations' => [
        'report_hours'                 => (int) env('NOTIFICATION_REPORT_HOURS', 24),
        'report_recipients'            => env('NOTIFICATION_OPS_EMAIL'),
        'failed_jobs_threshold'        => (int) env('NOTIFICATION_OPS_FAILED_JOBS_THRESHOLD', 5),
        'failed_email_rate_threshold'  => (float) env('NOTIFICATION_OPS_FAILED_EMAIL_RATE', 0.05),
        'queue_backlog_threshold'      => (int) env('NOTIFICATION_OPS_QUEUE_BACKLOG', 250),
    ],
];
