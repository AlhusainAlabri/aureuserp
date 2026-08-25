<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\Cache;

class SchedulerHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'scheduler';
    }

    public function label(): string
    {
        return __('operations.health.checks.scheduler');
    }

    public function run(): HealthCheckResult
    {
        $settings = OperationsSettingsResolver::resolve();

        $lastRun = Cache::get(config('operations.cache.scheduler_heartbeat'));

        if ($lastRun === null) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: HealthStatus::Unknown,
                summary: __('operations.health.scheduler.never'),
                metrics: [
                    'last_run' => null,
                    'active'   => false,
                ],
                message: __('operations.health.scheduler.setup_hint'),
            );
        }

        $minutesAgo = now()->diffInMinutes($lastRun);
        $status = match (true) {
            $minutesAgo >= $settings->scheduler_critical_minutes => HealthStatus::Critical,
            $minutesAgo >= $settings->scheduler_warning_minutes  => HealthStatus::Warning,
            default                                              => HealthStatus::Healthy,
        };

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: __('operations.health.scheduler.last_run', [
                'time' => $lastRun->diffForHumans(),
            ]),
            metrics: [
                'last_run'    => $lastRun->toDateTimeString(),
                'minutes_ago' => $minutesAgo,
                'active'      => $status !== HealthStatus::Critical,
            ],
            message: $status !== HealthStatus::Healthy
                ? __('operations.health.scheduler.stale')
                : null,
        );
    }
}
