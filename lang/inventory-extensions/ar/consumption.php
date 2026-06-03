<?php

return [
    'title'            => 'تسجيل الاستهلاك',
    'description'      => 'سجّل استهلاك المواد بسرعة عبر تحويل داخلي.',
    'bulk_link'        => 'تحويل داخلي متعدد',
    'bulk_description' => 'استخدم التحويلات الداخلية للاستهلاك الجماعي أو متعدد المنتجات.',
    'fields'           => [
        'product'         => 'المنتج',
        'quantity'        => 'الكمية',
        'department'      => 'القسم',
        'project'         => 'المشروع',
        'purpose'         => 'الغرض',
        'source_location' => 'موقع المصدر',
    ],
    'notifications' => [
        'recorded'      => 'تم تسجيل الاستهلاك',
        'recorded_body' => 'تم إنشاء التحويل الداخلي :name.',
        'failed'        => 'تعذّر إكمال التحويل',
    ],
    'plugin_missing'    => 'وحدة المخزون غير مثبتة.',
    'no_operation_type' => 'لم يتم تكوين نوع عملية التحويل الداخلي.',
    'no_destination'    => 'لم يتم العثور على موقع الاستهلاك.',
    'operation_name'    => 'استهلاك: :product',
    'demo_purpose'      => 'استهلاك تجريبي لمستلزمات القسم',
];
