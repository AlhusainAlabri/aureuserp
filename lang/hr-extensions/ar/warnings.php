<?php

return [
    'navigation'        => 'إنذاراتي',
    'empty_heading'     => 'لا توجد إنذارات',
    'empty_description' => 'لا توجد إنذارات تأديبية في سجلك.',
    'fields'            => [
        'type'            => 'نوع الإنذار',
        'subject'         => 'الموضوع',
        'issued_at'       => 'تاريخ الإنذار',
        'acknowledged'    => 'تم الإقرار',
        'signed_document' => 'المستند الموقّع',
        'notes'           => 'ملاحظات',
    ],
    'actions' => [
        'acknowledge' => 'الإقرار بالإنذار',
    ],
    'notifications' => [
        'acknowledged' => 'تم الإقرار بالإنذار بنجاح.',
    ],
    'mail' => [
        'acknowledged_subject' => 'تم الإقرار بإنذار من :employee',
        'acknowledged_heading' => 'إقرار بإنذار موظف',
        'acknowledged_body'    => 'أقر :employee بالإنذار ":subject" الصادر في :date.',
        'thanks'               => 'هذا إشعار آلي لسجلات الموارد البشرية.',
    ],
];
