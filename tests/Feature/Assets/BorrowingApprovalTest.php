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
use Wezlo\FilamentApproval\Models\Approval;

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

function approvalUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

const TEST_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

it('approves a pending request and marks the asset borrowed', function (): void {
    approvalUser(['approve_borrowing_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(7));
    $borrowing->approve(TEST_SIGNATURE);

    expect($borrowing->fresh()->status)->toBe(BorrowingStatus::Active)
        ->and($borrowing->fresh()->borrow_signature_path)->not->toBeNull()
        ->and($asset->fresh()->status)->toBe(AssetStatus::Borrowed);
});

it('activates borrowing when approval workflow completes', function (): void {
    approvalUser(['approve_borrowing_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(5),
        'status'      => BorrowingStatus::PendingApproval,
    ]);

    $approval = new Approval([
        'status'       => 'approved',
        'submitted_by' => auth()->id(),
        'submitted_at' => now(),
        'completed_at' => now(),
    ]);

    $borrowing->onApprovalApproved($approval);

    expect($borrowing->fresh()->status)->toBe(BorrowingStatus::Active)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Borrowed)
        ->and($borrowing->events()->where('event_type', 'approval_approved')->exists())->toBeTrue();
});

it('rejects borrowing when approval workflow is rejected', function (): void {
    approvalUser(['reject_borrowing_assets_asset']);
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(5),
        'status'      => BorrowingStatus::PendingApproval,
    ]);

    $approval = new Approval([
        'status'       => 'rejected',
        'submitted_by' => auth()->id(),
        'submitted_at' => now(),
        'completed_at' => now(),
    ]);

    $borrowing->onApprovalRejected($approval);

    expect($borrowing->fresh()->status)->toBe(BorrowingStatus::Rejected)
        ->and($asset->fresh()->status)->toBe(AssetStatus::Available);
});
