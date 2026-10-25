<?php

use Webkul\Attendance\Filament\Resources\AttendanceDayResource;
use Webkul\Attendance\Filament\Resources\OvertimeRequestResource;

/*
 * Neither resource can delete: days are corrected by hand and overtime requests are decided once.
 * Approving and rejecting overtime use the `update` permission.
 */
$actions = ['view_any', 'view', 'create', 'update'];

return [
    'resources' => [
        'manage' => [
            AttendanceDayResource::class    => $actions,
            OvertimeRequestResource::class  => $actions,
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],

    'widgets' => [
        'exclude' => [],
    ],
];
