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

    'self_check_in' => [
        'disabled'          => 'تسجيل الحضور الذاتي متوقف. يرجى مراجعة الموارد البشرية لتسجيل حضورك.',
        'ip_not_allowed'    => 'يمكنك تسجيل الحضور من شبكة الشركة فقط.',
        'location_required' => 'موقعك مطلوب لتسجيل الحضور. اسمح للمتصفح بالوصول إلى الموقع ثم حاول مرة أخرى.',
        'outside_geofence'  => 'أنت بعيد جداً عن مقر العمل لتسجيل الحضور.',
        'too_soon'          => 'سجّلت للتو بصمة. انتظر دقيقة قبل المحاولة مرة أخرى.',
    ],

    'my_attendance' => [
        'navigation'       => 'حضوري',
        'title'            => 'حضوري',
        'check_in'         => 'تسجيل حضور',
        'check_out'        => 'تسجيل انصراف',
        'recorded_in'      => 'تم تسجيل الحضور',
        'recorded_out'     => 'تم تسجيل الانصراف',
        'recorded_at'      => 'الوقت: :time',
        'location_hint'    => 'سيُطلب موقعك عند تسجيل الحضور.',
        'today'            => 'اليوم',
        'no_punches_today' => 'لا توجد بصمات مسجلة اليوم.',
        'month'            => 'هذا الشهر',
        'no_days'          => 'لا توجد أيام حضور مسجلة هذا الشهر بعد.',
        'columns'          => [
            'time'      => 'الوقت',
            'direction' => 'النوع',
            'source'    => 'المصدر',
            'date'      => 'التاريخ',
            'first_in'  => 'أول دخول',
            'last_out'  => 'آخر خروج',
            'worked'    => 'ساعات العمل',
            'late'      => 'التأخير (دقيقة)',
            'status'    => 'الحالة',
        ],
    ],

    'console' => [
        'close_day' => [
            'invalid_date' => 'يجب أن يكون التاريخ صحيحاً بالصيغة Y-m-d.',
            'summary'      => 'تم إقفال الحضور ليوم :date: عدد الموظفين المعالجين :processed، وعدد الغيابات المسجلة :absent.',
            'failed'       => 'تعذر إقفال يوم الموظف :employee: :message',
        ],
    ],
];
