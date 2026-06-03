<?php

return [
    'navigation' => 'التدريب',
    'types'      => [
        'internal'      => 'داخلي',
        'external'      => 'خارجي',
        'online'        => 'عن بعد',
        'workshop'      => 'ورشة عمل',
        'conference'    => 'مؤتمر',
        'certification' => 'اعتماد مهني',
    ],
    'statuses' => [
        'planned'     => 'مخطط',
        'in_progress' => 'قيد التنفيذ',
        'completed'   => 'مكتمل',
        'cancelled'   => 'ملغي',
    ],
    'fields' => [
        'course_name'             => 'اسم الدورة',
        'provider'                => 'الجهة المقدمة',
        'type'                    => 'النوع',
        'status'                  => 'الحالة',
        'start_date'              => 'تاريخ البدء',
        'end_date'                => 'تاريخ الانتهاء',
        'duration_hours'          => 'المدة بالساعات',
        'cost'                    => 'التكلفة',
        'certificate'             => 'الشهادة',
        'certificate_expiry_date' => 'تاريخ انتهاء الشهادة',
        'notes'                   => 'ملاحظات',
    ],
    'actions' => [
        'view_certificate'     => 'عرض الشهادة',
        'download_certificate' => 'تحميل الشهادة',
        'close'                => 'إغلاق',
        'add'                  => 'إضافة تدريب',
    ],
    'empty_heading'     => 'لا توجد سجلات تدريب',
    'empty_description' => 'أضف الدورات والاعتمادات وسجل التدريب لهذا الموظف.',
    'duration_unknown'  => 'غير محدد',
    'duration_hours'    => ':count ساعة',
    'duration_days'     => ':count يوم',
    'notifications'     => [
        'expiring' => [
            'title' => 'شهادة تدريب قاربت على الانتهاء',
            'body'  => ':employee — :course تنتهي بتاريخ :date',
        ],
    ],
];
