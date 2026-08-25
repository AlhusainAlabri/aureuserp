<?php

namespace App\Services\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationOperationsReport
{
    /**
     * @return array{
     *     period: array{from: string, to: string, hours: int},
     *     email_logs: array{queued: int, sent: int, failed: int, total: int, failure_rate: float},
     *     failed_jobs: array{last_period: int, unresolved: int, recent: array<int, array{id: int, queue: string, failed_at: string, exception: string}>},
     *     queue: array{pending: int},
     *     database_notifications: array{created: int},
     *     alerts: array<int, string>,
     *     healthy: bool,
     * }
     */
    public function build(?CarbonInterface $since = null): array
    {
        $hours = (int) config('notifications.operations.report_hours', 24);
        $since ??= now()->subHours($hours);

        $emailLogs = $this->emailLogStats($since);
        $failedJobs = $this->failedJobStats($since);
        $queuePending = $this->pendingJobCount();
        $databaseNotifications = $this->databaseNotificationStats($since);

        $alerts = $this->buildAlerts(
            emailLogs: $emailLogs,
            failedJobsLastPeriod: $failedJobs['last_period'],
            queuePending: $queuePending,
        );

        return [
            'period' => [
                'from'  => $since->toDateTimeString(),
                'to'    => now()->toDateTimeString(),
                'hours' => $hours,
            ],
            'email_logs'             => $emailLogs,
            'failed_jobs'            => $failedJobs,
            'queue'                  => ['pending' => $queuePending],
            'database_notifications' => ['created' => $databaseNotifications],
            'alerts'                 => $alerts,
            'healthy'                => $alerts === [],
        ];
    }

    /** @return array{queued: int, sent: int, failed: int, total: int, failure_rate: float} */
    protected function emailLogStats(CarbonInterface $since): array
    {
        if (! Schema::hasTable('email_logs')) {
            return [
                'queued'        => 0,
                'sent'          => 0,
                'failed'        => 0,
                'total'         => 0,
                'failure_rate'  => 0.0,
            ];
        }

        $counts = DB::table('email_logs')
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->where('created_at', '>=', $since)
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $queued = (int) ($counts['queued'] ?? 0);
        $sent = (int) ($counts['sent'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);
        $total = $queued + $sent + $failed;
        $denominator = max($total, 1);

        return [
            'queued'       => $queued,
            'sent'         => $sent,
            'failed'       => $failed,
            'total'        => $total,
            'failure_rate' => round($failed / $denominator, 4),
        ];
    }

    /**
     * @return array{last_period: int, unresolved: int, recent: array<int, array{id: int, queue: string, failed_at: string, exception: string}>}
     */
    protected function failedJobStats(CarbonInterface $since): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'last_period' => 0,
                'unresolved'  => 0,
                'recent'      => [],
            ];
        }

        $lastPeriod = (int) DB::table('failed_jobs')
            ->where('failed_at', '>=', $since)
            ->count();

        $unresolved = (int) DB::table('failed_jobs')->count();

        $recent = DB::table('failed_jobs')
            ->select(['id', 'queue', 'failed_at', 'exception'])
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'id'         => (int) $row->id,
                'queue'      => (string) $row->queue,
                'failed_at'  => (string) $row->failed_at,
                'exception'  => str($row->exception)->before("\n")->limit(200)->toString(),
            ])
            ->all();

        return [
            'last_period' => $lastPeriod,
            'unresolved'  => $unresolved,
            'recent'      => $recent,
        ];
    }

    protected function pendingJobCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }

    protected function databaseNotificationStats(CarbonInterface $since): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        return (int) DB::table('notifications')
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * @param  array{queued: int, sent: int, failed: int, total: int, failure_rate: float}  $emailLogs
     * @return array<int, string>
     */
    protected function buildAlerts(array $emailLogs, int $failedJobsLastPeriod, int $queuePending): array
    {
        $alerts = [];

        $failedJobThreshold = (int) config('notifications.operations.failed_jobs_threshold', 5);
        $failedEmailRateThreshold = (float) config('notifications.operations.failed_email_rate_threshold', 0.05);
        $queueBacklogThreshold = (int) config('notifications.operations.queue_backlog_threshold', 250);

        if ($failedJobsLastPeriod >= $failedJobThreshold) {
            $alerts[] = __('notifications.operations.alerts.failed_jobs', [
                'count'     => $failedJobsLastPeriod,
                'threshold' => $failedJobThreshold,
            ]);
        }

        if ($emailLogs['total'] > 0 && $emailLogs['failure_rate'] >= $failedEmailRateThreshold) {
            $alerts[] = __('notifications.operations.alerts.failed_emails', [
                'rate'      => number_format($emailLogs['failure_rate'] * 100, 1),
                'threshold' => number_format($failedEmailRateThreshold * 100, 1),
            ]);
        }

        if ($queuePending >= $queueBacklogThreshold) {
            $alerts[] = __('notifications.operations.alerts.queue_backlog', [
                'count'     => $queuePending,
                'threshold' => $queueBacklogThreshold,
            ]);
        }

        return $alerts;
    }

    /** @return Collection<int, string> */
    public function recipientEmails(): Collection
    {
        $raw = (string) config('notifications.operations.report_recipients', '');

        return collect(explode(',', $raw))
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filled($email))
            ->unique()
            ->values();
    }
}
