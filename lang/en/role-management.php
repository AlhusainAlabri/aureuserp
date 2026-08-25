<?php

return [
    'starting_point' => [
        'label'    => 'Starting point',
        'blank'    => 'Blank role',
        'template' => 'Role template',
        'clone'    => 'Clone existing role',
    ],

    'fields' => [
        'role_template'        => 'Role template',
        'clone_role_id'        => 'Clone from role',
        'module_access'        => 'Module access',
        'advanced_editor_open' => 'Open advanced permission editor',
        'grant_all_modules'    => 'Grant full access to all modules',
    ],

    'sections' => [
        'basics'    => 'Role details',
        'starting'  => 'Starting point',
        'modules'   => 'Module access',
        'summary'   => 'Permission summary',
        'advanced'  => 'Advanced permission editor',
        'review'    => 'Review',
    ],

    'wizard' => [
        'details' => 'Details',
        'access'  => 'Access',
        'review'  => 'Review',
    ],

    'module_access' => [
        'levels' => [
            'none'     => 'None',
            'view'     => 'View only',
            'standard' => 'Standard (view + create + update)',
            'full'     => 'Full access',
        ],
        'selected_count'      => ':selected / :total permissions',
        'modules_with_access' => ':count of :total modules with access',
        'reset_template'      => 'Reset to template defaults',
    ],

    'summary' => [
        'permissions' => ':selected of :total permissions selected',
        'modules'     => ':count of :total modules with access',
        'no_modules'  => 'No module access configured yet.',
    ],

    'templates' => [
        'employee' => [
            'label'       => 'Employee',
            'description' => 'Self-service HR, submissions, and personal assets access.',
        ],
        'manager' => [
            'label'       => 'Manager',
            'description' => 'Team oversight across HR, projects, purchases, and assets.',
        ],
        'hr_manager' => [
            'label'       => 'HR Manager',
            'description' => 'Full HR operations including employee files and assessments.',
        ],
        'finance_manager' => [
            'label'       => 'Finance Manager',
            'description' => 'Accounting, invoicing, and financial reporting access.',
        ],
        'general_manager' => [
            'label'       => 'General Manager',
            'description' => 'Broad cross-module access for executive oversight.',
        ],
    ],

    'modules' => [
        'Accounting'     => 'Accounting',
        'Account'        => 'Accounts',
        'Employee'       => 'Employees',
        'Inventory'      => 'Inventory',
        'Purchase'       => 'Purchases',
        'Sale'           => 'Sales',
        'Project'        => 'Projects',
        'Meetings'       => 'Meetings',
        'Correspondence' => 'Correspondence',
        'Assets'         => 'Assets',
        'Payroll'        => 'Payroll',
        'TimeOff'        => 'Time Off',
        'Recruitment'    => 'Recruitment',
        'Support'        => 'Support',
        'Extensions'     => 'Extensions',
    ],

    'advanced' => [
        'search'          => 'Search permissions',
        'resources'       => 'Resources',
        'pages'           => 'Pages',
        'widgets'         => 'Widgets',
        'custom'          => 'Custom permissions',
        'select_module'   => 'Select all in module',
        'deselect_module' => 'Deselect all in module',
        'progress'        => ':selected / :total selected',
    ],

    'view' => [
        'permissions_count' => 'Permissions',
        'module_summary'    => 'Module access summary',
        'permission_list'   => 'Assigned permissions',
    ],
];
