<?php

namespace Webkul\Assets\Jobs;

use App\Services\Assets\AssetBorrowingNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Assets\Models\AssetBorrowing;

class NotifyOverdueAssetBorrowingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AssetBorrowing $borrowing,
    ) {}

    public function handle(AssetBorrowingNotificationService $notifications): void
    {
        $this->borrowing->loadMissing(['asset', 'employee']);

        $this->borrowing->markOverdue();

        $notifications->notifyOverdue($this->borrowing->fresh());
    }
}
