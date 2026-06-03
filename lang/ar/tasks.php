<?php

return [
    'navigation' => [
        'hub'      => 'مركز المهام',
        'kanban'   => 'لوحة كانبان',
        'calendar' => 'تقويم العمليات',
        'group'    => 'المشاريع',
    ],

    'hub' => [
        'title'          => 'مركز عمليات المهام',
        'subheading'     => 'إدارة المهام والمواعيد النهائية وحمل العمل في مكان واحد.',
        'view_list'      => 'قائمة',
        'view_kanban'    => 'كانبان',
        'view_calendar'  => 'تقويم',
        'quick_create'   => 'مهمة جديدة',
        'view_all_tasks' => 'جميع المهام',
    ],

    'filters' => [
        'title'              => 'الفلاتر',
        'employee'           => 'الموظف',
        'department'         => 'القسم',
        'project'            => 'المشروع',
        'category'           => 'التصنيف',
        'status'             => 'الحالة',
        'priority'           => 'الأولوية',
        'clear'              => 'مسح الفلاتر',
        'show_project_tasks' => 'مهام المشاريع',
        'show_meetings'      => 'الاجتماعات',
        'show_meeting_tasks' => 'مهام الاجتماعات',
        'show_leave'         => 'الإجازات',
        'show_milestones'    => 'المعالم',
        'show_holidays'      => 'العطل الرسمية',
    ],

    'fields' => [
        'start_date'     => 'تاريخ البدء',
        'completed_at'   => 'تاريخ الإنجاز',
        'owner'          => 'المسؤول',
        'category'       => 'التصنيف',
        'department'     => 'القسم',
        'priority_level' => 'الأولوية',
    ],

    'priority' => [
        'low'    => 'منخفضة',
        'medium' => 'متوسطة',
        'high'   => 'عالية',
        'urgent' => 'عاجلة',
    ],

    'state' => [
        'pending'     => 'قيد الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'on_hold'     => 'معلّقة',
        'completed'   => 'مكتملة',
        'cancelled'   => 'ملغاة',
    ],

    'stats' => [
        'open'           => 'المهام المفتوحة',
        'overdue'        => 'متأخرة',
        'due_today'      => 'مستحقة اليوم',
        'completed_week' => 'مكتملة هذا الأسبوع',
        'by_status'      => 'المهام حسب الحالة',
        'workload'       => 'حمل العمل حسب الموظف',
    ],

    'kanban' => [
        'title'        => 'لوحة كانبان',
        'subheading'   => 'اسحب المهام بين المراحل لتحديث سير العمل.',
        'empty_column' => 'اسحب مهمة هنا',
        'no_stages'    => 'لم يتم إعداد مراحل المهام بعد.',
        'overdue'      => 'متأخرة',
    ],

    'calendar' => [
        'title'        => 'تقويم العمليات',
        'subheading'   => 'المهام والاجتماعات والإجازات والمعالم في عرض واحد.',
        'legend'       => 'دليل الألوان',
        'project_task' => 'مهمة مشروع',
        'meeting'      => 'اجتماع',
        'meeting_task' => 'مهمة اجتماع',
        'leave'        => 'إجازة',
        'milestone'    => 'معلم',
        'holiday'      => 'عطلة رسمية',
    ],

    'actions' => [
        'archive'         => 'أرشفة المهمة',
        'archive_confirm' => 'أرشفة هذه المهمة؟ ستُخفى من القوائم النشطة مع الاحتفاظ بها للتقارير.',
        'archived'        => 'تمت أرشفة المهمة',
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'مهمة جديدة',
            'body'  => 'تم تعيينك في: :title',
        ],
        'status_changed' => [
            'title' => 'تحديث حالة المهمة',
            'body'  => ':title أصبحت :status',
        ],
        'deadline' => [
            'title' => 'اقتراب موعد المهمة',
            'body'  => ':title مستحقة في :date',
        ],
        'overdue' => [
            'title' => 'مهمة متأخرة',
            'body'  => ':title متأخرة',
        ],
        'task_created' => [
            'title' => 'تم إنشاء المهمة',
            'body'  => 'تم إنشاء المهمة بنجاح.',
        ],
    ],

    'mail' => [
        'deadline' => [
            'subject' => 'موعد مهمة: :title',
            'heading' => 'تذكير بموعد مهمة',
            'intro'   => 'مرحباً :name، هذا تذكير بموعد مهمة قادمة.',
        ],
        'overdue' => [
            'subject' => 'مهمة متأخرة: :title',
            'heading' => 'تذكير بمهمة متأخرة',
            'intro'   => 'مرحباً :name، المهمة التالية متأخرة.',
        ],
        'status'         => 'الحالة',
        'deadline_label' => 'تاريخ الاستحقاق',
    ],

    'columns' => [
        'owner'           => 'المسؤول',
        'category'        => 'التصنيف',
        'department'      => 'القسم',
        'priority_level'  => 'مستوى الأولوية',
        'featured'        => 'مميزة',
        'start_date'      => 'البدء',
        'overdue'         => 'متأخرة',
    ],

    'empty' => [
        'no_records'                      => 'لا توجد مهام',
        'no_records_description'          => 'أنشئ مهمة جديدة أو غيّر عرض الجدول لعرض السجلات.',
        'no_workload'                     => 'لا يوجد حمل عمل',
        'no_workload_description'         => 'لا توجد مهام مفتوحة مسندة إلى موظفين حالياً.',
        'no_open_tasks_chart'             => 'لا توجد مهام مفتوحة',
        'no_open_tasks_chart_description' => 'لا توجد مهام مفتوحة حالياً لعرضها حسب الحالة.',
    ],
];
