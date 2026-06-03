<?php

return [
    'salary_component_type' => [
        'earning'       => 'استحقاق',
        'deduction'     => 'استقطاع',
        'employer_cost' => 'تكلفة صاحب العمل',
    ],
    'calculation_type' => [
        'fixed'             => 'مبلغ ثابت',
        'percent_of_basic'  => 'نسبة من الأساسي',
        'percent_of_gross'  => 'نسبة من الإجمالي',
        'formula'           => 'معادلة',
        'hours_based'       => 'حسب الساعات',
    ],
    'batch_status' => [
        'draft'            => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved'         => 'معتمد',
        'paid'             => 'مدفوع',
        'posted'           => 'مرحّل',
        'cancelled'        => 'ملغى',
    ],
    'payslip_status' => [
        'draft'     => 'مسودة',
        'validated' => 'معتمد',
        'paid'      => 'مدفوع',
    ],
    'payment_method' => [
        'bank_transfer' => 'تحويل بنكي',
        'cash'          => 'نقداً',
        'cheque'        => 'شيك',
    ],
    'loan_type' => [
        'salary_advance'  => 'سلفة راتب',
        'personal_loan'   => 'قرض شخصي',
        'emergency_loan'  => 'قرض طارئ',
        'other'           => 'أخرى',
    ],
    'loan_status' => [
        'draft'            => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved'         => 'معتمد',
        'active'           => 'نشط',
        'completed'        => 'مكتمل',
        'cancelled'        => 'ملغى',
    ],
    'loan_installment_status' => [
        'scheduled' => 'مجدول',
        'deducted'  => 'مخصوم',
        'skipped'   => 'متخطى',
        'cancelled' => 'ملغى',
    ],
];
