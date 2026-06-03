<?php

return [
    'navigation'        => 'عقود التوظيف',
    'empty_heading'     => 'لا توجد عقود مسجلة',
    'empty_description' => 'أضف عقود التوظيف مع تواريخ البداية والنهاية والمرفقات.',
    'fields'            => [
        'contract_type'      => 'نوع العقد',
        'start_date'         => 'تاريخ بداية العقد',
        'end_date'           => 'تاريخ نهاية العقد',
        'renewal_date'       => 'تاريخ التجديد',
        'first_joining_date' => 'تاريخ أول التحاق',
        'wage'               => 'الراتب',
        'contract_file'      => 'ملف عقد التوظيف',
        'notes'              => 'ملاحظات',
        'is_active'          => 'عقد نشط',
    ],
    'types' => [
        'permanent'  => 'دائم',
        'fixed_term' => 'محدد المدة',
        'temporary'  => 'مؤقت',
        'probation'  => 'فترة تجريبية',
    ],
    'actions' => [
        'add'       => 'إضافة عقد',
        'view_file' => 'عرض العقد',
    ],
    'notifications' => [
        'expiring_title' => 'عقد على وشك الانتهاء',
        'expiring_body'  => 'ينتهي عقد :employee في :end_date.',
    ],
];
