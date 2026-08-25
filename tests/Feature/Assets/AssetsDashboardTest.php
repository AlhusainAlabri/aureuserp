<?php

use App\Filament\Assets\Pages\AssetsDashboard;
use App\Filament\Assets\Widgets\AssetOverviewStatsWidget;
use App\Filament\Assets\Widgets\AssetsByCategoryChartWidget;
use App\Filament\Assets\Widgets\DueSoonBorrowingsWidget;
use App\Filament\Assets\Widgets\OverdueBorrowingsWidget;
use App\Filament\Assets\Widgets\RecentBorrowingsWidget;
use App\Filament\Widgets\Dashboard\AssetsSummaryWidget;
use App\Support\FilamentUrl;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        $this->markTestSkipped('Assets plugin not installed.');
    }

    $user = User::factory()->create();
    Permission::findOrCreate('page_assets_dashboard', 'web');
    Permission::findOrCreate('view_any_assets_asset', 'web');
    Permission::findOrCreate('view_assets_asset', 'web');
    $user->givePermissionTo([
        'page_assets_dashboard',
        'view_any_assets_asset',
        'view_assets_asset',
    ]);
    $this->actingAs($user);
});

it('renders the assets dashboard for authorized users', function (): void {
    Livewire::test(AssetsDashboard::class)
        ->assertSuccessful();
});

it('shows overview stats widget on the assets dashboard', function (): void {
    Livewire::test(AssetsDashboard::class)
        ->assertSee(__('assets::assets.widgets.stats.available'))
        ->assertSee(__('assets::assets.widgets.stats.borrowed'))
        ->assertSee(__('assets::assets.widgets.stats.overdue'))
        ->assertSee(__('assets-extensions::dashboard.pending_requests'));
});

it('shows dashboard section headings in the current locale', function (): void {
    app()->setLocale('ar');

    Livewire::test(AssetsDashboard::class)
        ->assertSee(__('assets-extensions::dashboard.due_soon'))
        ->assertSee(__('assets-extensions::dashboard.overdue'))
        ->assertSee(__('assets-extensions::dashboard.recent_activity'))
        ->assertSee(__('assets-extensions::dashboard.by_category'))
        ->assertSee(__('assets-extensions::dashboard.pending_requests'));
});

it('renders all assets dashboard widgets without errors', function (): void {
    Livewire::test(AssetOverviewStatsWidget::class)->assertSuccessful();
    Livewire::test(DueSoonBorrowingsWidget::class)->assertSuccessful();
    Livewire::test(OverdueBorrowingsWidget::class)->assertSuccessful();
    Livewire::test(RecentBorrowingsWidget::class)->assertSuccessful();
    Livewire::test(AssetsByCategoryChartWidget::class)->assertSuccessful();
});

it('renders arabic overview stats and table widget labels', function (): void {
    app()->setLocale('ar');

    Livewire::test(AssetOverviewStatsWidget::class)
        ->assertSee(__('assets-extensions::dashboard.pending_requests'))
        ->assertSee(__('assets::assets.widgets.stats.available'))
        ->assertSee(__('assets::assets.widgets.stats.borrowed'))
        ->assertSee(__('assets::assets.widgets.stats.overdue'));

    Livewire::test(DueSoonBorrowingsWidget::class)
        ->assertSee(__('assets-extensions::dashboard.view_borrowed_assets'));
});

it('renders the main org dashboard assets summary widget in arabic', function (): void {
    app()->setLocale('ar');

    Livewire::test(AssetsSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee(__('assets-extensions::dashboard.pending_requests'))
        ->assertSee(__('assets::assets.widgets.stats.overdue'));
});

it('builds locale-aware asset list urls from stats concern', function (): void {
    app()->setLocale('ar');

    $url = AssetResource::getUrl(
        'index',
        FilamentUrl::withLocale(['tab' => 'available']),
    );

    expect($url)->toContain('tab=available')
        ->and($url)->toContain('lang=ar');
});
