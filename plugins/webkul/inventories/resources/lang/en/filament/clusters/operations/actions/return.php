<?php

return [
    'label' => 'Return',

    'modal' => [
        'form' => [
            'columns' => [
                'product'  => 'Product',
                'quantity' => 'Quantity',
                'uom'      => 'UOM',
            ],
            'to-refund'           => 'Update received quantities on the purchase order',
            'to-refund-help'      => 'Turn on if the goods go back to the vendor and you expect a credit note: the received quantity goes down and what is left to bill is recomputed.',
            'to-refund-sale'      => 'Update delivered quantities on the sales order',
            'to-refund-sale-help' => 'Turn on if the customer sends the goods back and you plan to issue a credit note: the delivered quantity goes down and what is left to invoice is recomputed.',
        ],
    ],

    'notification' => [
        'no-products' => [
            'body' => 'No products to return (only lines in Done state and not fully returned yet can be returned).',
        ],
        'no-quantities' => [
            'body' => 'Please specify at least one non-zero quantity.',
        ],
    ],
];
