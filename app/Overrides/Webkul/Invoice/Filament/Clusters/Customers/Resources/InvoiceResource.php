<?php

namespace Webkul\Invoice\Filament\Clusters\Customers\Resources;

use App\Filament\Invoices\Pages\ManageInvoiceDocuments;
use Filament\Resources\Pages\Page;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\CreateInvoice;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\EditInvoice;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ListInvoices;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ManagePayments;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ViewInvoice;

class InvoiceResource extends CoreInvoiceResource
{
    public static function getPluralModelLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/invoice.navigation.title');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewInvoice::class,
            EditInvoice::class,
            ManagePayments::class,
            ManageInvoiceDocuments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListInvoices::route('/'),
            'create'    => CreateInvoice::route('/create'),
            'view'      => ViewInvoice::route('/{record}'),
            'edit'      => EditInvoice::route('/{record}/edit'),
            'payments'  => ManagePayments::route('/{record}/payments'),
            'documents' => ManageInvoiceDocuments::route('/{record}/documents'),
        ];
    }
}
