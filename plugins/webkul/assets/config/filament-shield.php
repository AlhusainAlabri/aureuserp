<?php

use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Filament\Widgets\AssetsStatsWidget;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];

return [
    'resources' => [
        'manage' => [
            AssetResource::class => [
                ...$basic,
                ...$delete,
                'borrow',
                'return_asset',
            ],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],

    'widgets' => [
        'exclude' => [
            AssetsStatsWidget::class,
        ],
    ],
];
