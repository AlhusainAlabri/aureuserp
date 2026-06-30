<?php

use App\Filament\Assets\Concerns\ConfiguresAssetBorrowingTable;
use App\Filament\Assets\Pages\MyBorrowedAssets;
use App\Filament\Assets\Pages\PendingBorrowingRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

function assetsTablePageUser(string $permission): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);

    test()->actingAs($user);

    return $user;
}

it('scopes pending borrowing requests page to pending status only', function (): void {
    assetsTablePageUser('page_pending_borrowing_requests');

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $pendingAsset = Asset::factory()->create(['status' => AssetStatus::Available]);
    $activeAsset = Asset::factory()->borrowed()->create();

    $pending = AssetBorrowing::query()->create([
        'asset_id'     => $pendingAsset->id,
        'employee_id'  => $employee->id,
        'due_at'       => now()->addDays(3),
        'status'       => BorrowingStatus::Pending,
        'requested_by' => auth()->id(),
    ]);

    $active = AssetBorrowing::query()->create([
        'asset_id'    => $activeAsset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(3),
        'borrowed_at' => now(),
        'status'      => BorrowingStatus::Active,
        'borrowed_by' => auth()->id(),
    ]);

    Livewire::test(PendingBorrowingRequests::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$active]);
});

it('scopes my borrowed assets page to the signed-in employee only', function (): void {
    $user = assetsTablePageUser('page_my_borrowed_assets');

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $employee->update(['user_id' => $user->id]);

    $otherEmployee = Employee::query()
        ->where('id', '!=', $employee->id)
        ->first() ?? Employee::factory()->create();

    $myAsset = Asset::factory()->borrowed()->create();
    $otherAsset = Asset::factory()->borrowed()->create();

    $myBorrowing = AssetBorrowing::query()->create([
        'asset_id'    => $myAsset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(4),
        'borrowed_at' => now(),
        'status'      => BorrowingStatus::Active,
        'borrowed_by' => $user->id,
    ]);

    $otherBorrowing = AssetBorrowing::query()->create([
        'asset_id'    => $otherAsset->id,
        'employee_id' => $otherEmployee->id,
        'due_at'      => now()->addDays(4),
        'borrowed_at' => now(),
        'status'      => BorrowingStatus::Active,
        'borrowed_by' => $user->id,
    ]);

    Livewire::test(MyBorrowedAssets::class)
        ->assertCanSeeTableRecords([$myBorrowing])
        ->assertCanNotSeeTableRecords([$otherBorrowing]);
});

it('formats invalid borrowing dates as placeholders instead of negative years', function (): void {
    expect(ConfiguresAssetBorrowingTable::formatBorrowingDateTime(null))->toBeNull()
        ->and(ConfiguresAssetBorrowingTable::formatBorrowingDateTime('0000-00-00 00:00:00'))->toBeNull();
});
