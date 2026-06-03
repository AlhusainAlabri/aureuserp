<?php

namespace Webkul\Assets\Jobs;

use App\Services\Assets\AssetBorrowingNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Assets\Models\AssetBorrowing;

class NotifyDueSoonAssetBorrowingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AssetBorrowing $borrowing,
    ) {}

    public function handle(AssetBorrowingNotificationService $notifications): void
    {
        $notifications->notifyDueReminder($this->borrowing);
    }
}
