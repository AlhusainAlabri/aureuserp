<?php

use App\Services\Assets\AssetBorrowingNotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
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

    $this->mock(AssetBorrowingNotificationService::class, function ($mock): void {
        $mock->shouldReceive('notifySubmitted')->andReturnNull();
        $mock->shouldReceive('notifyApproved')->andReturnNull();
        $mock->shouldReceive('notifyRejected')->andReturnNull();
    });
});

function borrowingRequestUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

it('submits a borrowing request as pending without changing asset status', function (): void {
    $user = borrowingRequestUser(['request_borrow_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest(
        $asset,
        $employee,
        now()->addDays(5),
        'Need for field visit',
    );

    expect($borrowing->status)->toBe(BorrowingStatus::Pending)
        ->and($borrowing->requested_by)->toBe($user->id)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Available);
});

it('rejects a pending borrowing request with reason', function (): void {
    $manager = borrowingRequestUser(['reject_borrowing_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(3));

    $borrowing->reject('Not available this week');

    expect($borrowing->fresh()->status)->toBe(BorrowingStatus::Rejected)
        ->and($borrowing->fresh()->rejection_reason)->toBe('Not available this week')
        ->and($borrowing->fresh()->rejected_by)->toBe($manager->id)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Available);
});

it('logs a submitted event when a request is created', function (): void {
    borrowingRequestUser(['request_borrow_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(2));

    expect($borrowing->events()->where('event_type', 'submitted')->exists())->toBeTrue();
});
