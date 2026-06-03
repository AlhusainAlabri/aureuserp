<?php

namespace App\Filament\Inventory\Widgets;

use App\Filament\Inventory\Concerns\InteractsWithPendingReceiptCount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class PendingReceiptsWidget extends BaseWidget
{
    use InteractsWithPendingReceiptCount;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 3;

    protected function getHeading(): ?string
    {
        return __('inventory-extensions::dashboard.pending_receipts');
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('purchases_orders') || ! Schema::hasTable('purchases_order_operations')) {
            return [
                Stat::make(__('inventory-extensions::dashboard.pending_receipts'), __('inventory-extensions::dashboard.plugin_missing'))
                    ->color('gray'),
            ];
        }

        $count = $this->countPendingReceipts();

        return [
            Stat::make(__('inventory-extensions::dashboard.pending_receipts'), $count)
                ->description(__('inventory-extensions::dashboard.view_receipts'))
                ->descriptionIcon('heroicon-m-truck')
                ->color($count > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url($this->pendingReceiptsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
