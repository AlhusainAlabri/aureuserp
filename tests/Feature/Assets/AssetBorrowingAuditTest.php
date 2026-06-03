<?php

use App\Services\Assets\AssetBorrowingNotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
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

    $this->actingAs(User::factory()->create());

    $this->mock(AssetBorrowingNotificationService::class, function ($mock): void {
        $mock->shouldReceive('notifySubmitted')->andReturnNull();
        $mock->shouldReceive('notifyApproved')->andReturnNull();
        $mock->shouldReceive('notifyRejected')->andReturnNull();
        $mock->shouldReceive('notifyReturned')->andReturnNull();
    });
});

it('creates immutable audit events for borrowing transitions', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(3));
    $borrowing->approve(null);
    $borrowing->markReturned(null);

    $events = $borrowing->events()->orderBy('id')->pluck('event_type')->all();

    expect($events)->toContain('submitted', 'approved', 'returned');
});

it('does not update existing audit events', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(2));
    $event = $borrowing->events()->first();

    expect(fn () => $event->update(['event_type' => 'tampered']))
        ->toThrow(RuntimeException::class);
});

it('logs overdue transition via markOverdue', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'borrowed_at' => now()->subDays(5),
        'due_at'      => now()->subDay(),
        'status'      => BorrowingStatus::Active,
    ]);

    $borrowing->markOverdue();

    expect($borrowing->events()->where('event_type', 'overdue')->exists())->toBeTrue()
        ->and($borrowing->fresh()->status)->toBe(BorrowingStatus::Overdue);
});
