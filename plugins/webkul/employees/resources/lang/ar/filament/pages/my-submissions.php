<?php

return [
    'navigation' => [
        'title' => 'صوتي',
    ],

    'form' => [
        'section' => [
            'title'       => 'شارك رأيك',
            'description' => 'رأيك مهم. جميع المراسلات تُراجع من قِبل الإدارة.',
        ],
        'info-note'   => 'سيتم مراجعة طلبك من قبل الموارد البشرية والإدارة.',
        'fields'      => [
            'type'                => 'النوع',
            'complaint'           => '💬 شكوى',
            'suggestion'          => '💡 اقتراح',
            'inquiry'             => '❓ استفسار',
            'feedback'            => '⭐ ملاحظة',
            'subject'             => 'الموضوع',
            'subject-placeholder' => 'ملخص موجز لطلبك',
            'body'                => 'التفاصيل',
            'body-placeholder'    => 'صف بالتفصيل...',
            'attachments'         => 'المرفقات (بحد أقصى 3، 5 ميجا لكل ملف)',
        ],
        'submit' => 'إرسال',
    ],

    'history' => [
        'title'       => 'طلباتي السابقة',
        'empty'       => [
            'title'       => 'لا توجد طلبات بعد',
            'description' => 'لم تقم بإرسال أي طلبات بعد. شارك رأيك أعلاه.',
        ],
    ],

    'types' => [
        'complaint'  => 'شكوى',
        'suggestion' => 'اقتراح',
        'inquiry'    => 'استفسار',
        'feedback'   => 'ملاحظة',
    ],

    'statuses' => [
        'open'         => 'مفتوح',
        'under_review' => 'قيد المراجعة',
        'resolved'     => 'تم الحل',
        'closed'       => 'مغلق',
    ],

    'modal' => [
        'attachments'  => 'المرفقات',
        'replies'      => 'الردود',
        'hr-team'      => 'فريق الموارد البشرية',
        'close'        => 'إغلاق',
        'timeline'     => [
            'title' => 'الجدول الزمني للحالة',
        ],
    ],

    'notifications' => [
        'no-employee' => [
            'title' => 'لم يتم العثور على سجل الموظف',
            'body'  => 'يرجى التواصل مع الموارد البشرية لربط حساب المستخدم الخاص بك.',
        ],
        'submitted' => [
            'title' => 'تم الإرسال بنجاح!',
            'body'  => 'رقم طلبك هو :ticket',
        ],
    ],
];
