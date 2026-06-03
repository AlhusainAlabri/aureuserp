<?php

return [
    'install' => [
        'success' => 'تم تثبيت إضافة الأصول بنجاح.',
    ],
    'all'        => 'الكل',
    'navigation' => [
        'group'  => 'إدارة الأصول',
        'assets' => 'الأصول',
    ],
    'models' => [
        'asset' => 'أصل',
    ],
    'statuses' => [
        'available'   => 'متاح',
        'borrowed'    => 'مُعار',
        'maintenance' => 'صيانة',
        'retired'     => 'متقاعد',
    ],
    'borrowing_statuses' => [
        'pending'           => 'قيد الانتظار',
        'pending_approval'  => 'بانتظار الموافقة',
        'active'            => 'نشط',
        'returned'          => 'مُرجَع',
        'overdue'           => 'متأخر',
        'rejected'          => 'مرفوض',
    ],
    'fields' => [
        'asset_number'     => 'رقم الأصل',
        'name'             => 'الاسم',
        'description'      => 'الوصف',
        'category'         => 'الفئة',
        'serial_number'    => 'الرقم التسلسلي',
        'status'           => 'الحالة',
        'value'            => 'القيمة (ر.ع.)',
        'location'         => 'الموقع',
        'purchased_at'     => 'تاريخ الشراء',
        'notes'            => 'ملاحظات',
        'employee'         => 'الموظف',
        'borrowed_by'      => 'مُعار إلى',
        'borrowed_at'      => 'تاريخ الإعارة',
        'due_at'           => 'تاريخ الاستحقاق',
        'returned_at'      => 'تاريخ الإرجاع',
        'borrowing_status' => 'حالة الإعارة',
        'processed_by'     => 'تمت المعالجة بواسطة',
        'employee_search'  => 'البحث عن الموظفين',
    ],
    'form' => [
        'auto_generated' => 'يُنشأ تلقائياً عند الحفظ',
        'sections'       => [
            'details' => 'تفاصيل الأصل',
        ],
    ],
    'infolist' => [
        'sections' => [
            'details' => 'تفاصيل الأصل',
        ],
    ],
    'pages' => [
        'create_title' => 'إنشاء أصل',
        'edit_title'   => 'تعديل :name',
        'view_title'   => ':name',
    ],
    'actions' => [
        'create'              => 'إنشاء أصل',
        'borrow'              => 'إعارة الأصل',
        'return'              => 'إرجاع الأصل',
        'return_confirmation' => 'هل تريد تأكيد إرجاع هذا الأصل وجعله متاحاً؟',
        'view'                => 'عرض',
    ],
    'relations' => [
        'borrowings' => 'سجل الإعارات',
    ],
    'empty' => [
        'no_assets'                 => 'لا توجد أصول بعد',
        'no_assets_description'     => 'أنشئ أول سجل أصل لبدء تتبع العناصر المادية.',
        'no_borrowings'             => 'لا توجد سجلات إعارة',
        'no_borrowings_description' => 'سيظهر سجل الإعارات هنا بعد إعارة الأصل لموظف.',
    ],
    'notifications' => [
        'borrowed' => [
            'title' => 'تمت إعارة الأصل',
            'body'  => 'تم تخصيص :name لموظف.',
        ],
        'returned' => [
            'title' => 'تم إرجاع الأصل',
            'body'  => 'تم إرجاع :name وهو متاح الآن.',
        ],
        'no_active_borrowing' => [
            'title' => 'لا توجد إعارة نشطة',
        ],
        'overdue' => [
            'title' => 'إعارة أصل متأخرة',
            'body'  => 'الأصل :name (:number) المعَار إلى :employee كان مستحقاً في :due_at.',
        ],
        'request_submitted' => [
            'title' => 'تم تقديم طلب إعارة',
            'body'  => 'قدّم :employee طلباً لإعارة :name.',
        ],
        'request_approved' => [
            'title' => 'تمت الموافقة على طلب الإعارة',
            'body'  => 'تمت الموافقة على طلبك لإعارة :name.',
        ],
        'request_rejected' => [
            'title' => 'تم رفض طلب الإعارة',
            'body'  => 'تم رفض طلبك لإعارة :name.',
        ],
    ],
    'commands' => [
        'overdue' => [
            'done'          => 'تم إرسال إشعارات لـ :count إعارة متأخرة.',
            'table_missing' => 'جدول asset_borrowings غير موجود.',
        ],
    ],
    'widgets' => [
        'stats' => [
            'unavailable'          => 'الأصول',
            'plugin_not_installed' => 'إضافة الأصول غير مثبتة.',
            'available'            => 'متاح',
            'available_hint'       => 'جاهز للإعارة',
            'borrowed'             => 'مُعار',
            'borrowed_hint'        => 'مُخصص حالياً',
            'overdue'              => 'متأخر',
            'overdue_hint'         => 'تجاوز تاريخ الاستحقاق',
        ],
    ],
];
