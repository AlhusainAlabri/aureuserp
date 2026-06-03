<?php

return [
    'title'      => 'وارد/صادر',
    'breadcrumb' => 'حركات المخزون',

    'empty-state' => [
        'heading'     => 'لا توجد حركات مخزون',
        'description' => 'ستظهر حركات الوارد والصادر لهذا المنتج هنا.',
    ],

    'tabs' => [
        'todo'     => 'للتنفيذ',
        'done'     => 'منجز',
        'incoming' => 'وارد',
        'outgoing' => 'صادر',
        'internal' => 'داخلي',
    ],

    'table' => [
        'columns' => [
            'date'                 => 'التاريخ',
            'reference'            => 'المرجع',
            'product'              => 'المنتج',
            'package'              => 'الطرد',
            'lot'                  => 'الدفعة / الأرقام التسلسلية',
            'source-location'      => 'موقع المصدر',
            'destination-location' => 'موقع الوجهة',
            'quantity'             => 'الكمية',
            'unit'                 => 'الوحدة',
            'state'                => 'الحالة',
            'done-by'              => 'تم بواسطة',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف الحركة',
                    'body'  => 'تم حذف الحركة بنجاح.',
                ],
            ],
        ],
    ],
];
