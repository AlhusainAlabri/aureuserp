<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Assets\Concerns\InteractsWithAssetStats;
use App\Filament\Assets\Pages\AssetsDashboard;
use App\Filament\Assets\Pages\PendingBorrowingRequests;
use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardLayout;
use App\Support\FilamentUrl;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class AssetsSummaryWidget extends BaseWidget
{
    use HasOrgDashboardLayout;
    use InteractsWithAssetStats;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return Schema::hasTable('assets')
            && auth()->user()?->can('page_assets_dashboard');
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('assets')) {
            return [
                Stat::make(__('assets-extensions::navigation.dashboard'), __('assets-extensions::dashboard.plugin_missing'))
                    ->color('gray'),
            ];
        }

        $pending = $this->countPendingRequests();
        $overdue = $this->countOverdueBorrowings();

        return [
            Stat::make(__('assets-extensions::dashboard.pending_requests'), $pending)
                ->description(__('assets-extensions::dashboard.view_pending'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(FilamentUrl::appendLocaleToUrl(PendingBorrowingRequests::getUrl()))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make(__('assets::assets.widgets.stats.overdue'), $overdue)
                ->description(__('assets-extensions::navigation.dashboard'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(FilamentUrl::appendLocaleToUrl(AssetsDashboard::getUrl()))
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
