<?php

return [
    'actions' => [
        'view' => 'عرض السجل',
    ],

    'fallback' => [
        'record' => 'سجل',
        'step'   => 'خطوة الموافقة',
    ],

    'mail' => [
        'footer' => 'شكراً لاستخدامك :app.',
    ],

    'approvals' => [
        'requested' => [
            'title' => 'طلب موافقة: :step',
            'body'  => 'لديك موافقة معلقة على :record في خطوة :step.',
        ],
        'sla_warning' => [
            'title' => 'تنبيه الموعد النهائي للموافقة: :step',
            'body'  => 'الموافقة على :record في خطوة :step تقترب من الموعد النهائي (:deadline).',
        ],
        'escalated' => [
            'title' => 'تم تصعيد الموافقة',
            'body'  => 'تم تصعيد الموافقة على :record ويتطلب انتباهك.',
        ],
        'rejected' => [
            'title' => 'تم رفض الموافقة',
            'body'  => 'تم رفض طلب الموافقة الخاص بك على :record.',
        ],
        'completed' => [
            'title' => 'اكتملت الموافقة',
            'body'  => 'تمت الموافقة بالكامل على طلبك المتعلق بـ :record.',
        ],
    ],
];
