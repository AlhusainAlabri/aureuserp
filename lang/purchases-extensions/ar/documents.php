<?php

return [
    'navigation' => [
        'title' => 'المستندات',
    ],

    'page' => [
        'title' => 'مستندات :reference',
    ],

    'fields' => [
        'title'       => 'العنوان',
        'file'        => 'الملف',
        'file_name'   => 'اسم الملف',
        'file_size'   => 'الحجم',
        'mime_type'   => 'نوع الملف',
        'notes'       => 'ملاحظات',
        'creator'     => 'رفع بواسطة',
        'uploaded_at' => 'تاريخ الرفع',
    ],

    'form' => [
        'upload_hint'        => 'الحد الأقصى للحجم: :max ميغابايت. يُقبل PDF والصور ومستندات Office.',
        'upload_description' => 'ارفع عروض أسعار أو فواتير أو موافقات أو أي ملفات مرتبطة بطلب الشراء.',
    ],

    'actions' => [
        'upload'   => 'رفع مستند',
        'view'     => 'عرض',
        'download' => 'تنزيل',
    ],

    'empty' => [
        'heading'     => 'لا توجد مستندات',
        'description' => 'ارفع المستندات المرتبطة بهذا الطلب للاحتفاظ بها في مكان واحد.',
    ],
];
