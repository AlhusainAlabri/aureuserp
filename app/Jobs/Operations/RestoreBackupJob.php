<?php

namespace App\Jobs\Operations;

use App\Models\Operations\SystemBackup;
use App\Services\Operations\RestoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Security\Models\User;

class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $backupId,
        public int $userId,
    ) {}

    public function handle(RestoreService $restoreService): void
    {
        $backup = SystemBackup::query()->findOrFail($this->backupId);
        $user = User::query()->findOrFail($this->userId);

        $restoreService->restore($backup, $user);
    }
}
