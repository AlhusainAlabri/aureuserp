<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use App\Filament\Invoices\Pages\ManageBillDocuments;
use Filament\Resources\Pages\Page;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages\CreateBill;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages\EditBill;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages\ListBills;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages\ManagePayments;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages\ViewBill;

class BillResource extends CoreBillResource
{
    public static function getPluralModelLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/bill.navigation.title');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewBill::class,
            EditBill::class,
            ManagePayments::class,
            ManageBillDocuments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => ListBills::route('/'),
            'create'    => CreateBill::route('/create'),
            'edit'      => EditBill::route('/{record}/edit'),
            'view'      => ViewBill::route('/{record}'),
            'payments'  => ManagePayments::route('/{record}/payments'),
            'documents' => ManageBillDocuments::route('/{record}/documents'),
        ];
    }
}
