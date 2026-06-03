<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Filament\Resources\AssetResource\Pages\ViewAsset;
use Webkul\Assets\Jobs\NotifyOverdueAssetBorrowingJob;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

function assetsUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

it('generates asset numbers in AST format', function (): void {
    $asset = Asset::factory()->create(['name' => 'Laptop']);

    expect($asset->asset_number)
        ->toMatch('/^AST-\d{4}-\d{4}$/');
});

it('can borrow and return an asset', function (): void {
    assetsUser([
        'borrow_assets_asset',
        'return_asset_assets_asset',
    ]);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(7),
    ]);

    expect($asset->fresh()->status)->toBe(AssetStatus::Borrowed)
        ->and($borrowing->status)->toBe(BorrowingStatus::Active);

    $borrowing->markReturned();

    expect($asset->fresh()->status)->toBe(AssetStatus::Available)
        ->and($borrowing->fresh()->status)->toBe(BorrowingStatus::Returned)
        ->and($borrowing->fresh()->returned_at)->not->toBeNull();
});

it('marks overdue borrowings and dispatches notification job', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'borrowed_at' => now()->subDays(10),
        'due_at'      => now()->subDay(),
        'status'      => BorrowingStatus::Active,
    ]);

    Queue::fake();

    Artisan::call('assets:notify-overdue-borrowings');

    Queue::assertPushed(NotifyOverdueAssetBorrowingJob::class);
});

it('borrows an asset from the view page via livewire', function (): void {
    assetsUser([
        'view_any_assets_asset',
        'view_assets_asset',
        'borrow_assets_asset',
    ]);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    Livewire::test(ViewAsset::class, ['record' => $asset->id])
        ->callAction('borrow', data: [
            'employee_id' => $employee->id,
            'due_at'      => now()->addDays(7)->format('Y-m-d H:i:s'),
            'notes'       => 'Livewire borrow test',
        ])
        ->assertNotified();

    expect($asset->fresh()->status)->toBe(AssetStatus::Borrowed)
        ->and(AssetBorrowing::query()->where('asset_id', $asset->id)->active()->exists())->toBeTrue();
});

it('returns an asset from the view page via livewire', function (): void {
    assetsUser([
        'view_any_assets_asset',
        'view_assets_asset',
        'return_asset_assets_asset',
    ]);

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(3),
    ]);

    Livewire::test(ViewAsset::class, ['record' => $asset->id])
        ->callAction('return', data: [
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ])
        ->assertNotified();

    expect($asset->fresh()->status)->toBe(AssetStatus::Available)
        ->and(AssetBorrowing::query()->where('asset_id', $asset->id)->active()->exists())->toBeFalse();
});
