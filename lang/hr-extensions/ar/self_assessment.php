<?php

return [
    'navigation'         => 'التقييم الذاتي',
    'navigation_manage'  => 'التقييمات الذاتية',
    'form'               => [
        'section' => 'التقييم الذاتي الشهري',
    ],
    'history_heading'   => 'سجل التقييمات',
    'empty_heading'     => 'لا توجد تقييمات',
    'empty_description' => 'قدّم تقييمك الذاتي الشهري باستخدام النموذج أعلاه.',
    'period_label'      => ':month/:year',
    'fields'            => [
        'period'             => 'الفترة',
        'period_year'        => 'السنة',
        'period_month'       => 'الشهر',
        'employee_comments'  => 'تعليقاتك',
        'attachment'         => 'ملف التقييم',
        'status'             => 'الحالة',
        'submitted_at'       => 'تاريخ التقديم',
        'manager_feedback'   => 'ملاحظات المدير',
        'reviewed_by'        => 'تمت المراجعة بواسطة',
        'reviewed_at'        => 'تاريخ المراجعة',
    ],
    'months' => [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ],
    'status' => [
        'draft'     => 'مسودة',
        'submitted' => 'مُقدَّم',
        'reviewed'  => 'تمت المراجعة',
    ],
    'actions' => [
        'submit' => 'تقديم التقييم',
        'review' => 'إضافة ملاحظات المدير',
    ],
    'notifications' => [
        'submitted'      => 'تم تقديم التقييم الذاتي بنجاح.',
        'no_employee'    => 'لا يوجد سجل موظف مرتبط بحسابك.',
        'reminder_title' => 'التقييم الذاتي الشهري مستحق',
        'reminder_body'  => 'يرجى تقديم تقييمك الذاتي لشهر :month :year.',
        'reviewed_title' => 'تمت مراجعة التقييم الذاتي',
        'reviewed_body'  => 'تمت مراجعة تقييمك الذاتي للفترة :period.',
        'review_saved'   => 'تم حفظ ملاحظات المدير.',
    ],
    'mail' => [
        'reminder_subject' => 'تذكير: التقييم الذاتي لشهر :month :year',
        'reminder_heading' => 'تذكير بالتقييم الذاتي الشهري',
        'reminder_body'    => 'عزيزي/عزيزتي :name، يرجى تقديم تقييمك الذاتي لشهر :month :year.',
        'submit_button'    => 'تقديم التقييم الذاتي',
    ],
];
