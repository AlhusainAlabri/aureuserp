<?php

return [
    'navigation' => [
        'title' => 'نظرة عامة',
    ],

    'title' => 'نظرة عامة على الموظف',

    'banner' => [
        'inactive-title' => 'هذا الموظف لم يعد نشطاً.',
        'departed-on'    => 'غادر في :date',
    ],

    'summary' => [
        'expired-docs'      => 'وثائق منتهية',
        'expiring-soon'     => 'تنتهي قريباً',
        'active-warnings'   => 'تحذيرات نشطة',
        'compliance-issues' => 'مشكلات الامتثال',
    ],

    'info' => [
        'heading'          => 'معلومات الموظف',
        'manager'          => 'المدير',
        'department'       => 'القسم',
        'job-position'     => 'المسمى الوظيفي',
        'work-email'       => 'البريد الإلكتروني للعمل',
        'work-phone'       => 'هاتف العمل',
        'employment-type'  => 'نوع التوظيف',
        'civil-id'         => 'الرقم المدني',
        'civil-id-expires' => 'ينتهي',
    ],

    'documents' => [
        'heading' => 'تنبيهات الوثائق',
        'columns' => [
            'type'        => 'النوع',
            'name'        => 'اسم الوثيقة',
            'expiry-date' => 'تاريخ الانتهاء',
            'status'      => 'الحالة',
        ],
    ],

    'compliance' => [
        'heading'     => 'تنبيهات الامتثال',
        'visa-expire' => 'انتهاء التأشيرة',
        'work-permit' => 'انتهاء تصريح العمل',
        'civil-id'    => 'انتهاء البطاقة المدنية',
    ],

    'warnings' => [
        'heading'   => 'تحذيرات غير مُقرّة',
        'issued-on' => 'صدر في :date',
    ],

    'status' => [
        'expired'         => 'منتهي',
        'expires-in-days' => 'خلال :days يوم',
        'valid'           => 'ساري',
    ],

    'header-actions' => [
        'edit'           => 'تعديل الموظف',
        'add-document'   => 'إضافة مستند',
        'issue-warning'  => 'إصدار إنذار',
    ],

    'all-clear' => [
        'title'       => 'كل شيء على ما يرام',
        'description' => 'لا توجد تنبيهات امتثال أو مشكلات في الوثائق أو تحذيرات نشطة لهذا الموظف.',
    ],
];
