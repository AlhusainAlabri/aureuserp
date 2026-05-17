<?php

return [
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
            'force-delete' => [
                'notification' => [
                    'title' => 'تم الحذف النهائي لنوع الإنذار',
                    'body'  => 'تم حذف نوع الإنذار نهائيًا.',
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
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'المعلومات العامة',
                'entries' => [
                    'name'           => 'الاسم',
                    'description'    => 'الوصف',
                    'warnings-count' => 'عدد الإنذارات',
                ],
            ],
        ],
    ],

    'pages' => [
        'list-warning-type' => [
            'header-actions' => [
                'create' => [
                    'label' => 'نوع إنذار جديد',
                ],
            ],
            'tabs' => [
                'archived' => 'المؤرشف',
            ],
        ],
        'create-warning-type' => [
            'notification' => [
                'title' => 'تم إنشاء نوع الإنذار',
                'body'  => 'تم إنشاء نوع الإنذار بنجاح.',
            ],
        ],
        'edit-warning-type' => [
            'notification' => [
                'title' => 'تم تحديث نوع الإنذار',
                'body'  => 'تم تحديث نوع الإنذار بنجاح.',
            ],
            'header-actions' => [
                'delete' => [
                    'notification' => [
                        'title' => 'تم حذف نوع الإنذار',
                        'body'  => 'تم حذف نوع الإنذار بنجاح.',
                    ],
                ],
            ],
        ],
        'view-warning-type' => [
            'header-actions' => [
                'delete' => [
                    'notification' => [
                        'title' => 'تم حذف نوع الإنذار',
                        'body'  => 'تم حذف نوع الإنذار بنجاح.',
                    ],
                ],
            ],
        ],
    ],
];
