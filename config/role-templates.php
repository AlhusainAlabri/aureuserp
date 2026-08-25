<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role Templates
    |--------------------------------------------------------------------------
    |
    | Starting points for role creation. Module levels are applied via
    | ModulePermissionResolver; explicit permissions supplement modules.
    |
    */

    'employee' => [
        'label'       => 'role-management.templates.employee.label',
        'description' => 'role-management.templates.employee.description',
        'modules'     => [
            'Employee'  => 'view',
            'TimeOff'   => 'view',
            'Payroll'   => 'view',
            'Assets'    => 'view',
            'MyNotes'   => 'view',
            'Meetings'  => 'none',
            'Project'   => 'none',
        ],
        'permissions' => [
            'page_MyEmployeeProfile',
            'page_MyEmployeeSubmissions',
            'page_MySelfAssessment',
            'page_MyWarnings',
            'page_MyRequests',
            'page_my_borrowing_requests',
            'page_my_borrowed_assets',
            'request_borrow_assets_asset',
        ],
    ],

    'manager' => [
        'label'       => 'role-management.templates.manager.label',
        'description' => 'role-management.templates.manager.description',
        'modules'     => [
            'Employee'       => 'standard',
            'TimeOff'        => 'standard',
            'Project'        => 'standard',
            'Meetings'       => 'standard',
            'Correspondence' => 'standard',
            'Purchase'       => 'standard',
            'Inventory'      => 'view',
            'Assets'         => 'standard',
        ],
        'permissions' => [
            'review_self_assessment',
            'page_pending_borrowing_requests',
            'approve_borrowing_assets_asset',
            'reject_borrowing_assets_asset',
            'page_InternalRequests',
        ],
    ],

    'hr_manager' => [
        'label'       => 'role-management.templates.hr_manager.label',
        'description' => 'role-management.templates.hr_manager.description',
        'modules'     => [
            'Employee'     => 'full',
            'TimeOff'      => 'full',
            'Payroll'      => 'standard',
            'Recruitment'  => 'standard',
            'Meetings'     => 'view',
            'Assets'       => 'standard',
        ],
        'permissions' => [
            'close_employee_file',
            'reopen_employee_file',
            'review_self_assessment',
            'view_any_self_assessment',
            'approve_borrowing_assets_asset',
            'reject_borrowing_assets_asset',
        ],
    ],

    'finance_manager' => [
        'label'       => 'role-management.templates.finance_manager.label',
        'description' => 'role-management.templates.finance_manager.description',
        'modules'     => [
            'Accounting' => 'full',
            'Account'    => 'full',
            'Invoice'    => 'full',
            'Payment'    => 'standard',
            'Purchase'   => 'standard',
            'Sale'       => 'view',
            'Inventory'  => 'view',
        ],
        'permissions' => [
            'page_purchases_department_report',
            'page_inventory_dashboard',
            'page_inventory_movement_report',
        ],
    ],

    'general_manager' => [
        'label'       => 'role-management.templates.general_manager.label',
        'description' => 'role-management.templates.general_manager.description',
        'modules'     => [
            'Employee'       => 'standard',
            'Accounting'     => 'standard',
            'Purchase'       => 'standard',
            'Sale'           => 'standard',
            'Inventory'      => 'standard',
            'Project'        => 'standard',
            'Meetings'       => 'standard',
            'Correspondence' => 'standard',
            'Assets'         => 'standard',
            'Support'        => 'view',
        ],
        'permissions' => [
            'page_filament_manage_navigation',
            'page_assets_dashboard',
            'page_inventory_dashboard',
            'approve_borrowing_assets_asset',
        ],
    ],

];
