<?php

use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard;
use Webkul\DocumentArchive\Filament\Pages\DocumentManager;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;
use Webkul\DocumentArchive\Filament\Widgets\DocumentStatsWidget;
use Webkul\DocumentArchive\Filament\Widgets\RecentFilesWidget;

return [
    'resources' => [
        'manage' => [
            DocFileResource::class => [
                'view_any', 'view', 'create', 'update', 'delete', 'delete_any',
                'download', 'share',
            ],
            DocFolderResource::class => [
                'view_any', 'view', 'create', 'update', 'delete', 'delete_any',
                'manage_permissions',
            ],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            DocumentManager::class,
            DocumentDashboard::class,
        ],
    ],

    'widgets' => [
        'exclude' => [
            DocumentStatsWidget::class,
            RecentFilesWidget::class,
        ],
    ],
];
