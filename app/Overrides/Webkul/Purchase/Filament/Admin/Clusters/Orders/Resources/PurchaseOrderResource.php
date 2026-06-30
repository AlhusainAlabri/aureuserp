<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Purchases\Pages\CreatePurchaseOrder;
use App\Filament\Purchases\Pages\EditPurchaseOrder;
use App\Filament\Purchases\Pages\ManagePurchaseOrderDocuments;
use App\Filament\Purchases\Pages\ViewPurchaseOrder;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ManageBills;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ManageReceipts;

class PurchaseOrderResource extends CorePurchaseOrderResource
{
    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderResourceExtensions::localizeForm(parent::form($schema));
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrderResourceExtensions::applyTableCustomizations(parent::table($table));
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPurchaseOrder::class,
            EditPurchaseOrder::class,
            ManageBills::class,
            ManageReceipts::class,
            ManagePurchaseOrderDocuments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListPurchaseOrders::route('/'),
            'create'    => CreatePurchaseOrder::route('/create'),
            'view'      => ViewPurchaseOrder::route('/{record}'),
            'edit'      => EditPurchaseOrder::route('/{record}/edit'),
            'bills'     => ManageBills::route('/{record}/bills'),
            'receipts'  => ManageReceipts::route('/{record}/receipts'),
            'documents' => ManagePurchaseOrderDocuments::route('/{record}/documents'),
        ];
    }
}
