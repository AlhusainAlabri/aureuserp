<?php

use Webkul\Correspondence\Filament\Pages\CorrespondenceDashboard;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceApprovalsTable;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceDashboardStats;
use Webkul\Correspondence\Filament\Widgets\IncomingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\PendingOutgoingCorrespondencesTable;
use Webkul\Correspondence\Filament\Widgets\UrgentCorrespondencesTable;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];

return [
    'resources' => [
        'manage' => [
            CorrespondenceResource::class => [
                ...$basic,
                ...$delete,
                'archive',
                'send',
                'export_pdf',
                'manage_followers',
                'view_all_departments',
            ],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            CorrespondenceDashboard::class,
        ],
    ],

    'widgets' => [
        'exclude' => [
            CorrespondenceApprovalsTable::class,
            CorrespondenceDashboardStats::class,
            IncomingCorrespondencesTable::class,
            PendingOutgoingCorrespondencesTable::class,
            UrgentCorrespondencesTable::class,
        ],
    ],
];
