<?php

namespace App\Console\Commands;

use App\Enums\Operations\BackupType;
use App\Jobs\Operations\RunBackupJob;
use App\Services\Operations\BackupRetentionService;
use App\Services\Operations\BackupService;
use App\Support\Operations\AutomaticBackupScheduler;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Console\Command;

class RunSystemBackup extends Command
{
    protected $signature = 'system:backup-run
                            {--type= : database, files, or full}
                            {--automatic : Mark backup as automatic}
                            {--sync : Run synchronously}';

    protected $description = 'Create a system backup';

    public function handle(): int
    {
        $settings = OperationsSettingsResolver::resolve();

        if ($this->option('automatic')) {
            if (! AutomaticBackupScheduler::shouldRunNow($settings)) {
                return self::SUCCESS;
            }

            if (AutomaticBackupScheduler::alreadyRanForPeriod($settings)) {
                return self::SUCCESS;
            }

            AutomaticBackupScheduler::markRanForPeriod($settings);
        }

        $typeValue = $this->option('type') ?: $settings->backup_type->value;
        $type = BackupType::from($typeValue);

        if ($this->option('sync')) {
            try {
                app(BackupService::class)
                    ->create($type, (bool) $this->option('automatic'));
                app(BackupRetentionService::class)->clean();
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info(__('operations.backups.created'));

            return self::SUCCESS;
        }

        RunBackupJob::dispatch($type, (bool) $this->option('automatic'));

        $this->info(__('operations.backups.queued'));

        return self::SUCCESS;
    }
}
