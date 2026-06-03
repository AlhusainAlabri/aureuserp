<?php

namespace Webkul\Assets\Filament\Widgets;

use App\Support\FilamentUrl;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;

class AssetsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 3,
        ];
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('assets')) {
            return [
                Stat::make(__('assets::assets.widgets.stats.unavailable'), '—')
                    ->description(__('assets::assets.widgets.stats.plugin_not_installed'))
                    ->color('gray'),
            ];
        }

        $available = Asset::query()->where('status', AssetStatus::Available)->count();
        $borrowed = Asset::query()->where('status', AssetStatus::Borrowed)->count();
        $overdue = Schema::hasTable('asset_borrowings')
            ? AssetBorrowing::query()->overdue()->count()
            : 0;

        return [
            Stat::make(__('assets::assets.widgets.stats.available'), $available)
                ->description(__('assets::assets.widgets.stats.available_hint'))
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success')
                ->url(FilamentUrl::appendLocaleToUrl(
                    AssetResource::getUrl('index', FilamentUrl::withLocale(['tab' => 'available'])),
                ))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make(__('assets::assets.widgets.stats.borrowed'), $borrowed)
                ->description(__('assets::assets.widgets.stats.borrowed_hint'))
                ->descriptionIcon('heroicon-m-arrow-right-circle', IconPosition::Before)
                ->color('warning')
                ->url(FilamentUrl::appendLocaleToUrl(
                    AssetResource::getUrl('index', FilamentUrl::withLocale(['tab' => 'borrowed'])),
                ))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make(__('assets::assets.widgets.stats.overdue'), $overdue)
                ->description(__('assets::assets.widgets.stats.overdue_hint'))
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color($overdue > 0 ? 'danger' : 'gray')
                ->url(FilamentUrl::appendLocaleToUrl(
                    AssetResource::getUrl('index', FilamentUrl::withLocale()),
                ))
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}
