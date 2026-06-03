<?php

return [
    'navigation' => [
        'group'  => 'الرواتب',
        'config' => 'إعدادات الرواتب',
    ],

    'currency' => [
        'symbol_ar' => 'ر.ع.',
        'symbol_en' => 'OMR',
    ],

    'install' => [
        'success' => 'تم تثبيت وحدة الرواتب بنجاح.',
    ],

    'models' => [
        'salary_component'   => 'مكون الراتب',
        'employee_component' => 'تعيين الراتب',
        'payroll_batch'      => 'دفعة الرواتب',
        'payslip'            => 'كشف الراتب',
        'loan'               => 'قرض الموظف',
    ],

    'models_plural' => [
        'salary_component'   => 'مكونات الراتب',
        'employee_component' => 'تعيينات الراتب',
        'payroll_batch'      => 'دفعات الرواتب',
        'payslip'            => 'كشوف الرواتب',
        'loan'               => 'قروض الموظفين',
    ],

    'fields' => [
        'code'                => 'الرمز',
        'name'                => 'الاسم (إنجليزي)',
        'name_ar'             => 'الاسم (عربي)',
        'display_name'        => 'الاسم',
        'type'                => 'النوع',
        'calculation_type'    => 'طريقة الحساب',
        'default_amount'      => 'المبلغ الافتراضي',
        'default_percent'     => 'النسبة الافتراضية',
        'formula'             => 'المعادلة',
        'is_taxable'          => 'خاضع للضريبة',
        'is_active'           => 'نشط',
        'sort_order'          => 'الترتيب',
        'account'             => 'حساب الأستاذ',
        'company'             => 'الشركة',
        'employee'            => 'الموظف',
        'component'           => 'مكون الراتب',
        'amount'              => 'المبلغ',
        'percent'             => 'النسبة',
        'start_date'          => 'تاريخ البداية',
        'end_date'            => 'تاريخ النهاية',
        'notes'               => 'ملاحظات',
        'reference_number'    => 'المرجع',
        'period_year'         => 'السنة',
        'period_month'        => 'الشهر',
        'period'              => 'الفترة',
        'pay_date'            => 'تاريخ الدفع',
        'status'              => 'الحالة',
        'total_gross'         => 'إجمالي الاستحقاقات',
        'total_deductions'    => 'إجمالي الاستقطاعات',
        'total_net'           => 'صافي الإجمالي',
        'total_employer_cost' => 'تكلفة صاحب العمل',
        'employee_count'      => 'الموظفون',
        'journal'             => 'دفتر اليومية',
        'account_move'        => 'قيد اليومية',
        'batch'               => 'دفعة الرواتب',
        'working_days'        => 'أيام العمل',
        'worked_days'         => 'أيام الحضور',
        'unpaid_leave_days'   => 'أيام الإجازة بدون راتب',
        'basic_salary'        => 'الراتب الأساسي',
        'gross_amount'        => 'الإجمالي',
        'deductions_amount'   => 'الاستقطاعات',
        'net_amount'          => 'صافي الراتب',
        'employer_cost'       => 'تكلفة صاحب العمل',
        'payment_method'      => 'طريقة الدفع',
        'bank_account_number' => 'رقم الحساب',
        'bank_name'           => 'البنك',
        'cheque_number'       => 'رقم الشيك',
        'loan_type'           => 'نوع القرض',
        'total_amount'        => 'المبلغ الإجمالي',
        'installment_count'   => 'عدد الأقساط',
        'installment_amount'  => 'قيمة القسط',
        'start_period'        => 'فترة البداية',
        'end_period'          => 'فترة النهاية',
        'reason'              => 'السبب',
        'amount_repaid'       => 'المبلغ المسدد',
        'amount_remaining'    => 'المتبقي',
        'progress'            => 'التقدم',
        'quantity'            => 'الكمية',
        'rate'                => 'المعدل',
        'deducted_at'         => 'تاريخ الخصم',
        'payslip'             => 'كشف الراتب',
        'department'          => 'القسم',
        'auto_generated'      => 'يُنشأ تلقائياً',
    ],

    'form' => [
        'sections' => [
            'details'     => 'التفاصيل',
            'calculation' => 'الحساب',
            'accounting'  => 'المحاسبة',
            'assignment'  => 'التعيين',
            'period'      => 'فترة الدفع',
            'totals'      => 'الإجماليات',
            'payment'     => 'الدفع',
            'loan'        => 'تفاصيل القرض',
            'lines'       => 'بنود كشف الراتب',
        ],
    ],

    'table' => [
        'empty' => 'لا توجد سجلات.',
    ],

    'filters' => [
        'employee'   => 'الموظف',
        'status'     => 'الحالة',
        'type'       => 'النوع',
        'year'       => 'السنة',
        'month'      => 'الشهر',
        'department' => 'القسم',
        'from'       => 'من',
        'until'      => 'إلى',
        'all'        => 'الكل',
    ],

    'tabs' => [
        'all'       => 'الكل',
        'draft'     => 'مسودة',
        'validated' => 'معتمد',
        'paid'      => 'مدفوع',
    ],

    'actions' => [
        'generate'           => 'إنشاء كشوف الرواتب',
        'mark_paid'          => 'تحديد كمدفوع',
        'post_to_accounting' => 'ترحيل للمحاسبة',
        'export_wps'         => 'تصدير WPS',
        'export_pdf'         => 'تصدير PDF',
        'recalculate'        => 'إعادة الحساب',
        'validate'           => 'اعتماد',
        'email_pdf'          => 'إرسال PDF',
        'activate'           => 'تفعيل القرض',
        'cancel'             => 'إلغاء',
        'download'           => 'تنزيل',
    ],

    'batch' => [
        'title'   => 'دفعة الرواتب',
        'actions' => [
            'generate'           => 'إنشاء كشوف الرواتب',
            'mark_paid'          => 'تحديد الدفعة كمدفوعة',
            'post_to_accounting' => 'ترحيل للمحاسبة',
            'export_wps'         => 'تصدير ملف WPS',
            'export_pdf'         => 'تصدير سجل الرواتب',
        ],
        'notifications' => [
            'generated' => [
                'title' => 'تم إنشاء كشوف الرواتب',
                'body'  => 'تم إنشاء :count كشف راتب لـ :period.',
            ],
            'paid' => [
                'title' => 'تم تحديد الدفعة كمدفوعة',
            ],
            'posted' => [
                'title' => 'تم الترحيل للمحاسبة',
            ],
        ],
    ],

    'payslip' => [
        'title'   => 'كشف الراتب',
        'actions' => [
            'recalculate' => 'إعادة حساب كشف الراتب',
            'validate'    => 'اعتماد كشف الراتب',
            'export_pdf'  => 'تصدير PDF',
            'email_pdf'   => 'إرسال PDF بالبريد',
        ],
        'notifications' => [
            'recalculated' => ['title' => 'تم إعادة حساب كشف الراتب'],
            'validated'    => ['title' => 'تم اعتماد كشف الراتب'],
            'exported'     => ['title' => 'تم إنشاء PDF'],
        ],
        'lines' => [
            'earnings'      => 'الاستحقاقات',
            'deductions'    => 'الاستقطاعات',
            'employer_cost' => 'تكاليف صاحب العمل',
        ],
    ],

    'loan' => [
        'title'   => 'قرض الموظف',
        'actions' => [
            'activate' => 'تفعيل القرض',
            'cancel'   => 'إلغاء القرض',
        ],
        'notifications' => [
            'activated' => ['title' => 'تم تفعيل القرض'],
            'cancelled' => ['title' => 'تم إلغاء القرض'],
        ],
        'installments' => 'الأقساط',
    ],

    'relations' => [
        'approvals'           => 'الموافقات',
        'payslips'            => 'كشوف الرواتب',
        'installments'        => 'الأقساط',
        'employee_components' => 'تعيينات الراتب',
    ],

    'my_payslips' => [
        'navigation' => 'كشوف رواتبي',
        'title'      => 'كشوف رواتبي',
        'view'       => 'عرض التفاصيل',
        'empty'      => [
            'title'       => 'لا توجد كشوف رواتب',
            'description' => 'ستظهر كشوف رواتبك هنا بعد معالجة الرواتب.',
        ],
        'download' => 'تنزيل PDF',
        'period'   => 'الفترة',
    ],

    'reports' => [
        'navigation'       => 'تقارير الرواتب',
        'title'            => 'تقارير الرواتب',
        'summary'          => 'ملخص',
        'by_department'    => 'حسب القسم',
        'by_component'     => 'حسب المكون',
        'total_gross'      => 'إجمالي الاستحقاقات',
        'total_net'        => 'صافي الإجمالي',
        'total_deductions' => 'إجمالي الاستقطاعات',
        'employee_count'   => 'الموظفون المدفوعون',
        'payslip_count'    => 'كشوف الرواتب',
    ],

    'pdf' => [
        'payslip' => [
            'title'            => 'كشف الراتب',
            'employee_details' => 'بيانات الموظف',
            'earnings'         => 'الاستحقاقات',
            'deductions'       => 'الاستقطاعات',
            'employer_costs'   => 'تكاليف صاحب العمل',
            'summary'          => 'الملخص',
            'footer'           => 'المرجع: :reference — صفحة :page',
        ],
        'register' => [
            'title'     => 'سجل الرواتب',
            'period'    => 'الفترة',
            'summary'   => 'ملخص الدفعة',
            'employees' => 'كشوف رواتب الموظفين',
            'footer'    => 'الدفعة: :reference — صفحة :page',
        ],
    ],

    'notifications' => [
        'saved'         => ['title' => 'تم الحفظ بنجاح'],
        'error'         => ['title' => 'حدث خطأ'],
        'no_accounting' => [
            'title' => 'وحدة المحاسبة غير متوفرة',
            'body'  => 'قم بتثبيت وحدة الحسابات لترحيل قيود الرواتب.',
        ],
    ],

    'months' => [
        1  => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5  => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9  => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ],
];
