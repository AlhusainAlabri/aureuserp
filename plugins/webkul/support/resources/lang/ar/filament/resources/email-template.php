<?php

return [
    'navigation' => [
        'title' => 'قوالب البريد الإلكتروني',
        'group' => 'الإعدادات',
    ],

    'form' => [
        'sections' => [
            'general' => 'تفاصيل القالب',
        ],
        'fields' => [
            'code'        => 'الرمز',
            'name'        => 'الاسم',
            'subject'     => 'الموضوع',
            'sender_name' => 'اسم المرسل',
            'layout'      => 'التخطيط',
            'is_active'   => 'نشط',
            'description' => 'الوصف',
            'content'     => 'المحتوى',
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'الاسم',
            'code'       => 'الرمز',
            'subject'    => 'الموضوع',
            'is_active'  => 'نشط',
            'updated_at' => 'تاريخ التحديث',
        ],
        'filters' => [
            'is_active' => 'نشط',
        ],
    ],

    'pages' => [
        'list' => [
            'create' => 'قالب جديد',
        ],
    ],

    'notifications' => [
        'created' => [
            'title' => 'تم إنشاء القالب',
            'body'  => 'تم إنشاء قالب البريد الإلكتروني بنجاح.',
        ],
        'updated' => [
            'title' => 'تم تحديث القالب',
            'body'  => 'تم تحديث قالب البريد الإلكتروني بنجاح.',
        ],
        'deleted' => [
            'title' => 'تم حذف القالب',
            'body'  => 'تم حذف قالب البريد الإلكتروني بنجاح.',
        ],
    ],
];
