<?php

namespace App\Services\Operations;

use App\Enums\Operations\HealthStatus;
use App\Mail\Operations\SystemHealthAlertMail;
use App\Services\Notifications\NotificationDedupService;
use App\Services\Notifications\NotificationOperationsReport;
use App\Services\Operations\Checks\HealthCheckResult;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\Mail;

class HealthAlertService
{
    public function __construct(
        private readonly NotificationDedupService $dedup,
        private readonly NotificationOperationsReport $operationsReport,
    ) {}

    /**
     * @param  array<int, HealthCheckResult>  $results
     */
    public function process(array $results, HealthStatus $overall): void
    {
        $settings = OperationsSettingsResolver::resolve();

        if (! $settings->alerts_enabled) {
            return;
        }

        $recipients = $this->operationsReport->recipientEmails();

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($results as $result) {
            $this->maybeAlertComponent($result->key, $result->status, $recipients->all(), [
                'label'   => $result->label,
                'summary' => $result->summary,
                'message' => $result->message,
            ]);
        }

        $this->maybeAlertComponent('overall', $overall, $recipients->all(), [
            'label'   => __('operations.health.overall'),
            'summary' => __('operations.health.status.'.$overall->value),
        ]);
    }

    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $context
     */
    protected function maybeAlertComponent(string $component, HealthStatus $status, array $recipients, array $context): void
    {
        $cacheKey = "health:{$component}:{$status->value}";
        $previousKey = "health:{$component}:previous";

        $previous = cache()->get($previousKey);
        cache()->put($previousKey, $status->value, now()->addDays(7));

        $shouldAlert = match (true) {
            in_array($status, [HealthStatus::Warning, HealthStatus::Critical], true)
                && $previous !== $status->value => true,
            $status === HealthStatus::Healthy
                && in_array($previous, [HealthStatus::Warning->value, HealthStatus::Critical->value], true) => true,
            default                                                                                         => false,
        };

        if (! $shouldAlert) {
            return;
        }

        $dedupKey = "health_alert:{$component}:{$status->value}";

        $this->dedup->rememberIfNotSent($dedupKey, function () use ($recipients, $component, $status, $context): void {
            foreach ($recipients as $email) {
                Mail::to($email)->queue(new SystemHealthAlertMail($component, $status, $context));
            }
        }, ttlHours: 6);
    }
}
