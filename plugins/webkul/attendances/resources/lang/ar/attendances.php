<?php

return [
    'navigation' => [
        'group' => 'الحضور والانصراف',
    ],

    'install' => [
        'success' => 'تم تثبيت إضافة الحضور والانصراف بنجاح.',
    ],

    'models' => [
        'attendance_punch'  => 'تسجيل حضور',
        'attendance_day'    => 'يوم حضور',
        'overtime_request'  => 'طلب عمل إضافي',
        'import_batch'      => 'دفعة استيراد',
    ],

    'models_plural' => [
        'attendance_punch'  => 'تسجيلات الحضور',
        'attendance_day'    => 'أيام الحضور',
        'overtime_request'  => 'طلبات العمل الإضافي',
        'import_batch'      => 'دفعات الاستيراد',
    ],

    'console' => [
        'close_day' => [
            'invalid_date' => 'يجب أن يكون التاريخ صحيحاً بالصيغة Y-m-d.',
            'summary'      => 'تم إقفال الحضور ليوم :date: عدد الموظفين المعالجين :processed، وعدد الغيابات المسجلة :absent.',
            'failed'       => 'تعذر إقفال يوم الموظف :employee: :message',
        ],
    ],
];
