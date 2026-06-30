<?php

return [
    'navigation' => [
        'title' => 'Documents',
    ],

    'page' => [
        'title' => ':reference documents',
    ],

    'fields' => [
        'title'       => 'Title',
        'file'        => 'File',
        'file_name'   => 'File name',
        'file_size'   => 'Size',
        'mime_type'   => 'File type',
        'notes'       => 'Notes',
        'creator'     => 'Uploaded by',
        'uploaded_at' => 'Uploaded at',
    ],

    'form' => [
        'upload_hint'        => 'Maximum size: :max MB. PDF, images, and Office documents are accepted.',
        'upload_description' => 'Upload quotations, invoices, approvals, or any files related to this purchase request.',
    ],

    'actions' => [
        'upload'   => 'Upload document',
        'view'     => 'View',
        'download' => 'Download',
    ],

    'empty' => [
        'heading'     => 'No documents yet',
        'description' => 'Upload files related to this purchase order to keep everything in one place.',
    ],
];
