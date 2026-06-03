<?php

return [
    'section_title'    => 'تفاصيل الطلب',
    'internal_section' => 'تفاصيل الطلب الداخلي',
    'fields'           => [
        'request_type'      => 'نوع الطلب',
        'urgency'           => 'الأولوية',
        'justification'     => 'مبرر العمل',
        'item_description'  => 'وصف الصنف أو الخدمة',
        'expected_delivery' => 'تاريخ التسليم المتوقع',
        'quotation'         => 'عرض السعر أو الفاتورة',
        'payment_voucher'   => 'سند الصرف',
        'vendor_hint'       => 'اختياري — اسم المحل أو الشركة لهذا الشراء',
    ],
    'payment' => [
        'section_title'    => 'تتبع الدفعات',
        'amount_paid'      => 'المبلغ المدفوع',
        'amount_remaining' => 'المبلغ المتبقي',
        'record_payment'   => 'تسجيل دفعة',
    ],
    'notifications' => [
        'voucher_required' => [
            'title' => 'سند الصرف مطلوب',
            'body'  => 'يرجى رفع سند الصرف قبل اعتماد صرف المبلغ.',
        ],
    ],
    'types' => [
        'standard_purchase' => 'شراء عادي',
        'device_request'    => 'طلب جهاز',
        'technical_support' => 'دعم فني',
        'office_supplies'   => 'مستلزمات مكتبية',
        'maintenance'       => 'صيانة',
        'other'             => 'أخرى',
    ],
    'urgency' => [
        'low'      => 'منخفضة',
        'normal'   => 'عادية',
        'high'     => 'عالية',
        'critical' => 'حرجة',
    ],
    'navigation' => [
        'my_requests'       => 'طلباتي',
        'internal_requests' => 'الطلبات الداخلية',
    ],
    'actions' => [
        'new_request' => 'طلب جديد',
    ],
    'create_title'   => 'إنشاء :type',
    'lines'          => [
        'title'       => 'بنود الطلب',
        'add_line'    => 'إضافة بند',
        'description' => 'الوصف',
        'quantity'    => 'الكمية',
        'unit_price'  => 'سعر الوحدة (ر.ع.)',
    ],
    'tabs' => [
        'all'               => 'الكل',
        'standard_purchase' => 'شراء عادي',
        'device_request'    => 'طلبات الأجهزة',
        'technical_support' => 'الدعم الفني',
        'office_supplies'   => 'مستلزمات مكتبية',
        'maintenance'       => 'الصيانة',
        'other'             => 'أخرى',
    ],
    'currency' => [
        'format' => 'ر.ع. :amount',
    ],
    'email' => [
        'receipt_reminder' => [
            'subject'  => 'الفاتورة مطلوبة للطلب :reference',
            'greeting' => 'مرحباً :name،',
            'body'     => 'يرجى رفع فاتورة أو إيصال الشراء للطلب :reference.',
            'footer'   => 'هذا تذكير آلي من نظام المشتريات.',
        ],
    ],
];
