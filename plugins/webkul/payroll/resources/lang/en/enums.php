<?php

return [
    'salary_component_type' => [
        'earning'       => 'Earning',
        'deduction'     => 'Deduction',
        'employer_cost' => 'Employer Cost',
    ],
    'calculation_type' => [
        'fixed'             => 'Fixed Amount',
        'percent_of_basic'  => 'Percentage of Basic',
        'percent_of_gross'  => 'Percentage of Gross',
        'formula'           => 'Formula',
        'hours_based'       => 'Hours Based',
    ],
    'batch_status' => [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'paid'             => 'Paid',
        'posted'           => 'Posted',
        'cancelled'        => 'Cancelled',
    ],
    'payslip_status' => [
        'draft'     => 'Draft',
        'validated' => 'Validated',
        'paid'      => 'Paid',
    ],
    'payment_method' => [
        'bank_transfer' => 'Bank Transfer',
        'cash'          => 'Cash',
        'cheque'        => 'Cheque',
    ],
    'loan_type' => [
        'salary_advance'  => 'Salary Advance',
        'personal_loan'   => 'Personal Loan',
        'emergency_loan'  => 'Emergency Loan',
        'other'           => 'Other',
    ],
    'loan_status' => [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'active'           => 'Active',
        'completed'        => 'Completed',
        'cancelled'        => 'Cancelled',
    ],
    'loan_installment_status' => [
        'scheduled' => 'Scheduled',
        'deducted'  => 'Deducted',
        'skipped'   => 'Skipped',
        'cancelled' => 'Cancelled',
    ],
];
