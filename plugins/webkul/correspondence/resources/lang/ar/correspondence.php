<?php

return [
    'correspondence'      => 'مراسلة',
    'correspondences'     => 'المراسلات',
    'outgoing'            => 'الصادر',
    'incoming'            => 'الوارد',
    'reference_number'    => 'رقم المرجع',
    'direction'           => 'الاتجاه',
    'subject'             => 'الموضوع',
    'body'                => 'المحتوى',
    'sender_name'         => 'اسم المرسِل',
    'sender_entity'       => 'الجهة المرسِلة',
    'external_entity'     => 'الجهة المرسَل إليها',
    'from_department'     => 'من دائرة',
    'to_department'       => 'إلى دائرة',
    'to_user'             => 'إلى موظف',
    'recipient'           => 'المستلم',
    'to_external_email'   => 'البريد الخارجي',
    'received_at'         => 'تاريخ الاستلام',
    'sent_at'             => 'تاريخ الإرسال',
    'due_date'            => 'الموعد النهائي',
    'project'             => 'المشروع',
    'meeting'             => 'الاجتماع',
    'purchase_request'    => 'طلب الشراء',
    'reply'               => 'رد على المراسلة',
    'reply_subject'       => 'رداً على: :subject',
    'thread'              => 'سلسلة المراسلات',
    'followers'           => 'المتابعون',
    'send_correspondence' => 'إرسال',
    'email_sent'          => 'تم إرسال البريد الإلكتروني',
    'email_failed'        => 'فشل إرسال البريد الإلكتروني — يرجى المحاولة لاحقاً',
    'overdue'             => 'متأخرة',
    'attachments'         => 'المرفقات',
    'details'             => 'التفاصيل',
    'date'                => 'التاريخ',
    'creator'             => 'المنشئ',
    'user'                => 'المستخدم',
    'file'                => 'الملف',
    'file_name'           => 'اسم الملف',
    'file_size'           => 'حجم الملف',
    'mime_type'           => 'نوع الملف',
    'yes'                 => 'نعم',
    'no'                  => 'لا',

    'navigation' => [
        'group'     => 'المراسلات',
        'dashboard' => 'لوحة المراسلات',
    ],

    'directions' => [
        'outgoing' => 'صادر',
        'incoming' => 'وارد',
    ],

    'type' => [
        'label'    => 'النوع',
        'official' => 'رسمي',
        'internal' => 'داخلي',
        'external' => 'خارجي',
    ],

    'types' => [
        'official' => 'رسمي',
        'internal' => 'داخلي',
        'external' => 'خارجي',
    ],

    'priority' => [
        'label'        => 'الأولوية',
        'normal'       => 'عادي',
        'urgent'       => 'عاجل',
        'confidential' => 'سري',
    ],

    'priorities' => [
        'normal'       => 'عادي',
        'urgent'       => 'عاجل',
        'confidential' => 'سري',
    ],

    'status' => [
        'label'            => 'الحالة',
        'draft'            => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved'         => 'معتمد',
        'sent'             => 'مرسل',
        'received'         => 'مستلم',
        'archived'         => 'مؤرشف',
    ],

    'statuses' => [
        'draft'            => 'مسودة',
        'pending_approval' => 'بانتظار الموافقة',
        'approved'         => 'معتمد',
        'sent'             => 'مرسل',
        'received'         => 'مستلم',
        'archived'         => 'مؤرشف',
    ],

    'form' => [
        'sections' => [
            'type'    => 'نوع المراسلة',
            'parties' => 'الجهات',
            'content' => 'محتوى المراسلة',
            'links'   => 'الربط',
        ],
    ],

    'filters' => [
        'from'           => 'من',
        'until'          => 'إلى',
        'received_from'  => 'تاريخ الاستلام من',
        'received_until' => 'تاريخ الاستلام إلى',
        'due_from'       => 'الموعد النهائي من',
        'due_until'      => 'الموعد النهائي إلى',
    ],

    'actions' => [
        'view'        => 'عرض',
        'archive'     => 'أرشفة',
        'unarchive'   => 'إلغاء الأرشفة',
        'download'    => 'تنزيل',
        'export_pdf'  => 'تصدير PDF',
        'create_task' => 'إنشاء مهمة متابعة',
    ],

    'departments' => [
        'navigation'           => 'الدوائر',
        'model'                => 'دائرة',
        'plural'               => 'الدوائر',
        'section'              => 'بيانات الدائرة',
        'name'                 => 'الاسم',
        'code'                 => 'الرمز',
        'manager'              => 'المدير',
        'company'              => 'الشركة',
        'employees_department' => 'دائرة الموارد البشرية المرتبطة',
    ],

    'tasks' => [
        'navigation' => 'مهام المتابعة',
        'create'     => 'إنشاء مهمة',
        'title'      => 'عنوان المهمة',
        'deadline'   => 'الموعد النهائي',
        'assignee'   => 'المكلف',
        'created'    => 'تم إنشاء مهمة المتابعة',
        'empty'      => 'لا توجد مهام متابعة مرتبطة بالمراسلات بعد.',
    ],

    'tabs' => [
        'archived' => 'الأرشيف',
    ],

    'relations' => [
        'approvals'                 => 'سجل الموافقات',
        'project_correspondences'   => 'المراسلات المرتبطة',
        'meeting_correspondences'   => 'المراسلات المرتبطة',
    ],

    'approvals' => [
        'default_flow' => 'مسار اعتماد المراسلات الصادرة',
        'steps'        => [
            'department_manager' => 'مدير الدائرة',
            'admin_manager'      => 'مدير الإدارة',
        ],
    ],

    'notify' => [
        'submitted' => [
            'title' => 'مراسلة صادرة بانتظار موافقتك',
            'body'  => ':reference — :subject',
        ],
        'approved' => [
            'title' => 'تمت الموافقة على المراسلة',
            'body'  => ':reference جاهزة للإرسال',
        ],
        'rejected' => [
            'title'     => 'تم رفض المراسلة',
            'body'      => ':reference — :reason',
            'no_reason' => 'بدون سبب',
        ],
        'sent' => [
            'title' => 'تم إرسال المراسلة',
            'body'  => ':reference أُرسلت إلى :target',
        ],
        'received' => [
            'title' => 'مراسلة واردة جديدة',
            'body'  => 'من: :sender — :subject',
        ],
        'overdue' => [
            'title' => 'مراسلة متأخرة',
            'body'  => ':reference — تجاوزت الموعد النهائي :date',
        ],
        'reply' => [
            'title' => 'رد جديد على مراسلتك',
            'body'  => ':reference — :subject',
        ],
    ],

    'empty' => [
        'no_records'             => 'لا توجد مراسلات',
        'no_records_description' => 'ستظهر المراسلات هنا عند توفرها.',
    ],

    'dashboard' => [
        'stats' => [
            'outgoing_month'   => 'إجمالي الصادر هذا الشهر',
            'incoming_month'   => 'إجمالي الوارد هذا الشهر',
            'pending_approval' => 'بانتظار الموافقة',
            'overdue'          => 'مراسلات متأخرة',
            'my_approvals'     => 'موافقاتي المعلقة',
        ],
        'sections' => [
            'incoming'         => 'آخر الوارد',
            'pending_outgoing' => 'الصادر المعلق',
            'my_approvals'     => 'موافقاتي المعلقة',
            'urgent'           => 'المراسلات العاجلة',
        ],
    ],

    'pdf' => [
        'official_title' => 'مراسلة رسمية',
        'internal_title' => 'مراسلة داخلية',
        'signature'      => 'توقيع المرسِل / ختم الجهة',
        'created_by'     => 'تم الإنشاء بواسطة :user بتاريخ :date',
    ],

    'exceptions' => [
        'send_before_approval' => 'لا يمكن إرسال المراسلة قبل اكتمال الموافقات',
        'task_create_failed'   => 'تعذر إنشاء مهمة المتابعة. تحقق من وجود مرحلة مهام.',
    ],

    'commands' => [
        'overdue_complete' => 'تم إرسال تنبيهات المراسلات المتأخرة.',
    ],

    'install' => [
        'success' => 'تم تثبيت إضافة المراسلات بنجاح.',
    ],
];
