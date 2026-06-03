<?php

return [
    'title' => 'أنواع الإنذارات',

    'navigation' => [
        'title' => 'أنواع الإنذارات',
        'group' => 'الموظفون',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'المعلومات العامة',
                'fields' => [
                    'name'        => 'الاسم',
                    'description' => 'الوصف',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'           => 'الاسم',
            'description'    => 'الوصف',
            'warnings-count' => 'عدد الإنذارات',
        ],
        'filters' => [
            'name'        => 'الاسم',
            'description' => 'الوصف',
            'created-at'  => 'تاريخ الإنشاء',
            'updated-at'  => 'تاريخ التحديث',
        ],
        'groups' => [
            'name'       => 'الاسم',
            'created-at' => 'تاريخ الإنشاء',
            'updated-at' => 'تاريخ التحديث',
        ],
        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف نوع الإنذار',
                    'body'  => 'تم حذف نوع الإنذار بنجاح.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'تم استعادة نوع الإنذار',
                    'body'  => 'تم استعادة نوع الإنذار بنجاح.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف أنواع الإنذارات',
                    'body'  => 'تم حذف أنواع الإنذارات بنجاح.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'تم استعادة أنواع الإنذارات',
                    'body'  => 'تم استعادة أنواع الإنذارات بنجاح.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'تم الحذف النهائي لأنواع الإنذارات',
                    'body'  => 'تم حذف أنواع الإنذارات نهائيًا.',
                ],
            ],
        ],
        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'تم إنشاء نوع الإنذار',
                    'body'  => 'تم إنشاء نوع الإنذار بنجاح.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'entries' => [
                    'name'           => 'الاسم',
                    'description'    => 'الوصف',
                    'warnings-count' => 'عدد الإنذارات',
                ],
            ],
        ],
    ],
];
