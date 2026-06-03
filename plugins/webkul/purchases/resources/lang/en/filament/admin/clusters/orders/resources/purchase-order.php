<?php

return [
    'navigation' => [
        'title' => 'Purchase Orders',
    ],

    'table' => [
        'columns' => [
            'approval-status' => 'Approval Status',
        ],
    ],

    'infolist' => [
        'sections' => [
            'receipt' => [
                'title' => 'Receipt',

                'entries' => [
                    'uploaded'    => 'Receipt uploaded ✓',
                    'missing'     => 'Receipt required — please upload the purchase receipt',
                    'uploaded-at' => 'Uploaded At',
                ],

                'actions' => [
                    'upload'   => 'Upload Receipt',
                    'download' => 'Download Receipt',
                ],

                'form' => [
                    'fields' => [
                        'receipt-file' => 'Receipt File',
                    ],
                ],

                'notifications' => [
                    'upload-success' => [
                        'title' => 'Receipt uploaded',
                        'body'  => 'The receipt has been uploaded successfully.',
                    ],
                ],
            ],
        ],
    ],
];
