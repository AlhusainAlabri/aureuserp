<?php

use App\Services\Assets\AssetBorrowingNotificationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    Storage::fake('private');
    $this->actingAs(User::factory()->create());

    $this->mock(AssetBorrowingNotificationService::class, function ($mock): void {
        $mock->shouldReceive('notifySubmitted')->andReturnNull();
        $mock->shouldReceive('notifyApproved')->andReturnNull();
        $mock->shouldReceive('notifyRejected')->andReturnNull();
        $mock->shouldReceive('notifyReturned')->andReturnNull();
    });
});

const SIGNATURE_DATA_URL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

it('stores borrow signature on private disk when approving', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

    $borrowing = AssetBorrowing::submitRequest($asset, $employee, now()->addDays(4));
    $borrowing->approve(SIGNATURE_DATA_URL);

    $path = $borrowing->fresh()->borrow_signature_path;

    expect($path)->not->toBeNull()
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});

it('stores return signature on private disk when returning', function (): void {
    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    $borrowing = AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'due_at'      => now()->addDays(2),
        'status'      => BorrowingStatus::Active,
        'borrowed_at' => now(),
    ]);

    $borrowing->markReturned(SIGNATURE_DATA_URL);

    $path = $borrowing->fresh()->return_signature_path;

    expect($path)->not->toBeNull()
        ->and(Storage::disk('private')->exists($path))->toBeTrue();
});
