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
        'tag_name'          => 'اسم الوسم',
        'tag_color'         => 'لون الوسم',
        'remove_password'   => 'إزالة كلمة المرور',
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
            'file'      => 'ملف المستند',
        ],
        'auto_generated' => 'يتم إنشاؤه تلقائياً',
        'file'           => [
            'create_help'         => 'ارفع المستند الذي سيتم حفظه في الأرشيف.',
            'replace_help'        => 'يبقى الملف الحالي فعالاً حتى ترفع ملفاً بديلاً. يتم الاحتفاظ بالإصدارات السابقة تلقائياً.',
            'current'             => 'الملف الحالي',
            'replace_label'       => 'استبدال بملف جديد',
            'replace_upload_help' => 'اختياري. اتركه فارغاً للاحتفاظ بالملف الحالي دون تغيير.',
        ],
        'access' => [
            'private_help'       => 'الملفات الخاصة مرئية فقط لمنشئها والمستخدمين الذين لديهم صلاحية كاملة على الأرشيف.',
            'password_status'    => 'حماية كلمة المرور',
            'password_enabled'   => 'محمي — المعاينة والتحميل تتطلب كلمة مرور',
            'password_disabled'  => 'غير محمي',
            'password_help'      => 'عيّن كلمة مرور لطلب فتح المستند قبل المعاينة أو التحميل. تبقى الجلسة مفتوحة لمدة 30 دقيقة بعد إدخال كلمة المرور الصحيحة.',
            'yes'                => 'نعم',
            'no'                 => 'لا',
        ],
    ],

    'expiry' => [
        'expired_title'       => 'انتهت صلاحية هذا المستند',
        'expired_body'        => 'تاريخ الانتهاء كان :date. راجع المستند أو جدده.',
        'expiring_soon_title' => 'هذا المستند ينتهي قريباً',
        'expiring_soon_body'  => 'ينتهي في :date (متبقي :days أيام).',
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
        'upload'   => 'رفع',
        'restore'  => 'استعادة',
        'view'     => 'عرض التفاصيل',
    ],

    'tags' => [
        'empty'             => 'لا توجد وسوم',
        'add'               => 'إضافة وسوم',
        'manage'            => 'إدارة الوسوم',
        'saved'             => 'تم تحديث الوسوم',
        'placeholder'       => 'اختر أو أنشئ وسوماً…',
        'advanced'          => 'خيارات الوسوم المتقدمة',
        'advanced_help'     => 'تخصيص ألوان الوسوم يدوياً. يمكن لمعظم المستخدمين تجاهل هذا واستخدام محدد الوسوم أعلاه.',
        'custom_colors'     => 'ألوان الوسوم المخصصة',
        'select_help'       => 'اختر وسوماً موجودة أو استخدم + لإنشاء وسم جديد بلون اختياري.',
        'select_help_short' => 'ابحث عن وسوم أو اضغط + لإضافة وسم جديد.',
    ],

    'dashboard' => [
        'page_title' => 'لوحة أرشيف المستندات',
        'stats'      => [
            'total_files'           => 'إجمالي الملفات',
            'total_storage'         => 'إجمالي التخزين',
            'expiring_soon'         => 'تنتهي قريباً',
            'expiring_soon_heading' => 'تنتهي خلال :days أيام',
            'expires_within_days'   => 'تنتهي خلال :days أيام',
            'view_all_expiring'     => 'عرض جميع المستندات المنتهية قريباً',
            'recent_uploads'        => 'أحدث الرفع',
        ],
        'empty' => [
            'recent_uploads' => 'لا توجد رفوعات حديثة',
            'expiring_soon'  => 'لا توجد مستندات تنتهي قريباً',
        ],
        'charts' => [
            'top_tags'          => 'أكثر الوسوم استخداماً',
            'storage_by_folder' => 'التخزين حسب المجلد',
            'files_count'       => 'الملفات',
            'storage'           => 'التخزين (بايت)',
            'empty'             => 'لا توجد بيانات بعد',
            'largest_folder'    => 'أكبر مجلد: :size',
        ],
    ],

    'preview' => [
        'close'          => 'إغلاق',
        'file_not_found' => 'الملف غير موجود على القرص',
        'no_preview'     => 'المعاينة غير متاحة لهذا النوع من الملفات.',
        'loading'        => 'جاري تحميل المعاينة...',
    ],

    'manager' => [
        'title'               => 'إدارة المستندات',
        'folders'             => 'المجلدات',
        'search'              => 'بحث في الملفات...',
        'empty'               => 'لا توجد ملفات في هذا المجلد',
        'no_results'          => 'لم يتم العثور على ملفات',
        'items'               => ':count عناصر',
        'all_files'           => 'جميع الملفات',
        'root'                => 'الجذر',
        'filters'             => 'التصفية',
        'filter_tag'          => 'تصفية حسب الوسم',
        'filter_privacy'      => 'الخصوصية',
        'public_only'         => 'العامة فقط',
        'reset_filters'       => 'إعادة ضبط التصفية',
        'include_subfolders'  => 'تضمين المجلدات الفرعية',
        'view_grid'           => 'عرض شبكي',
        'view_list'           => 'عرض جدول',
        'view_explorer'       => 'عرض مستكشف',
        'subfolders'          => 'المجلدات الفرعية',
        'details'             => 'التفاصيل',
        'select_file'         => 'اختر ملفاً لعرض التفاصيل',
        'actions'             => [
            'label' => 'الإجراءات',
            'more'  => 'المزيد من الإجراءات',
        ],
    ],

    'password' => [
        'title'   => 'كلمة المرور مطلوبة',
        'body'    => 'أدخل كلمة المرور لفتح :name',
        'submit'  => 'فتح',
        'invalid' => 'كلمة المرور غير صحيحة.',
    ],

    'missing_file' => [
        'title' => 'الملف غير موجود على القرص',
        'body'  => 'سجل الملف :name موجود، لكن الملف المخزّن مفقود. يرجى إعادة رفع المستند.',
        'back'  => 'رجوع',
    ],

    'permissions' => [
        'title'      => 'صلاحيات المجلد',
        'type'       => 'النوع',
        'user'       => 'المستخدم',
        'role'       => 'الدور',
        'permission' => 'مستوى الصلاحية',
        'types'      => [
            'user' => 'مستخدم',
            'role' => 'دور',
        ],
        'levels' => [
            'view'   => 'عرض',
            'upload' => 'رفع',
            'manage' => 'إدارة',
        ],
    ],

    'validation' => [
        'invalid_extension' => 'نوع الملف غير مسموح.',
        'file_too_large'    => 'حجم الملف يتجاوز الحد الأقصى :max ميجابايت.',
        'upload_missing'    => 'تعذر العثور على الملف المرفوع.',
    ],

    'share' => [
        'expired_title'        => 'رابط المشاركة منتهي',
        'expired_body'         => 'هذا الرابط لم يعد صالحاً.',
        'shared_with_email'    => 'مشاركة عبر البريد',
        'view_once'            => 'عرض مرة واحدة',
        'expires_at'           => 'ينتهي في',
        'created_title'        => 'تم إنشاء رابط المشاركة',
        'created_body'         => 'انسخ هذا الرابط: :url',
        'email_subject'        => 'مستند مشترك :reference',
        'email_heading'        => 'تمت مشاركة المستند: :name',
        'email_body'           => 'تمت مشاركة المستند :reference معك.',
        'email_button'         => 'فتح المستند',
        'email_view_once_note' => 'يمكن استخدام هذا الرابط للعرض مرة واحدة فقط.',
        'email_expires'        => 'ينتهي هذا الرابط في :date.',
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
        'uploaded' => [
            'title' => 'تم رفع الملف',
            'body'  => 'تم رفع المستند :reference بنجاح.',
        ],
    ],
];
