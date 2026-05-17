<?php

return [
    'navigation' => [
        'title' => 'أوامر الشراء',
    ],

    'table' => [
        'columns' => [
            'approval-status' => 'حالة الموافقة',
        ],
    ],

    'infolist' => [
        'sections' => [
            'receipt' => [
                'title' => 'الفاتورة',

                'entries' => [
                    'uploaded'    => 'تم رفع الفاتورة ✓',
                    'missing'     => 'الفاتورة مطلوبة — يرجى رفع فاتورة الشراء',
                    'uploaded-at' => 'تاريخ الرفع',
                ],

                'actions' => [
                    'upload'   => 'رفع الفاتورة',
                    'download' => 'تحميل الفاتورة',
                ],

                'form' => [
                    'fields' => [
                        'receipt-file' => 'ملف الفاتورة',
                    ],
                ],

                'notifications' => [
                    'upload-success' => [
                        'title' => 'تم رفع الفاتورة',
                        'body'  => 'تم رفع الفاتورة بنجاح.',
                    ],
                ],
            ],
        ],
    ],
];
