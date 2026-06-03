<?php

use Webkul\Payroll\Filament\Resources\LoanResource;
use Webkul\Payroll\Filament\Resources\PayrollBatchResource;
use Webkul\Payroll\Filament\Resources\PayslipResource;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];

return [
    'resources' => [
        'manage' => [
            SalaryComponentResource::class => [
                ...$basic,
                ...$delete,
            ],
            PayrollBatchResource::class => [
                ...$basic,
                ...$delete,
                'generate',
                'mark_paid',
                'post_to_accounting',
                'export_wps',
                'export_pdf',
                'submit_for_approval',
                'approve',
                'reject',
            ],
            PayslipResource::class => [
                ...$basic,
                ...$delete,
                'recalculate',
                'validate',
                'export_pdf',
                'email_pdf',
            ],
            LoanResource::class => [
                ...$basic,
                ...$delete,
                'activate',
                'cancel',
                'submit_for_approval',
                'approve',
                'reject',
            ],
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
