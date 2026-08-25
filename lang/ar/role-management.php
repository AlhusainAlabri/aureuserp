<?php

return [
    'starting_point' => [
        'label'    => 'نقطة البداية',
        'blank'    => 'دور فارغ',
        'template' => 'قالب دور',
        'clone'    => 'نسخ دور موجود',
    ],

    'fields' => [
        'role_template'        => 'قالب الدور',
        'clone_role_id'        => 'نسخ من دور',
        'module_access'        => 'صلاحيات الوحدات',
        'advanced_editor_open' => 'فتح محرر الصلاحيات المتقدم',
        'grant_all_modules'    => 'منح وصول كامل لجميع الوحدات',
    ],

    'sections' => [
        'basics'    => 'بيانات الدور',
        'starting'  => 'نقطة البداية',
        'modules'   => 'صلاحيات الوحدات',
        'summary'   => 'ملخص الصلاحيات',
        'advanced'  => 'محرر الصلاحيات المتقدم',
        'review'    => 'مراجعة',
    ],

    'wizard' => [
        'details' => 'التفاصيل',
        'access'  => 'الصلاحيات',
        'review'  => 'المراجعة',
    ],

    'module_access' => [
        'levels' => [
            'none'     => 'بدون',
            'view'     => 'عرض فقط',
            'standard' => 'قياسي (عرض + إنشاء + تعديل)',
            'full'     => 'وصول كامل',
        ],
        'selected_count'      => ':selected / :total صلاحية',
        'modules_with_access' => ':count من :total وحدة بها صلاحيات',
        'reset_template'      => 'إعادة تعيين إلى إعدادات القالب',
    ],

    'summary' => [
        'permissions' => ':selected من :total صلاحية محددة',
        'modules'     => ':count من :total وحدة بها صلاحيات',
        'no_modules'  => 'لم يتم ضبط صلاحيات الوحدات بعد.',
    ],

    'templates' => [
        'employee' => [
            'label'       => 'موظف',
            'description' => 'خدمات الموارد البشرية الذاتية والطلبات والأصول الشخصية.',
        ],
        'manager' => [
            'label'       => 'مدير',
            'description' => 'إشراف على الفريق في الموارد البشرية والمشاريع والمشتريات والأصول.',
        ],
        'hr_manager' => [
            'label'       => 'مدير الموارد البشرية',
            'description' => 'عمليات الموارد البشرية الكاملة بما في ذلك ملفات الموظفين.',
        ],
        'finance_manager' => [
            'label'       => 'مدير المالية',
            'description' => 'المحاسبة والفوترة والتقارير المالية.',
        ],
        'general_manager' => [
            'label'       => 'المدير العام',
            'description' => 'وصول واسع عبر الوحدات للإشراف التنفيذي.',
        ],
    ],

    'modules' => [
        'Accounting'     => 'المحاسبة',
        'Account'        => 'الحسابات',
        'Employee'       => 'الموظفون',
        'Inventory'      => 'المخزون',
        'Purchase'       => 'المشتريات',
        'Sale'           => 'المبيعات',
        'Project'        => 'المشاريع',
        'Meetings'       => 'الاجتماعات',
        'Correspondence' => 'المراسلات',
        'Assets'         => 'الأصول',
        'Payroll'        => 'الرواتب',
        'TimeOff'        => 'الإجازات',
        'Recruitment'    => 'التوظيف',
        'Support'        => 'الدعم',
        'Extensions'     => 'الامتدادات',
    ],

    'advanced' => [
        'search'          => 'بحث في الصلاحيات',
        'resources'       => 'الموارد',
        'pages'           => 'الصفحات',
        'widgets'         => 'الودجات',
        'custom'          => 'صلاحيات مخصصة',
        'select_module'   => 'تحديد الكل في الوحدة',
        'deselect_module' => 'إلغاء تحديد الكل في الوحدة',
        'progress'        => ':selected / :total محددة',
    ],

    'view' => [
        'permissions_count' => 'الصلاحيات',
        'module_summary'    => 'ملخص صلاحيات الوحدات',
        'permission_list'   => 'الصلاحيات المعينة',
    ],
];
