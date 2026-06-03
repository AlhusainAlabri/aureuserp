<?php

use App\Filament\Assets\Pages\AssetsDashboard;
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
        ->assertSee(__('assets::assets.widgets.stats.overdue'));
});

it('shows dashboard section headings in the current locale', function (): void {
    app()->setLocale('ar');

    Livewire::test(AssetsDashboard::class)
        ->assertSee(__('assets-extensions::dashboard.due_soon'))
        ->assertSee(__('assets-extensions::dashboard.overdue'))
        ->assertSee(__('assets-extensions::dashboard.recent_activity'));
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
