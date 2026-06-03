<?php

return [
    'navigation' => [
        'title' => 'التجديد',
        'group' => 'التعديلات',
    ],

    'form' => [
        'fields' => [
            'name'      => 'الاسم',
            'warehouse' => 'المستودع',
        ],
    ],

    'table' => [
        'empty-state' => [
            'heading'     => 'لا توجد قواعد تجديد',
            'description' => 'أضف قاعدة تجديد لضبط الحد الأدنى للمخزون وتلقي التنبيهات.',
        ],
        'columns' => [
            'product'           => 'المنتج',
            'location'          => 'الموقع',
            'route'             => 'المسار',
            'vendor'            => 'المورد',
            'trigger'           => 'المُفعِّل',
            'on-hand'           => 'المتاح',
            'min'               => 'الحد الأدنى',
            'max'               => 'الحد الأقصى',
            'multiple-quantity' => 'الكمية المتعددة',
            'to-order'          => 'للطلب',
            'uom'               => 'وحدة القياس',
            'company'           => 'الشركة',
        ],

        'groups' => [
            'location' => 'الموقع',
            'product'  => 'المنتج',
            'category' => 'الفئة',
        ],

        'filters' => [
        ],

        'header-actions' => [
            'create' => [
                'label' => 'إضافة تجديد',

                'notification' => [
                    'title' => 'تمت إضافة التجديد',
                    'body'  => 'تمت إضافة التجديد بنجاح.',
                ],

                'before' => [
                    'notification' => [
                        'title' => 'التجديد موجود بالفعل',
                        'body'  => 'يوجد تجديد بالفعل لهذه الإعدادات. يرجى تحديث التجديد الموجود بدلاً من ذلك.',
                    ],
                ],
            ],
        ],

        'actions' => [
        ],
    ],
];
