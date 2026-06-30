<?php

namespace App\Filament\Pages;

use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;

class InternalRequests extends ListPurchaseOrders
{
    protected static ?string $cluster = null;

    protected static ?string $slug = 'internal-requests';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return __('purchases-extensions::request.navigation.internal_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.purchase');
    }

    public function getTitle(): string
    {
        return __('purchases-extensions::request.navigation.internal_requests');
    }

    public static function canAccess(array $parameters = []): bool
    {
        if (! Auth::check() || ! Schema::hasTable('purchases_orders')) {
            return false;
        }

        if (! Schema::hasColumn('purchases_orders', 'request_type')) {
            return Auth::user()->can('view_any_purchase_purchase::order')
                || Auth::user()->can('page_InternalRequests');
        }

        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('purchases-extensions::request.actions.new_request'))
                ->url(PurchaseOrderResource::getUrl('create', [
                    'request_type' => RequestType::DeviceRequest->value,
                ])),
        ];
    }

    public function getPresetTableViews(): array
    {
        $views = [];

        foreach (PurchaseOrderResourceExtensions::presetTableViews() as $key => $view) {
            $views[$key] = $view->favorite();
        }

        return $views;
    }

    public function table(Table $table): Table
    {
        invade($table)->queryScopes = [];

        return PurchaseOrderResourceExtensions::applyTableCustomizations(
            OrderResource::table($table)
                ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('request_type', RequestType::internalRequestTypes()))
        );
    }
}
