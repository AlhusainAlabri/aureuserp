<?php

namespace App\Filament\Assets\Widgets;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Support\SchemaMemo;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingRequestsWidget extends BaseWidget
{
    use InteractsWithAssetStats;

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 3;

    protected function getStats(): array
    {
        if (! SchemaMemo::hasTable('asset_borrowings')) {
            return [];
        }

        $pending = $this->countPendingRequests();

        return [
            Stat::make(__('assets-extensions::dashboard.pending_requests'), $pending)
                ->description(__('assets-extensions::dashboard.view_pending'))
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color($pending > 0 ? 'warning' : 'success')
                ->url($this->pendingRequestsUrl())
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
