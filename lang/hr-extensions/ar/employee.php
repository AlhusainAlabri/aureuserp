<?php

return [
    'sections' => [
        'departments' => 'الأقسام',
        'file_status' => 'حالة الملف',
        'employment'  => 'تفاصيل التوظيف',
    ],
    'fields' => [
        'primary_job_responsibilities' => 'المسؤوليات الوظيفية الأساسية',
    ],
    'document_types' => [
        'professional_conduct' => 'سياسة السلوك المهني',
    ],
    'all_departments'         => 'جميع الأقسام',
    'primary_department'      => 'القسم الرئيسي',
    'show_closed_files'       => 'إظهار الملفات المغلقة',
    'close_file'              => 'إغلاق ملف الموظف',
    'reopen_file'             => 'إعادة فتح ملف الموظف',
    'file_closed_success'     => 'تم إغلاق ملف الموظف.',
    'file_reopened_success'   => 'تمت إعادة فتح ملف الموظف.',
    'account_closed'          => 'تم إغلاق ملفك الوظيفي. يرجى التواصل مع الموارد البشرية.',
    'yes'                     => 'نعم',
    'no'                      => 'لا',
    'file_upload_placeholder' => 'اسحب الملفات أو <span class="filepond--label-action">تصفح</span>',
    'navigation'              => [
        'more' => 'المزيد',
    ],
    'closure_reasons'         => [
        'administrative' => 'إداري',
        'ethical'        => 'أخلاقي',
        'resignation'    => 'استقالة',
        'retirement'     => 'تقاعد',
        'contract_ended' => 'انتهاء العقد',
        'other'          => 'أخرى',
    ],
    'file_status' => [
        'closed'        => 'الملف مغلق',
        'reason'        => 'سبب الإغلاق',
        'notes'         => 'ملاحظات الإغلاق',
        'closed_at'     => 'تاريخ الإغلاق',
        'closed_by'     => 'أغلق بواسطة',
        'reopen_reason' => 'سبب إعادة الفتح',
        'reopened_at'   => 'تاريخ إعادة الفتح',
        'reopened_by'   => 'أعيد فتحه بواسطة',
    ],
    'exceptions' => [
        'cannot_close'  => 'ليست لديك صلاحية إغلاق ملف هذا الموظف.',
        'cannot_reopen' => 'ليست لديك صلاحية إعادة فتح ملف هذا الموظف.',
    ],
    'close_helper'  => 'يرجى إدخال سبب واضح، سيحفظ هذا كجزء من السجل الدائم.',
    'reopen_helper' => 'اشرح سبب إعادة فتح هذا الملف.',
    'notifications' => [
        'file_closed_title'    => 'تم إغلاق ملفك الوظيفي',
        'file_closed_body'     => 'تواصل مع الموارد البشرية إذا كان ذلك خطأ.',
        'file_closed_hr_title' => 'تم إغلاق ملف موظف',
        'file_closed_hr_body'  => 'تم إغلاق ملف :employee بواسطة :by.',
    ],
];
