<?php

return [
    'batch_approval' => [
        'flow_name' => 'اعتماد دفعة الرواتب',
        'steps'     => [
            'hr_manager'      => 'مدير الموارد البشرية',
            'finance_manager' => 'مدير المالية',
            'general_manager' => 'المدير العام',
        ],
    ],
    'loan_approval' => [
        'flow_name' => 'اعتماد قرض الموظف',
        'steps'     => [
            'manager'         => 'المدير المباشر',
            'hr_manager'      => 'مدير الموارد البشرية',
            'finance_manager' => 'مدير المالية',
        ],
    ],
];
