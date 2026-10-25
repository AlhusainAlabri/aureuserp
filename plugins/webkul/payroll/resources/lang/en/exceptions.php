<?php

return [
    'batch_cannot_be_paid'           => 'This payroll batch cannot be marked as paid.',
    'batch_cannot_be_posted'         => 'This payroll batch cannot be posted to accounting.',
    'loan_not_approved'              => 'The loan must be fully approved before activation.',
    'settlement_no_departure_date'   => 'Set the employee departure date before creating the settlement.',
    'settlement_no_joining_date'     => 'The employee has no contract with a joining date, so the service period cannot be determined.',
    'settlement_exists'              => 'This employee already has an end-of-service settlement. Cancel it first to create a new one.',
    'settlement_cannot_be_paid'      => 'This settlement cannot be marked as paid (it must be approved first, and cannot be paid twice).',
    'settlement_cannot_be_cancelled' => 'A paid or cancelled settlement cannot be cancelled.',
    'settlement_locked'              => 'A paid settlement cannot be modified.',
    'payslip_line_locked'            => 'Payslip lines cannot be modified after validation.',
];
