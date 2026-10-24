<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'error' => [
                    'title' => 'لا يمكن حذف الطلب',
                    'body'  => 'لا يمكن حذف هذا الطلب لارتباطه بسجلات أخرى.',
                ],
                'success' => [
                    'title' => 'تم حذف الطلب',
                    'body'  => 'تم حذف الطلب بنجاح.',
                ],
            ],
        ],
    ],
];
