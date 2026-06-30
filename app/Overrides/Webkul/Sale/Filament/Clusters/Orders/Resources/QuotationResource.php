<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use App\Filament\Extensions\SalesQuotationResourceExtensions;
use App\Filament\Sales\Pages\ManageSalesOrderDocuments;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\CreateQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageDeliveries;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageInvoices;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;

class QuotationResource extends CoreQuotationResource
{
    public static function getPluralModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/quotation.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return SalesQuotationResourceExtensions::localizeForm(parent::form($schema));
    }

    public static function table(Table $table): Table
    {
        return SalesQuotationResourceExtensions::localizeTable(parent::table($table));
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewQuotation::class,
            EditQuotation::class,
            ManageInvoices::class,
            ManageDeliveries::class,
            ManageSalesOrderDocuments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'      => ListQuotations::route('/'),
            'create'     => CreateQuotation::route('/create'),
            'view'       => ViewQuotation::route('/{record}'),
            'edit'       => EditQuotation::route('/{record}/edit'),
            'invoices'   => ManageInvoices::route('/{record}/invoices'),
            'deliveries' => ManageDeliveries::route('/{record}/deliveries'),
            'documents'  => ManageSalesOrderDocuments::route('/{record}/documents'),
        ];
    }
}
