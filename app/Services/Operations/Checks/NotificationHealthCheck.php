<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use App\Services\Notifications\NotificationOperationsReport;

class NotificationHealthCheck implements HealthCheckContract
{
    public function __construct(
        private readonly NotificationOperationsReport $operationsReport,
    ) {}

    public function key(): string
    {
        return 'notifications';
    }

    public function label(): string
    {
        return __('operations.health.checks.notifications');
    }

    public function run(): HealthCheckResult
    {
        $report = $this->operationsReport->build();

        $mailConfigured = filled(config('mail.mailers.smtp.host'))
            || filled(config('mail.mailers.smtp.url'))
            || config('mail.default') !== 'smtp';

        $status = match (true) {
            ! $report['healthy']   => HealthStatus::Critical,
            ! $mailConfigured      => HealthStatus::Warning,
            default                => HealthStatus::Healthy,
        };

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: $report['healthy']
                ? __('operations.health.notifications.healthy')
                : __('operations.health.notifications.degraded'),
            metrics: [
                'mail_driver'            => (string) config('mail.default'),
                'mail_configured'        => $mailConfigured,
                'queue_pending'          => $report['queue']['pending'],
                'failed_jobs_unresolved' => $report['failed_jobs']['unresolved'],
                'email_failure_rate'     => $report['email_logs']['failure_rate'],
                'alerts'                 => $report['alerts'],
            ],
            message: $report['alerts'][0] ?? null,
        );
    }
}
