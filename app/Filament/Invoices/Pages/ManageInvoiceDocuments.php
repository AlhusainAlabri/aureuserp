<?php

namespace App\Filament\Invoices\Pages;

use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource;

class ManageInvoiceDocuments extends ManageAccountMoveDocuments
{
    protected static string $resource = InvoiceResource::class;

    protected static function storageDirectoryPrefix(): string
    {
        return 'invoices/documents/';
    }

    protected static function uploadDescriptionKey(): string
    {
        return 'invoices-extensions::documents.form.upload_description_customer';
    }

    protected static function emptyDescriptionKey(): string
    {
        return 'invoices-extensions::documents.empty.description_customer';
    }
}
