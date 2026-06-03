<?php

return [
    'tabs' => [
        'below_minimum' => 'تحت الحد الأدنى',
    ],
    'columns' => [
        'procurement' => 'التوريد الافتراضي',
    ],
    'fields' => [
        'default_procurement'  => 'التوريد الافتراضي',
        'default_request_type' => 'نوع الطلب الافتراضي',
    ],
    'actions' => [
        'internal_request'      => 'إنشاء طلب داخلي',
        'draft_po'              => 'إنشاء أمر شراء مسودة',
        'preference'            => 'تفضيل التوريد',
        'bulk_internal_request' => 'إنشاء طلبات داخلية',
        'bulk_draft_po'         => 'إنشاء أوامر شراء مسودة',
    ],
    'notifications' => [
        'internal_request_created' => 'تم إنشاء الطلب الداخلي',
        'draft_po_created'         => 'تم إنشاء أمر الشراء المسودة',
        'preference_saved'         => 'تم حفظ تفضيل التوريد',
        'bulk_created'             => 'تم إنشاء :count مستند/مستندات توريد',
    ],
];
