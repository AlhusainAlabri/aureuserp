<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class MissingReceiptsWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.missing_receipts');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('purchases_orders') || ! Schema::hasColumn('purchases_orders', 'receipt_uploaded')) {
                return [
                    Stat::make(__('dashboard.widgets.missing_receipts'), __('dashboard.plugin_not_installed'))
                        ->color('gray'),
                ];
            }

            $count = Order::query()
                ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
                ->where('receipt_uploaded', false)
                ->count();

            return [
                Stat::make(__('dashboard.stats.missing_receipts'), $count)
                    ->color($count > 0 ? 'warning' : 'success')
                    ->icon('heroicon-o-receipt-percent')
                    ->description(__('dashboard.widgets.missing_receipts')),
            ];
        } catch (\Exception) {
            return [
                Stat::make(__('dashboard.widgets.missing_receipts'), __('dashboard.data_unavailable'))
                    ->color('gray'),
            ];
        }
    }
}
