<?php

return [
    'navigation' => [
        'title' => 'سجل البريد الإلكتروني',
        'group' => 'الإعدادات',
    ],

    'status' => [
        'queued' => 'في قائمة الانتظار',
        'sent'   => 'تم الإرسال',
        'failed' => 'فشل',
    ],

    'table' => [
        'columns' => [
            'recipient_email' => 'البريد الإلكتروني للمستلم',
            'recipient_name'  => 'اسم المستلم',
            'subject'         => 'الموضوع',
            'status'          => 'الحالة',
            'mail_class'      => 'فئة البريد',
            'sender'          => 'المرسل',
            'template'        => 'القالب',
            'sent_at'         => 'تاريخ الإرسال',
            'created_at'      => 'تاريخ الإنشاء',
            'error_message'   => 'رسالة الخطأ',
            'variables'       => 'المتغيرات',
        ],
        'filters' => [
            'status' => 'الحالة',
        ],
    ],

    'infolist' => [
        'sections' => [
            'details' => 'تفاصيل البريد',
        ],
    ],
];
