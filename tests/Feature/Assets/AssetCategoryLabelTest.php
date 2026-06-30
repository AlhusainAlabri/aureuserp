<?php

use App\Filament\Extensions\AssetResourceExtensions\ListAssets;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Enums\AssetCategory;
use Webkul\Assets\Models\Asset;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }

    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach (['view_any_assets_asset', 'view_assets_asset'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    $this->actingAs($user);
});

it('shows translated category labels on the assets list in arabic', function (): void {
    app()->setLocale('ar');

    $asset = Asset::factory()->create([
        'name'     => 'QA Category Label Asset',
        'category' => AssetCategory::Equipment,
    ]);

    Livewire::test(ListAssets::class)
        ->assertCanSeeTableRecords([$asset])
        ->assertSee(__('assets-extensions::categories.equipment'));
});

it('casts asset category to enum instances', function (): void {
    $asset = Asset::factory()->create([
        'category' => AssetCategory::Vehicle,
    ]);

    expect($asset->fresh()->category)->toBe(AssetCategory::Vehicle);
});
