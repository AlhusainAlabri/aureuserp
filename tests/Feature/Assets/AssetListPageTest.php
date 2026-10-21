<?php

use App\Filament\Assets\Resources\AssetBorrowingResource\Pages\ViewAssetBorrowing;
use App\Filament\Assets\Resources\AssetBorrowingResource\RelationManagers\EventsRelationManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\EditAsset;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ListAssets;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ViewAsset;
use Webkul\Assets\Filament\Resources\AssetResource\RelationManagers\BorrowingRelationManager;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

function assetListPageUser(array $permissions): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

it('renders the view-all-assets list when there are no records', function (): void {
    app()->setLocale('ar');

    assetListPageUser([
        'view_any_assets_asset',
        'view_assets_asset',
        'create_assets_asset',
    ]);

    Livewire::test(ListAssets::class)
        ->assertSuccessful()
        ->assertSee(__('assets::assets.actions.create'));
});

it('renders the view-all-assets list with borrowed assets and numeric values', function (): void {
    app()->setLocale('ar');

    assetListPageUser([
        'view_any_assets_asset',
        'view_assets_asset',
        'create_assets_asset',
    ]);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create([
        'name'  => 'QA Borrowed List Asset',
        'value' => 125.5,
    ]);

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(4),
        'borrowed_at' => now(),
        'status'      => BorrowingStatus::Active,
        'borrowed_by' => auth()->id(),
    ]);

    Livewire::test(ListAssets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$asset])
        ->assertSee('QA Borrowed List Asset')
        ->assertSee(__('assets::assets.actions.create'));
});

it('shows a borrow add action on the asset borrowing history relation manager', function (): void {
    assetListPageUser([
        'view_any_assets_asset',
        'view_assets_asset',
        'borrow_assets_asset',
    ]);

    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    Livewire::test(BorrowingRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass'   => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('borrow')
        ->assertSee(__('assets::assets.empty.no_borrowings'));

    Livewire::test(BorrowingRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass'   => EditAsset::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('borrow');
});

it('loads the borrowing audit relation manager without a create action', function (): void {
    $user = assetListPageUser([
        'view_assets_asset_borrowing',
        'view_any_assets_asset_borrowing',
    ]);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create();
    $borrowing = AssetBorrowing::query()->create([
        'asset_id'     => $asset->id,
        'employee_id'  => $employee->id,
        'due_at'       => now()->addDays(5),
        'status'       => BorrowingStatus::Pending,
        'requested_by' => $user->id,
    ]);

    Livewire::test(ViewAssetBorrowing::class, ['record' => $borrowing->id])
        ->assertSuccessful();

    Livewire::test(EventsRelationManager::class, [
        'ownerRecord' => $borrowing,
        'pageClass'   => ViewAssetBorrowing::class,
    ])
        ->assertSuccessful();
});

it('builds the assets index url from the resource', function (): void {
    expect(AssetResource::getUrl('index'))->toContain('assets/assets');
});

it('continues asset numbers after a soft-deleted asset', function (): void {
    $deleted = Asset::factory()->create();
    $deleted->delete();

    $next = Asset::factory()->create();

    $deletedSequence = (int) substr((string) $deleted->asset_number, -4);
    $nextSequence = (int) substr((string) $next->asset_number, -4);

    expect($next->asset_number)->toStartWith('AST-'.now()->year.'-')
        ->and($nextSequence)->toBe($deletedSequence + 1);
});
