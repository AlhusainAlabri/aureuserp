<?php

namespace App\Filament\Invoices\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;

class ManageBillDocuments extends ManageAccountMoveDocuments
{
    protected static string $resource = BillResource::class;

    protected static function storageDirectoryPrefix(): string
    {
        return 'invoices/vendor-documents/';
    }

    protected static function uploadDescriptionKey(): string
    {
        return 'invoices-extensions::documents.form.upload_description_vendor';
    }

    protected static function emptyDescriptionKey(): string
    {
        return 'invoices-extensions::documents.empty.description_vendor';
    }
}
