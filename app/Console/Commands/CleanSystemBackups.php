<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupRetentionService;
use Illuminate\Console\Command;

class CleanSystemBackups extends Command
{
    protected $signature = 'system:backup-clean';

    protected $description = 'Remove expired system backups according to retention settings';

    public function handle(BackupRetentionService $retention): int
    {
        $deleted = $retention->clean();

        $this->info(__('operations.backups.retention_cleaned', ['count' => $deleted]));

        return self::SUCCESS;
    }
}
