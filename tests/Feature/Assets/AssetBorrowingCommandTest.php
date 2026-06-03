<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Jobs\NotifyDueSoonAssetBorrowingJob;
use Webkul\Assets\Jobs\NotifyOverdueAssetBorrowingJob;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;

beforeEach(function (): void {
    if (! Schema::hasTable('assets')) {
        Artisan::call('assets:install', ['--no-interaction' => true]);
    }
});

it('queues due-soon reminder jobs for active borrowings due within three days', function (): void {
    Queue::fake();

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'borrowed_at' => now()->subDay(),
        'due_at'      => now()->addDays(2),
        'status'      => BorrowingStatus::Active,
    ]);

    Artisan::call('assets:notify-due-soon');

    Queue::assertPushed(NotifyDueSoonAssetBorrowingJob::class);
});

it('marks borrowings overdue and queues notification job', function (): void {
    Queue::fake();

    $employee = Employee::query()->first() ?? Employee::factory()->create();
    $asset = Asset::factory()->borrowed()->create();

    AssetBorrowing::query()->create([
        'asset_id'    => $asset->id,
        'employee_id' => $employee->id,
        'borrowed_at' => now()->subDays(10),
        'due_at'      => now()->subDay(),
        'status'      => BorrowingStatus::Active,
    ]);

    Artisan::call('assets:notify-overdue-borrowings');

    Queue::assertPushed(NotifyOverdueAssetBorrowingJob::class);
});
