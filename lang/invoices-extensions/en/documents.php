<?php

return [
    'navigation' => [
        'title' => 'Documents',
    ],

    'page' => [
        'title' => 'Documents for :reference',
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
        'upload_hint'                 => 'Maximum size: :max MB. PDF, images, and Office documents are accepted.',
        'upload_description'          => 'Upload signed copies, approvals, or any files related to this invoice.',
        'upload_description_customer' => 'Upload signed copies, approvals, or any files related to this invoice.',
        'upload_description_vendor'   => 'Upload vendor invoices, receipts, approvals, or any files related to this bill.',
    ],

    'actions' => [
        'upload'   => 'Upload document',
        'view'     => 'View',
        'download' => 'Download',
    ],

    'empty' => [
        'heading'              => 'No documents yet',
        'description'          => 'Upload files related to this invoice to keep everything in one place.',
        'description_customer' => 'Upload files related to this invoice to keep everything in one place.',
        'description_vendor'   => 'Upload files related to this vendor bill to keep everything in one place.',
    ],
];
