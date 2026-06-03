<?php

return [
    'substitute_section'    => 'بديل الإجازة',
    'substitute_employee'   => 'الموظف البديل',
    'substitute_helper'     => 'اختر زميلا لتغطية مهامك.',
    'handover_notes'        => 'ملاحظات التسليم',
    'handover_helper'       => 'تعليمات مختصرة للموظف البديل.',
    'substitute_pending'    => 'بانتظار الموافقة',
    'substitute_accepted'   => 'تم القبول',
    'substitute_declined'   => 'تم الرفض',
    'actions'               => [
        'accept_substitute'  => 'قبول',
        'decline_substitute' => 'رفض',
        'view_leave'         => 'عرض طلب الإجازة',
    ],
    'infolist' => [
        'substitute_status' => 'حالة البديل',
    ],
    'notifications' => [
        'substitute_request' => [
            'title' => 'تم طلبك كبديل',
            'body'  => ':employee يطلب منك تغطية مهامه من :start إلى :end',
        ],
        'substitute_accepted' => [
            'title' => 'وافق البديل',
            'body'  => ':substitute وافق على تغطية إجازتك.',
        ],
        'substitute_declined' => [
            'title' => 'رفض البديل',
            'body'  => ':substitute رفض تغطية إجازتك.',
        ],
    ],
    'covering_for' => 'أنت تغطي مهام :employee من :start إلى :end',
];
