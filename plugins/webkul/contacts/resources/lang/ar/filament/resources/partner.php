<?php

return [
    'navigation' => [
        'title' => 'جهات الاتصال',
        'group' => 'جهات الاتصال',
    ],

    'model' => [
        'single' => 'جهة اتصال',
    ],

    'relations' => [
        'contacts'  => 'جهات الاتصال',
        'addresses' => 'العناوين',
    ],

    'pages' => [
        'manage-addresses' => [
            'title'         => 'عناوين :name',
            'create-modal'  => [
                'heading' => 'إضافة عنوان',
            ],
            'empty'       => [
                'heading'     => 'لا توجد عناوين',
                'description' => 'أضف عنواناً للبدء.',
            ],
        ],
        'manage-contacts' => [
            'title'       => 'جهات اتصال :name',
            'empty'       => [
                'heading'     => 'لا توجد جهات اتصال فرعية',
                'description' => 'أضف جهة اتصال للبدء.',
            ],
        ],
    ],

    'global-search' => [
        'project-manager' => 'مدير المشروع',
        'customer'        => 'العميل',
    ],
];
