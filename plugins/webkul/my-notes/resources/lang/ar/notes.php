<?php

return [
    'install' => [
        'success' => 'تم تثبيت إضافة مذكرتي بنجاح.',
    ],

    'navigation' => [
        'label' => 'مذكرتي',
        'group' => 'مذكرتي',
    ],

    'topbar' => [
        'quick_add'           => 'مذكرة سريعة',
        'heading'             => 'إضافة مذكرة',
        'capture'             => 'حفظ مذكرة سريعة',
        'capture_placeholder' => 'سجل فكرة سريعة…',
        'open_notes'          => 'فتح مذكرتي',
        'new_by_type'         => 'إنشاء حسب النوع',
    ],

    'types' => [
        'text'      => 'نص',
        'checklist' => 'قائمة مهام',
        'reminder'  => 'تذكير',
        'voice'     => 'صوت',
    ],

    'colors' => [
        'default' => 'افتراضي',
        'red'     => 'أحمر',
        'orange'  => 'برتقالي',
        'yellow'  => 'أصفر',
        'green'   => 'أخضر',
        'teal'    => 'فيروزي',
        'blue'    => 'أزرق',
        'purple'  => 'بنفسجي',
        'pink'    => 'وردي',
        'gray'    => 'رمادي',
    ],

    'form' => [
        'fields' => [
            'type'                => 'النوع',
            'color'               => 'اللون',
            'title'               => 'العنوان',
            'body'                => 'المحتوى',
            'checklist_items'     => 'قائمة المهام',
            'item_content'        => 'عنصر',
            'is_checked'          => 'مكتمل',
            'reminder_at'         => 'ذكرني في',
            'audio_path'          => 'مذكرة صوتية',
            'audio_transcription' => 'النص المكتوب',
            'tags'                => 'الوسوم',
            'link_meeting'        => 'ربط باجتماع',
            'link_project'        => 'ربط بمشروع',
            'link_correspondence' => 'ربط بمراسلة',
            'board_status'        => 'عمود اللوحة',
            'is_pinned'           => 'تثبيت المذكرة',
        ],
    ],

    'actions' => [
        'new_note'       => 'مذكرة جديدة',
        'edit_note'      => 'تعديل المذكرة',
        'save'           => 'حفظ',
        'saving'         => 'جاري الحفظ…',
        'cancel'         => 'إلغاء',
        'delete'         => 'حذف',
        'pin'            => 'تثبيت',
        'unpin'          => 'إلغاء التثبيت',
        'archive'        => 'أرشفة',
        'unarchive'      => 'إلغاء الأرشفة',
        'edit'           => 'تعديل',
        'view'           => 'عرض الملاحظات',
        'add_item'       => 'إضافة عنصر',
        'confirm_delete' => 'حذف هذه المذكرة؟',
    ],

    'canvas' => [
        'showing' => ':count مذكرة',
    ],

    'card' => [
        'updated' => 'آخر تحديث :date',
    ],

    'toolbar' => [
        'create_heading'            => 'إنشاء',
        'browse_heading'            => 'بحث وتنظيم',
        'quick_capture'             => 'مذكرة سريعة',
        'quick_capture_placeholder' => 'سجل فكرة سريعة…',
        'new_note'                  => 'مذكرة جديدة',
        'search_placeholder'        => 'ابحث في الملاحظات…',
        'sort'                      => 'ترتيب',
        'view'                      => 'عرض',
        'filter'                    => 'تصفية',
    ],

    'filters' => [
        'all'      => 'الكل',
        'pinned'   => 'المثبتة',
        'archived' => 'المؤرشفة',
        'other'    => 'أخرى',
    ],

    'sort' => [
        'newest'       => 'الأحدث',
        'oldest'       => 'الأقدم',
        'title'        => 'العنوان (أ–ي)',
        'reminder'     => 'تاريخ التذكير',
        'pinned_first' => 'المثبتة أولاً',
    ],

    'view_modes' => [
        'grid'     => 'شبكة',
        'list'     => 'قائمة',
        'board'    => 'لوحة',
        'calendar' => 'تقويم',
    ],

    'board_status' => [
        'inbox'        => 'الوارد',
        'in_progress'  => 'قيد التنفيذ',
        'waiting'      => 'في الانتظار',
        'done'         => 'منجز',
    ],

    'board' => [
        'empty_column' => 'أضف مذكرات هنا',
    ],

    'reminder_presets' => [
        'in_one_hour'  => 'بعد ساعة',
        'tomorrow_9am' => 'غداً الساعة 9 صباحاً',
        'next_monday'  => 'الإثنين القادم',
    ],

    'reminder_status' => [
        'upcoming' => 'قادم',
        'overdue'  => 'متأخر',
        'sent'     => 'مرسل',
    ],

    'voice' => [
        'record'            => 'تسجيل',
        'record_hint'       => 'سجّل مذكرة صوتية أو ارفع ملفاً من الأسفل.',
        'stop'              => 'إيقاف',
        'discard'           => 'تجاهل',
        'not_supported'     => 'تسجيل الصوت غير مدعوم في هذا المتصفح.',
        'permission_denied' => 'تم رفض إذن الميكروفون.',
        'upload_failed'     => 'تعذر إرفاق التسجيل. حاول رفع ملف صوتي بدلاً من ذلك.',
    ],

    'empty_state' => [
        'heading'              => 'ملاحظاتك هنا',
        'description'          => 'ابدأ بتسجيل الأفكار والمهام والتذكيرات.',
        'filtered_description' => 'لا توجد ملاحظات تطابق هذا الفلتر.',
        'no_checklist_items'   => 'لا توجد عناصر في القائمة بعد.',
    ],

    'calendar' => [
        'empty' => 'لا توجد تذكيرات لعرضها.',
    ],

    'more_items' => '+:count المزيد',

    'notifications' => [
        'saved'      => 'تم حفظ المذكرة',
        'deleted'    => 'تم حذف المذكرة',
        'archived'   => 'تمت أرشفة المذكرة',
        'unarchived' => 'تم إلغاء أرشفة المذكرة',
    ],

    'notify' => [
        'reminder_title' => 'تذكير: :title',
        'reminder_body'  => ':time — :body',
    ],

    'widget' => [
        'board_heading'         => 'مذكرتي',
        'upcoming_reminders'    => 'التذكيرات القادمة',
        'no_upcoming_reminders' => 'لا توجد تذكيرات قادمة',
        'no_pinned'             => 'لا توجد مذكرات مثبتة',
    ],

    'auto_title' => [
        'untitled'  => 'مذكرة بدون عنوان',
        'reminder'  => 'تذكير: :date',
        'checklist' => ':done/:total عناصر مكتملة',
    ],
];
