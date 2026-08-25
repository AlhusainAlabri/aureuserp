<?php

namespace App\Console\Commands;

use App\Mail\Notifications\NotificationDailyReportMail;
use App\Services\Notifications\NotificationOperationsReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ReportDailyNotifications extends Command
{
    protected $signature = 'notifications:report-daily {--dry-run : Print the report without sending email} {--notify : Email the report to configured ops recipients}';

    protected $description = 'Summarize notification, email, and queue health for the last 24 hours';

    public function handle(NotificationOperationsReport $reportService): int
    {
        $report = $reportService->build();
        $this->renderReport($report);

        if ($this->option('dry-run')) {
            $this->info(__('notifications.operations.report.dry_run'));

            return self::SUCCESS;
        }

        if ($this->option('notify')) {
            $this->sendReport($reportService, $report);
        }

        return $report['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    /** @param  array<string, mixed>  $report */
    protected function renderReport(array $report): void
    {
        $this->line(__('notifications.operations.report.heading', [
            'from' => $report['period']['from'],
            'to'   => $report['period']['to'],
        ]));

        $this->table(
            [__('notifications.operations.report.metric'), __('notifications.operations.report.value')],
            [
                [__('notifications.operations.report.email_queued'), (string) $report['email_logs']['queued']],
                [__('notifications.operations.report.email_sent'), (string) $report['email_logs']['sent']],
                [__('notifications.operations.report.email_failed'), (string) $report['email_logs']['failed']],
                [__('notifications.operations.report.email_failure_rate'), number_format($report['email_logs']['failure_rate'] * 100, 2).'%'],
                [__('notifications.operations.report.failed_jobs_period'), (string) $report['failed_jobs']['last_period']],
                [__('notifications.operations.report.failed_jobs_unresolved'), (string) $report['failed_jobs']['unresolved']],
                [__('notifications.operations.report.queue_pending'), (string) $report['queue']['pending']],
                [__('notifications.operations.report.database_notifications'), (string) $report['database_notifications']['created']],
            ],
        );

        if ($report['alerts'] !== []) {
            $this->warn(__('notifications.operations.report.alerts_heading'));

            foreach ($report['alerts'] as $alert) {
                $this->warn('- '.$alert);
            }
        } else {
            $this->info(__('notifications.operations.report.healthy'));
        }
    }

    /** @param  array<string, mixed>  $report */
    protected function sendReport(NotificationOperationsReport $reportService, array $report): void
    {
        $recipients = $reportService->recipientEmails();

        if ($recipients->isEmpty()) {
            $this->warn(__('notifications.operations.report.no_recipients'));

            return;
        }

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new NotificationDailyReportMail($report));
        }

        $this->info(__('notifications.operations.report.sent', [
            'count' => $recipients->count(),
        ]));
    }
}
