<?php

return [
    'navigation' => [
        'group'  => 'Payroll',
        'config' => 'Payroll Configuration',
    ],

    'currency' => [
        'symbol_ar' => 'ر.ع.',
        'symbol_en' => 'OMR',
    ],

    'install' => [
        'success' => 'Payroll plugin installed successfully.',
    ],

    'models' => [
        'salary_component'   => 'Salary Component',
        'employee_component' => 'Employee Assignment',
        'payroll_batch'      => 'Payroll Batch',
        'payslip'            => 'Payslip',
        'loan'               => 'Employee Loan',
    ],

    'models_plural' => [
        'salary_component'   => 'Salary Components',
        'employee_component' => 'Salary Assignments',
        'payroll_batch'      => 'Payroll Batches',
        'payslip'            => 'Payslips',
        'loan'               => 'Employee Loans',
    ],

    'fields' => [
        'code'                => 'Code',
        'name'                => 'Name (English)',
        'name_ar'             => 'Name (Arabic)',
        'display_name'        => 'Name',
        'type'                => 'Type',
        'calculation_type'    => 'Calculation Type',
        'default_amount'      => 'Default Amount',
        'default_percent'     => 'Default Percent',
        'formula'             => 'Formula',
        'is_taxable'          => 'Taxable',
        'is_active'           => 'Active',
        'sort_order'          => 'Sort Order',
        'account'             => 'GL Account',
        'company'             => 'Company',
        'employee'            => 'Employee',
        'component'           => 'Salary Component',
        'amount'              => 'Amount',
        'percent'             => 'Percent',
        'start_date'          => 'Start Date',
        'end_date'            => 'End Date',
        'notes'               => 'Notes',
        'reference_number'    => 'Reference',
        'period_year'         => 'Year',
        'period_month'        => 'Month',
        'period'              => 'Period',
        'pay_date'            => 'Pay Date',
        'status'              => 'Status',
        'total_gross'         => 'Total Gross',
        'total_deductions'    => 'Total Deductions',
        'total_net'           => 'Total Net',
        'total_employer_cost' => 'Employer Cost',
        'employee_count'      => 'Employees',
        'journal'             => 'Journal',
        'account_move'        => 'Journal Entry',
        'batch'               => 'Payroll Batch',
        'working_days'        => 'Working Days',
        'worked_days'         => 'Worked Days',
        'unpaid_leave_days'   => 'Unpaid Leave Days',
        'basic_salary'        => 'Basic Salary',
        'gross_amount'        => 'Gross',
        'deductions_amount'   => 'Deductions',
        'net_amount'          => 'Net Pay',
        'employer_cost'       => 'Employer Cost',
        'payment_method'      => 'Payment Method',
        'bank_account_number' => 'Bank Account',
        'bank_name'           => 'Bank',
        'cheque_number'       => 'Cheque Number',
        'loan_type'           => 'Loan Type',
        'total_amount'        => 'Total Amount',
        'installment_count'   => 'Installments',
        'installment_amount'  => 'Installment Amount',
        'start_period'        => 'Start Period',
        'end_period'          => 'End Period',
        'reason'              => 'Reason',
        'amount_repaid'       => 'Amount Repaid',
        'amount_remaining'    => 'Remaining',
        'progress'            => 'Progress',
        'quantity'            => 'Qty',
        'rate'                => 'Rate',
        'deducted_at'         => 'Deducted At',
        'payslip'             => 'Payslip',
        'department'          => 'Department',
        'auto_generated'      => 'Auto-generated',
    ],

    'form' => [
        'sections' => [
            'details'     => 'Details',
            'calculation' => 'Calculation',
            'accounting'  => 'Accounting',
            'assignment'  => 'Assignment',
            'period'      => 'Pay Period',
            'totals'      => 'Totals',
            'payment'     => 'Payment',
            'loan'        => 'Loan Details',
            'lines'       => 'Payslip Lines',
        ],
    ],

    'table' => [
        'empty' => 'No records found.',
    ],

    'filters' => [
        'employee'   => 'Employee',
        'status'     => 'Status',
        'type'       => 'Type',
        'year'       => 'Year',
        'month'      => 'Month',
        'department' => 'Department',
        'from'       => 'From',
        'until'      => 'Until',
        'all'        => 'All',
    ],

    'tabs' => [
        'all'       => 'All',
        'draft'     => 'Draft',
        'validated' => 'Validated',
        'paid'      => 'Paid',
    ],

    'actions' => [
        'generate'           => 'Generate Payslips',
        'mark_paid'          => 'Mark as Paid',
        'post_to_accounting' => 'Post to Accounting',
        'export_wps'         => 'Export WPS',
        'export_pdf'         => 'Export PDF',
        'recalculate'        => 'Recalculate',
        'validate'           => 'Validate',
        'email_pdf'          => 'Email PDF',
        'activate'           => 'Activate Loan',
        'cancel'             => 'Cancel',
        'download'           => 'Download',
    ],

    'batch' => [
        'title'   => 'Payroll Batch',
        'actions' => [
            'generate'           => 'Generate Payslips',
            'mark_paid'          => 'Mark Batch as Paid',
            'post_to_accounting' => 'Post to Accounting',
            'export_wps'         => 'Export WPS File',
            'export_pdf'         => 'Export Payroll Register',
        ],
        'notifications' => [
            'generated' => [
                'title' => 'Payslips generated',
                'body'  => ':count payslips generated for :period.',
            ],
            'paid' => [
                'title' => 'Batch marked as paid',
            ],
            'posted' => [
                'title' => 'Posted to accounting',
            ],
        ],
    ],

    'payslip' => [
        'title'   => 'Payslip',
        'actions' => [
            'recalculate' => 'Recalculate Payslip',
            'validate'    => 'Validate Payslip',
            'export_pdf'  => 'Export Payslip PDF',
            'email_pdf'   => 'Email Payslip PDF',
        ],
        'notifications' => [
            'recalculated' => ['title' => 'Payslip recalculated'],
            'validated'    => ['title' => 'Payslip validated'],
            'exported'     => ['title' => 'PDF generated'],
        ],
        'lines' => [
            'earnings'      => 'Earnings',
            'deductions'    => 'Deductions',
            'employer_cost' => 'Employer Costs',
        ],
    ],

    'loan' => [
        'title'   => 'Employee Loan',
        'actions' => [
            'activate' => 'Activate Loan',
            'cancel'   => 'Cancel Loan',
        ],
        'notifications' => [
            'activated' => ['title' => 'Loan activated'],
            'cancelled' => ['title' => 'Loan cancelled'],
        ],
        'installments' => 'Installments',
    ],

    'relations' => [
        'approvals'            => 'Approvals',
        'payslips'             => 'Payslips',
        'installments'         => 'Installments',
        'employee_components'  => 'Salary Assignments',
    ],

    'my_payslips' => [
        'navigation'   => 'My Payslips',
        'title'        => 'My Payslips',
        'view'         => 'View Details',
        'download'     => 'Download PDF',
        'period'       => 'Period',
        'ytd'          => 'Year-to-date earnings',
        'this_month'   => 'This month\'s salary',
        'active_loans' => 'Active loans',
        'empty'        => [
            'title'       => 'No payslips yet',
            'description' => 'Your payslips will appear here once payroll is processed.',
        ],
    ],

    'reports' => [
        'navigation'       => 'Payroll Reports',
        'title'            => 'Payroll Reports',
        'summary'          => 'Summary',
        'by_department'    => 'By Department',
        'by_component'     => 'By Component',
        'total_gross'      => 'Total Gross',
        'total_net'        => 'Total Net',
        'total_deductions' => 'Total Deductions',
        'employee_count'   => 'Employees Paid',
        'payslip_count'    => 'Payslips',
    ],

    'pdf' => [
        'payslip' => [
            'title'            => 'Payslip',
            'employee_details' => 'Employee Details',
            'earnings'         => 'Earnings',
            'deductions'       => 'Deductions',
            'employer_costs'   => 'Employer Costs',
            'summary'          => 'Summary',
            'footer'           => 'Reference: :reference — Page :page',
        ],
        'register' => [
            'title'     => 'Payroll Register',
            'period'    => 'Period',
            'summary'   => 'Batch Summary',
            'employees' => 'Employee Payslips',
            'footer'    => 'Batch: :reference — Page :page',
        ],
    ],

    'notifications' => [
        'saved'         => ['title' => 'Saved successfully'],
        'error'         => ['title' => 'An error occurred'],
        'no_accounting' => [
            'title' => 'Accounting module not available',
            'body'  => 'Install the accounts plugin to post payroll entries.',
        ],
    ],

    'months' => [
        1  => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5  => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9  => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ],

    'email' => [
        'subject'  => 'Your payslip for :period / كشف راتبك لشهر :period',
        'greeting' => 'Dear :name,',
        'body'     => 'Please find attached your payslip for :period.',
        'net'      => 'Net pay: ر.ع. :amount',
        'footer'   => 'This is an automated message from the payroll system.',
    ],

];
