<?php

return [
    'install' => [
        'success' => 'تم تثبيت إضافة أرشيف المستندات بنجاح.',
    ],

    'navigation' => [
        'group'     => 'أرشيف المستندات',
        'documents' => [
            'label' => 'المستندات',
            'icon'  => 'heroicon-o-folder-open',
        ],
        'dashboard' => [
            'label' => 'لوحة التحكم',
            'icon'  => 'heroicon-o-squares-2x2',
        ],
        'folders' => [
            'label' => 'المجلدات',
            'icon'  => 'heroicon-o-folder',
        ],
        'files' => [
            'label' => 'الملفات',
            'icon'  => 'heroicon-o-document',
        ],
    ],

    'models' => [
        'folder' => 'مجلد',
        'file'   => 'ملف',
    ],

    'fields' => [
        'reference_number'  => 'الرقم المرجعي',
        'name'              => 'الاسم',
        'slug'              => 'المعرف',
        'description'       => 'الوصف',
        'parent'            => 'المجلد الأصلي',
        'color'             => 'اللون',
        'icon'              => 'الأيقونة',
        'is_private'        => 'خاص',
        'password'          => 'كلمة المرور',
        'sort_order'        => 'الترتيب',
        'folder'            => 'المجلد',
        'original_filename' => 'اسم الملف الأصلي',
        'file'              => 'الملف',
        'file_size'         => 'الحجم',
        'mime_type'         => 'نوع الملف',
        'extension'         => 'الامتداد',
        'tags'              => 'الوسوم',
        'tag_color'         => 'لون الوسم',
        'expiry_date'       => 'تاريخ الانتهاء',
        'version'           => 'الإصدار',
        'project'           => 'المشروع',
        'meeting'           => 'الاجتماع',
        'correspondence'    => 'المراسلة',
        'view_count'        => 'المشاهدات',
        'download_count'    => 'التحميلات',
        'creator'           => 'المنشئ',
        'company'           => 'الشركة',
        'created_at'        => 'تاريخ الإنشاء',
        'updated_at'        => 'تاريخ التحديث',
        'files_count'       => 'الملفات',
    ],

    'form' => [
        'sections' => [
            'general'   => 'عام',
            'metadata'  => 'بيانات وصفية',
            'access'    => 'التحكم بالوصول',
            'lifecycle' => 'دورة الحياة',
        ],
        'auto_generated' => 'يتم إنشاؤه تلقائياً',
    ],

    'table' => [
        'tabs' => [
            'all'           => 'الكل',
            'private'       => 'الخاصة',
            'expiring_soon' => 'تنتهي قريباً',
            'expired'       => 'منتهية',
        ],
    ],

    'actions' => [
        'preview'  => 'معاينة',
        'download' => 'تحميل',
        'share'    => 'مشاركة',
        'restore'  => 'استعادة',
    ],

    'dashboard' => [
        'stats' => [
            'total_files'    => 'إجمالي الملفات',
            'total_storage'  => 'إجمالي التخزين',
            'expiring_soon'  => 'تنتهي قريباً',
            'recent_uploads' => 'أحدث الرفع',
        ],
    ],

    'manager' => [
        'title'      => 'إدارة المستندات',
        'folders'    => 'المجلدات',
        'search'     => 'بحث في الملفات...',
        'empty'      => 'لا توجد ملفات في هذا المجلد',
        'no_results' => 'لم يتم العثور على ملفات',
        'items'      => ':count عناصر',
        'all_files'  => 'جميع الملفات',
        'root'       => 'الجذر',
    ],

    'share' => [
        'expired_title' => 'رابط المشاركة منتهي',
        'expired_body'  => 'هذا الرابط لم يعد صالحاً.',
    ],

    'activity' => [
        'uploaded'      => 'تم الرفع',
        'viewed'        => 'تم العرض',
        'downloaded'    => 'تم التحميل',
        'shared'        => 'تمت المشاركة',
        'renamed'       => 'تمت إعادة التسمية',
        'moved'         => 'تم النقل',
        'deleted'       => 'تم الحذف',
        'version_added' => 'تمت إضافة إصدار جديد',
    ],

    'commands' => [
        'archive_expired' => [
            'done' => 'تمت أرشفة :count مستندات منتهية الصلاحية.',
        ],
        'cleanup_share_links' => [
            'done' => 'تم إلغاء تفعيل :count رابط مشاركة منتهي.',
        ],
    ],

    'notifications' => [
        'archived' => [
            'title' => 'تمت أرشفة المستند',
            'body'  => 'تمت أرشفة المستند :reference لانتهاء صلاحيته.',
        ],
    ],
];
