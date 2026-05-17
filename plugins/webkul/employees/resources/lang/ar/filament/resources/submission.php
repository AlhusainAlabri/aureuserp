<?php

return [
    'navigation' => [
        'title' => 'المراسلات',
        'group' => 'الموارد البشرية',
    ],

    'global-search' => [
        'type'     => 'النوع',
        'status'   => 'الحالة',
        'employee' => 'الموظف',
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

    'priorities' => [
        'low'    => 'منخفض',
        'medium' => 'متوسط',
        'high'   => 'عالي',
    ],

    'tabs' => [
        'all'          => 'الكل',
        'open'         => 'مفتوح',
        'under_review' => 'قيد المراجعة',
        'resolved'     => 'تم الحل',
        'closed'       => 'مغلق',
    ],

    'table' => [
        'columns' => [
            'ticket-number' => 'رقم الطلب',
            'type'          => 'النوع',
            'subject'       => 'الموضوع',
            'submitter'     => 'المرسل',
            'department'    => 'الدائرة',
            'status'        => 'الحالة',
            'priority'      => 'الأولوية',
            'replies'       => 'الردود',
            'created-at'    => 'تاريخ الإرسال',
        ],
        'filters' => [
            'type'       => 'النوع',
            'status'     => 'الحالة',
            'priority'   => 'الأولوية',
            'department' => 'الدائرة',
            'no-replies' => 'بلا ردود',
        ],
        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف المراسلة',
                    'body'  => 'تم حذف المراسلة بنجاح.',
                ],
            ],
        ],
        'bulk-actions' => [
            'mark-under-review' => 'وضع قيد المراجعة',
            'mark-resolved'     => 'وضع محلول',
            'mark-closed'       => 'إغلاق',
            'delete'            => [
                'notification' => [
                    'title' => 'تم حذف المراسلات',
                    'body'  => 'تم حذف المراسلات بنجاح.',
                ],
            ],
        ],
    ],

    'form' => [
        'fields' => [
            'status'   => 'الحالة',
            'priority' => 'الأولوية',
        ],
    ],

    'infolist' => [
        'sections' => [
            'details' => [
                'title'   => 'تفاصيل المراسلة',
                'entries' => [
                    'ticket-number' => 'رقم الطلب',
                    'type'          => 'النوع',
                    'priority'      => 'الأولوية',
                    'subject'       => 'الموضوع',
                    'body'          => 'المحتوى',
                    'submitter'     => 'المرسل',
                    'department'    => 'الدائرة',
                    'created-at'    => 'تاريخ الإرسال',
                    'status'        => 'الحالة',
                ],
            ],
        ],
    ],

    'pages' => [
        'view-submission' => [
            'actions' => [
                'change-status'    => 'تغيير الحالة',
                'set-priority'     => 'تعيين الأولوية',
                'delete'           => 'حذف',
                'mark-under-review'=> 'وضع قيد المراجعة',
                'mark-resolved'    => 'وضع محلول',
                'close-ticket'     => 'إغلاق الطلب',
            ],
            'notifications' => [
                'status-updated' => [
                    'title' => 'تم تحديث الحالة',
                    'body'  => 'تم تحديث حالة المراسلة.',
                ],
                'priority-updated' => [
                    'title' => 'تم تحديث الأولوية',
                    'body'  => 'تم تحديث أولوية المراسلة.',
                ],
                'reply-sent' => [
                    'title' => 'تم إرسال الرد',
                    'body'  => 'تم إرسال ردك بنجاح.',
                ],
            ],
            'sections' => [
                'details'       => 'تفاصيل المراسلة',
                'replies'       => 'سجل الردود',
                'info'          => 'المعلومات',
                'timeline'      => 'الجدول الزمني',
                'quick-actions' => 'إجراءات سريعة',
            ],
            'attachments'         => 'المرفقات',
            'internal-note-label' => 'ملاحظة داخلية — غير مرئية للموظف',
            'hr-team'             => 'فريق الموارد البشرية',
            'no-replies'          => 'لا توجد ردود بعد.',
            'reply-placeholder'   => 'اكتب ردك...',
            'internal-toggle'     => 'ملاحظة داخلية (غير مرئية للموظف)',
            'send-reply'          => 'إرسال الرد',
        ],
    ],

    'notifications' => [
        'new-submission' => [
            'title' => 'تم استلام :type جديد',
            'body'  => 'طلب :ticket — :subject',
        ],
        'reply' => [
            'title' => 'تم استلام رد على طلبك',
            'body'  => 'طلب :ticket — :subject',
        ],
        'resolved' => [
            'title' => 'تم حل طلبك',
            'body'  => 'طلب :ticket محلول.',
        ],
        'unresolved' => [
            'title' => ':count طلبات مفتوحة',
            'body'  => 'الأقدم: :ticket — :days يوم',
        ],
    ],
];
