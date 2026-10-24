<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'error' => [
                    'title' => 'Order cannot be deleted',
                    'body'  => 'This order has related records and cannot be deleted.',
                ],
                'success' => [
                    'title' => 'Order deleted',
                    'body'  => 'The order has been deleted successfully.',
                ],
            ],
        ],
    ],
];
