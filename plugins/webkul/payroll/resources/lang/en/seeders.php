<?php

return [
    'batch_approval' => [
        'flow_name' => 'Payroll Batch Approval',
        'steps'     => [
            'hr_manager'      => 'HR Manager',
            'finance_manager' => 'Finance Manager',
            'general_manager' => 'General Manager',
        ],
    ],
    'loan_approval' => [
        'flow_name' => 'Employee Loan Approval',
        'steps'     => [
            'manager'         => 'Direct Manager',
            'hr_manager'      => 'HR Manager',
            'finance_manager' => 'Finance Manager',
        ],
    ],
];
