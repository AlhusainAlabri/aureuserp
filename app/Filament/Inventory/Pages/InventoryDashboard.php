<?php

namespace App\Filament\Inventory\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Filament\Inventory\Widgets\LowStockOverviewWidget;
use App\Filament\Inventory\Widgets\PendingReceiptsWidget;
use App\Filament\Inventory\Widgets\RecentMovementsWidget;
use App\Filament\Inventory\Widgets\StockAlertsWidget;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Schema;

class InventoryDashboard extends BaseDashboard
{
    use HasPageShield;
    use InteractsWithAdvancedDashboard;

    protected static string $routePath = 'inventory/dashboard';

    protected string $view = 'filament.pages.advanced-dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 0;

    protected ?string $pollingInterval = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_inventory_dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory-extensions::navigation.dashboard');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations.navigation.group');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('inventories_order_points')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('inventory-extensions::navigation.dashboard');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.inventory_description');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('movementReport')
                ->label(__('inventory-extensions::navigation.movement_report'))
                ->icon('heroicon-o-document-chart-bar')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(MovementReportPage::getUrl()))
                ->visible(fn (): bool => MovementReportPage::canAccess())
                ->color('gray'),
            Action::make('recordConsumption')
                ->label(__('inventory-extensions::navigation.record_consumption'))
                ->icon('heroicon-o-arrow-right-circle')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(RecordConsumption::getUrl()))
                ->visible(fn (): bool => RecordConsumption::canAccess())
                ->color('gray'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            LowStockOverviewWidget::class,
            PendingReceiptsWidget::class,
            StockAlertsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            RecentMovementsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 12,
        ];
    }
}
