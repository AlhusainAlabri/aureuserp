<?php

use Webkul\Meetings\Filament\Pages\MeetingCalendar;
use Webkul\Meetings\Filament\Pages\MeetingDashboard;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\MeetingApprovalsTable;
use Webkul\Meetings\Filament\Widgets\MeetingCalendarWidget;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\RecentConfirmedMeetingsTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];

return [
    'resources' => [
        'manage' => [
            MeetingResource::class => [
                ...$basic,
                ...$delete,
                'archive',
                'confirm',
                'export_pdf',
                'manage_tasks',
                'manage_attendees',
            ],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            MeetingDashboard::class,
            MeetingCalendar::class,
        ],
    ],

    'widgets' => [
        'exclude' => [
            MeetingApprovalsTable::class,
            MeetingCalendarWidget::class,
            MeetingDashboardStats::class,
            MeetingTasksTable::class,
            RecentConfirmedMeetingsTable::class,
            UpcomingMeetingsTable::class,
        ],
    ],
];
