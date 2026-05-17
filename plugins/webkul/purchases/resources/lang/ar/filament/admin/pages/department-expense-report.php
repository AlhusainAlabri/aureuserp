<?php

return [
    'navigation' => [
        'title' => 'تقرير الدوائر',
        'group' => 'المشتريات',
    ],

    'form' => [
        'fields' => [
            'month'           => 'الشهر',
            'year'            => 'السنة',
            'department'      => 'الدائرة',
            'all-departments' => 'جميع الدوائر',
        ],
    ],

    'cards' => [
        'total-purchases'   => 'إجمالي المشتريات هذا الشهر',
        'total-amount'      => 'المبلغ الإجمالي',
        'missing-receipts'  => 'الفواتير المفقودة',
    ],

    'table' => [
        'columns' => [
            'department'         => 'الدائرة',
            'purchases-count'    => 'عدد المشتريات',
            'total-amount'       => 'المبلغ الإجمالي',
            'receipts-uploaded'  => 'الفواتير المرفوعة',
            'receipts-missing'   => 'الفواتير المفقودة',
        ],
    ],

    'actions' => [
        'export-csv' => 'تصدير CSV',
    ],
];
