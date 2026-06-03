<?php

return [
    'submitted' => [
        'title' => 'تم تقديم طلب إعارة أصل',
        'body'  => 'طلب :employee إعارة :asset (:number). تاريخ الاستحقاق: :due_at.',
    ],
    'approved' => [
        'title' => 'تمت الموافقة على طلب الإعارة',
        'body'  => 'تمت الموافقة على طلبك لإعارة :asset (:number). تاريخ الاستحقاق: :due_at.',
    ],
    'rejected' => [
        'title' => 'تم رفض طلب الإعارة',
        'body'  => 'تم رفض طلبك لإعارة :asset (:number).',
    ],
    'due_reminder' => [
        'title' => 'اقتراب موعد إرجاع الأصل',
        'body'  => 'الأصل :asset (:number) المعَار إلى :employee مستحق في :due_at.',
    ],
    'overdue' => [
        'title' => 'إعارة أصل متأخرة',
        'body'  => 'الأصل :asset (:number) المعَار إلى :employee كان مستحقاً في :due_at.',
    ],
    'returned' => [
        'title' => 'تم إرجاع الأصل',
        'body'  => 'تم إرجاع الأصل :asset (:number) المعَار إلى :employee.',
    ],
];
