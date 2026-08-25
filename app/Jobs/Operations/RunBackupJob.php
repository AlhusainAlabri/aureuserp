<?php

namespace App\Jobs\Operations;

use App\Enums\Operations\BackupType;
use App\Mail\Operations\SystemBackupFailedMail;
use App\Services\Operations\BackupRetentionService;
use App\Services\Operations\BackupService;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Webkul\Security\Models\User;

class RunBackupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BackupType $type,
        public bool $automatic = false,
        public ?int $userId = null,
    ) {}

    public function handle(BackupService $backups, BackupRetentionService $retention): void
    {
        $user = $this->userId ? User::query()->find($this->userId) : null;

        try {
            $backups->create($this->type, $this->automatic, $user);
            $retention->clean();
        } catch (\Throwable $exception) {
            $settings = OperationsSettingsResolver::resolve();

            if ($settings->backup_alert_on_failure) {
                $recipients = collect(explode(',', (string) config('notifications.operations.report_recipients')))
                    ->map(fn (string $email): string => trim($email))
                    ->filter()
                    ->all();

                foreach ($recipients as $email) {
                    Mail::to($email)->queue(new SystemBackupFailedMail($this->type, $exception->getMessage()));
                }
            }

            throw $exception;
        }
    }
}
