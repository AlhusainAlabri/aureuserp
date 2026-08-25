<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationOperationsReport;
use Illuminate\Console\Command;

class NotificationHealthCheck extends Command
{
    protected $signature = 'notifications:health';

    protected $description = 'Exit with a non-zero status when notification or queue thresholds are breached';

    public function handle(NotificationOperationsReport $reportService): int
    {
        $report = $reportService->build();

        if ($report['healthy']) {
            $this->info(__('notifications.operations.report.healthy'));

            return self::SUCCESS;
        }

        foreach ($report['alerts'] as $alert) {
            $this->error($alert);
        }

        return self::FAILURE;
    }
}
