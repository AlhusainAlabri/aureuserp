<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'queue';
    }

    public function label(): string
    {
        return __('operations.health.checks.queue');
    }

    public function run(): HealthCheckResult
    {
        $settings = OperationsSettingsResolver::resolve();

        $connection = (string) config('queue.default');
        $pending = $this->count('jobs');
        $failed = $this->count('failed_jobs');
        $oldestPending = $this->oldestPendingJob();

        $status = HealthStatus::Healthy;

        if ($failed >= $settings->failed_jobs_threshold) {
            $status = HealthStatus::Critical;
        } elseif ($failed > 0) {
            $status = HealthStatus::Warning;
        }

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: __('operations.health.queue.summary', [
                'pending' => $pending,
                'failed'  => $failed,
            ]),
            metrics: [
                'connection'     => $connection,
                'pending_jobs'   => $pending,
                'failed_jobs'    => $failed,
                'oldest_pending' => $oldestPending,
            ],
            message: $failed >= $settings->failed_jobs_threshold
                ? __('operations.health.queue.too_many_failed')
                : null,
        );
    }

    protected function count(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    protected function oldestPendingJob(): ?string
    {
        if (! Schema::hasTable('jobs')) {
            return null;
        }

        $createdAt = DB::table('jobs')->min('created_at');

        return $createdAt ? (string) $createdAt : null;
    }
}
