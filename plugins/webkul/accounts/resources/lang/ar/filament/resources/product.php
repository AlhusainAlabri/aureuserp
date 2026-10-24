<?php

return [
    'form' => [
        'fields' => [
            'product-taxes'  => 'ضرائب المبيعات',
            'supplier-taxes' => 'ضرائب المشتريات',
        ],
        'helper' => [
            'incl-taxes' => ':amount شامل الضريبة',
            'excl-taxes' => ':amount غير شامل الضريبة',
        ],
    ],
];
