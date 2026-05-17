<?php

return [
    'global-search' => [
        'vendor'    => 'المورد',
        'reference' => 'المرجع',
        'amount'    => 'المبلغ',
    ],

    'form' => [
        'sections' => [
            'additional-details' => [
                'title' => 'تفاصيل إضافية',

                'fields' => [
                    'requesting-department'  => 'الدائرة مقدمة الطلب',
                    'beneficiary-department' => 'الدائرة المستفيدة',
                    'linked-project'         => 'مشروع مرتبط',
                    'linked-meeting'         => 'محضر مرتبط',
                ],
            ],

            'general' => [
                'title' => 'عام',

                'fields' => [
                    'vendor'                   => 'المورد',
                    'vendor-reference'         => 'مرجع المورد',
                    'vendor-reference-tooltip' => 'رقم مرجع أمر البيع أو العرض المقدم من المورد. يُستخدم للمطابقة عند استلام المنتجات، حيث يُدرج هذا المرجع عادةً في أمر تسليم المورد.',
                    'agreement'                => 'الاتفاقية',
                    'currency'                 => 'العملة',
                    'confirmation-date'        => 'تاريخ التأكيد',
                    'order-deadline'           => 'الموعد النهائي للطلب',
                    'expected-arrival'         => 'تاريخ الوصول المتوقع',
                    'confirmed-by-vendor'      => 'مؤكد من المورد',
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'المنتجات',

                'repeater' => [
                    'products' => [
                        'title'            => 'المنتجات',
                        'add-product-line' => 'إضافة منتج',

                        'fields' => [
                            'product'             => 'المنتج',
                            'expected-arrival'    => 'تاريخ الوصول المتوقع',
                            'quantity'            => 'الكمية',
                            'received'            => 'المستلم',
                            'billed'              => 'المفوتر',
                            'unit'                => 'الوحدة',
                            'packaging-qty'       => 'كمية التغليف',
                            'packaging'           => 'التغليف',
                            'taxes'               => 'الضرائب',
                            'discount-percentage' => 'الخصم (%)',
                            'unit-price'          => 'سعر الوحدة',
                            'amount'              => 'المبلغ',
                        ],

                        'notifications' => [
                            'quantity-below-received' => [
                                'title' => 'لا يمكن تقليل الكمية',
                                'body'  => 'لا يمكنك تقليل الكمية إلى أقل من الكمية المستلمة (:qty).',
                            ],

                            'blanket-order-qty-limit' => [
                                'title' => 'الكمية تتجاوز حد الطلب الشامل',
                                'body'  => 'كمية المنتج (:product_qty) تتجاوز الكمية المتاحة (:available_qty) من الطلب الشامل.',
                            ],
                        ],

                        'columns' => [
                            'product'             => 'المنتج',
                            'expected-arrival'    => 'تاريخ الوصول المتوقع',
                            'quantity'            => 'الكمية',
                            'received'            => 'المستلم',
                            'billed'              => 'المفوتر',
                            'unit'                => 'الوحدة',
                            'packaging-qty'       => 'كمية التغليف',
                            'packaging'           => 'التغليف',
                            'taxes'               => 'الضرائب',
                            'discount-percentage' => 'الخصم (%)',
                            'unit-price'          => 'سعر الوحدة',
                            'amount'              => 'المبلغ',
                        ],

                        'delete-action' => [
                            'error' => [
                                'title' => 'لا يمكن حذف المنتج',
                                'body'  => 'لا يمكن حذف المنتجات من أمر شراء مؤكد.',
                            ],
                        ],
                    ],

                    'section' => [
                        'title' => 'إضافة قسم',

                        'fields' => [],
                    ],

                    'note' => [
                        'title' => 'إضافة ملاحظة',

                        'fields' => [],
                    ],
                ],
            ],

            'additional' => [
                'title' => 'معلومات إضافية',

                'fields' => [
                    'buyer'             => 'المشتري',
                    'company'           => 'الشركة',
                    'source-document'   => 'المستند المصدر',
                    'incoterm'          => 'شروط التجارة الدولية',
                    'incoterm-tooltip'  => 'شروط التجارة الدولية (Incoterms) هي مجموعة من الشروط التجارية الموحدة المستخدمة في المعاملات العالمية لتحديد المسؤوليات بين المشترين والبائعين.',
                    'incoterm-location' => 'موقع شرط التجارة',
                    'payment-term'      => 'شروط الدفع',
                    'fiscal-position'   => 'المركز المالي',
                ],
            ],

            'terms' => [
                'title' => 'الشروط والأحكام',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'favorite'              => 'المفضلة',
            'priority'              => 'الأولوية',
            'vendor-reference'      => 'مرجع المورد',
            'reference'             => 'المرجع',
            'vendor'                => 'المورد',
            'buyer'                 => 'المشتري',
            'company'               => 'الشركة',
            'requesting-department' => 'الدائرة',
            'receipt'               => 'الفاتورة',
            'order-deadline'        => 'الموعد النهائي للطلب',
            'source-document'       => 'المستند المصدر',
            'untaxed-amount'        => 'المبلغ بدون ضريبة',
            'total-amount'          => 'المبلغ الإجمالي',
            'status'                => 'الحالة',
            'billing-status'        => 'حالة الفوترة',
            'currency'              => 'العملة',
        ],

        'groups' => [
            'vendor'     => 'المورد',
            'buyer'      => 'المشتري',
            'state'      => 'الحالة',
            'created-at' => 'تاريخ الإنشاء',
            'updated-at' => 'تاريخ التحديث',
        ],

        'filters' => [
            'status'                => 'الحالة',
            'vendor-reference'      => 'مرجع المورد',
            'reference'             => 'المرجع',
            'untaxed-amount'        => 'المبلغ بدون ضريبة',
            'total-amount'          => 'المبلغ الإجمالي',
            'order-deadline'        => 'الموعد النهائي للطلب',
            'vendor'                => 'المورد',
            'buyer'                 => 'المشتري',
            'company'               => 'الشركة',
            'requesting-department' => 'الدائرة',
            'payment-term'          => 'شروط الدفع',
            'incoterm'              => 'شروط التجارة الدولية',
            'created-at'            => 'تاريخ الإنشاء',
            'updated-at'            => 'تاريخ التحديث',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'تم حذف الطلب',
                        'body'  => 'تم حذف الطلب بنجاح.',
                    ],

                    'error' => [
                        'title' => 'تعذر حذف الطلب',
                        'body'  => 'لا يمكن حذف الطلب لأنه قيد الاستخدام حالياً.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'تم حذف الطلبات',
                        'body'  => 'تم حذف الطلبات بنجاح.',
                    ],

                    'error' => [
                        'title' => 'تعذر حذف الطلبات',
                        'body'  => 'لا يمكن حذف الطلبات لأنها قيد الاستخدام حالياً.',
                    ],
                ],
            ],
        ],
    ],

    'notifications' => [
        'receipt-required' => [
            'title' => 'الفاتورة مطلوبة لأمر الشراء',
            'body'  => 'أمر شراء #{reference} — يرجى رفع الفاتورة',
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'عام',

                'entries' => [
                    'purchase-order'           => 'أمر الشراء',
                    'vendor'                   => 'المورد',
                    'vendor-reference'         => 'مرجع المورد',
                    'vendor-reference-tooltip' => 'رقم مرجع أمر البيع أو العرض المقدم من المورد. يُستخدم للمطابقة عند استلام المنتجات، حيث يُدرج هذا المرجع عادةً في أمر تسليم المورد.',
                    'agreement'                => 'الاتفاقية',
                    'currency'                 => 'العملة',
                    'confirmation-date'        => 'تاريخ التأكيد',
                    'order-deadline'           => 'الموعد النهائي للطلب',
                    'expected-arrival'         => 'تاريخ الوصول المتوقع',
                    'confirmed-by-vendor'      => 'مؤكد من المورد',
                ],
            ],
        ],

        'tabs' => [
            'products' => [
                'title' => 'المنتجات',

                'repeater' => [
                    'products' => [
                        'title'            => 'المنتجات',
                        'add-product-line' => 'إضافة منتج',

                        'entries' => [
                            'product'             => 'المنتج',
                            'expected-arrival'    => 'تاريخ الوصول المتوقع',
                            'quantity'            => 'الكمية',
                            'received'            => 'المستلم',
                            'billed'              => 'المفوتر',
                            'unit'                => 'الوحدة',
                            'packaging-qty'       => 'كمية التغليف',
                            'packaging'           => 'التغليف',
                            'taxes'               => 'الضرائب',
                            'discount-percentage' => 'الخصم (%)',
                            'unit-price'          => 'سعر الوحدة',
                            'amount'              => 'المبلغ',
                        ],
                    ],

                    'section' => [
                        'title' => 'إضافة قسم',
                    ],

                    'note' => [
                        'title' => 'إضافة ملاحظة',
                    ],
                ],
            ],

            'additional' => [
                'title' => 'معلومات إضافية',

                'entries' => [
                    'buyer'                  => 'المشتري',
                    'company'                => 'الشركة',
                    'requesting-department'  => 'الدائرة مقدمة الطلب',
                    'beneficiary-department' => 'الدائرة المستفيدة',
                    'linked-project'         => 'مشروع مرتبط',
                    'linked-meeting'         => 'محضر مرتبط',
                    'source-document'        => 'المستند المصدر',
                    'incoterm'               => 'شروط التجارة الدولية',
                    'incoterm-tooltip'       => 'شروط التجارة الدولية (Incoterms) هي مجموعة من الشروط التجارية الموحدة المستخدمة في المعاملات العالمية لتحديد المسؤوليات بين المشترين والبائعين.',
                    'incoterm-location'      => 'موقع شرط التجارة',
                    'payment-term'           => 'شروط الدفع',
                    'fiscal-position'        => 'المركز المالي',
                    'receipt'                => 'الفاتورة',
                    'receipt-uploaded'       => 'تم رفع الفاتورة ✓',
                    'receipt-missing'        => 'الفاتورة مطلوبة',
                    'receipt-uploaded-at'    => 'تاريخ الرفع',
                ],
            ],

            'terms' => [
                'title' => 'الشروط والأحكام',
            ],
        ],
    ],
];
