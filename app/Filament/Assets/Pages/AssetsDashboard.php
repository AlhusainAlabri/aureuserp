<?php

namespace App\Filament\Assets\Pages;

use App\Filament\Assets\Widgets\AssetOverviewStatsWidget;
use App\Filament\Assets\Widgets\AssetsByCategoryChartWidget;
use App\Filament\Assets\Widgets\DueSoonBorrowingsWidget;
use App\Filament\Assets\Widgets\OverdueBorrowingsWidget;
use App\Filament\Assets\Widgets\PendingRequestsWidget;
use App\Filament\Assets\Widgets\RecentBorrowingsWidget;
use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use App\Support\FilamentUrl;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Filament\Resources\AssetResource;

class AssetsDashboard extends BaseDashboard
{
    use HasPageShield;
    use InteractsWithAdvancedDashboard;

    protected static string $routePath = 'assets/dashboard';

    protected string $view = 'filament.pages.advanced-dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 0;

    protected ?string $pollingInterval = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_assets_dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return __('assets-extensions::navigation.dashboard');
    }

    public static function getNavigationGroup(): string
    {
        return __('assets::assets.navigation.group');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Schema::hasTable('assets')
            && parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('assets-extensions::navigation.dashboard');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.assets_description');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAsset')
                ->label(__('assets::assets.actions.create'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(AssetResource::getUrl('create')))
                ->visible(fn (): bool => AssetResource::canCreate())
                ->color('primary'),
            Action::make('myRequests')
                ->label(__('assets-extensions::navigation.my_requests_action'))
                ->icon('heroicon-o-inbox')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(MyBorrowingRequests::getUrl()))
                ->visible(fn (): bool => auth()->user()?->can('page_my_borrowing_requests') ?? false)
                ->color('gray'),
            Action::make('viewAllAssets')
                ->label(__('assets-extensions::navigation.view_all_assets'))
                ->icon('heroicon-o-queue-list')
                ->url(fn (): string => FilamentUrl::appendLocaleToUrl(AssetResource::getUrl('index')))
                ->color('gray'),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            AssetOverviewStatsWidget::class,
            PendingRequestsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            DueSoonBorrowingsWidget::class,
            OverdueBorrowingsWidget::class,
            RecentBorrowingsWidget::class,
            AssetsByCategoryChartWidget::class,
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
