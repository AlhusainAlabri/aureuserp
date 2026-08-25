<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\BackupStatus;
use App\Enums\Operations\HealthStatus;
use App\Models\Operations\SystemBackup;
use Illuminate\Support\Facades\Schema;

class BackupHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'backup';
    }

    public function label(): string
    {
        return __('operations.health.checks.backup');
    }

    public function run(): HealthCheckResult
    {
        if (! Schema::hasTable('system_backups')) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: HealthStatus::Unknown,
                summary: __('operations.health.backup.not_ready'),
                metrics: [],
            );
        }

        $lastSuccess = SystemBackup::query()
            ->where('status', BackupStatus::Completed)
            ->latest('completed_at')
            ->first();

        $lastFailed = SystemBackup::query()
            ->where('status', BackupStatus::Failed)
            ->latest('completed_at')
            ->first();

        $total = SystemBackup::query()->count();
        $totalSize = (int) SystemBackup::query()
            ->where('status', BackupStatus::Completed)
            ->sum('size_bytes');

        $status = HealthStatus::Healthy;
        $summary = __('operations.health.backup.no_success');

        if ($lastSuccess) {
            $hoursAgo = now()->diffInHours($lastSuccess->completed_at);
            $summary = __('operations.health.backup.last_success', [
                'time' => $lastSuccess->completed_at?->diffForHumans(),
            ]);

            $status = match (true) {
                $hoursAgo >= 168 => HealthStatus::Critical,
                $hoursAgo >= 48  => HealthStatus::Warning,
                default          => HealthStatus::Healthy,
            };
        } else {
            $status = HealthStatus::Warning;
        }

        if ($lastFailed && ($lastSuccess === null || $lastFailed->completed_at?->gt($lastSuccess->completed_at))) {
            $status = HealthStatus::Critical;
            $summary = __('operations.health.backup.last_failed');
        }

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: $summary,
            metrics: [
                'last_success_at' => $lastSuccess?->completed_at?->toDateTimeString(),
                'last_failed_at'  => $lastFailed?->completed_at?->toDateTimeString(),
                'total_backups'   => $total,
                'total_size'      => $totalSize,
            ],
            message: $lastFailed?->failure_reason,
        );
    }
}
