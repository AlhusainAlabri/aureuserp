<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Purchases\Pages\CreateQuotation as AppCreateQuotation;
use App\Filament\Purchases\Pages\ManageQuotationDocuments;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ManageBills;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ManageReceipts;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;

class QuotationResource extends CoreQuotationResource
{
    public static function getPluralModelLabel(): string
    {
        return __('purchases-extensions::request.navigation.quotations');
    }

    public static function getModelLabel(): string
    {
        return __('purchases::filament/admin/clusters/orders/resources/quotation.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderResourceExtensions::localizeForm(parent::form($schema));
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewQuotation::class,
            EditQuotation::class,
            ManageBills::class,
            ManageReceipts::class,
            ManageQuotationDocuments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListQuotations::route('/'),
            'create'    => AppCreateQuotation::route('/create'),
            'view'      => ViewQuotation::route('/{record}'),
            'edit'      => EditQuotation::route('/{record}/edit'),
            'bills'     => ManageBills::route('/{record}/bills'),
            'receipts'  => ManageReceipts::route('/{record}/receipts'),
            'documents' => ManageQuotationDocuments::route('/{record}/documents'),
        ];
    }
}
