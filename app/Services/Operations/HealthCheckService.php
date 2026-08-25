<?php

namespace App\Services\Operations;

use App\Enums\Operations\HealthStatus;
use App\Models\Operations\SystemHealthSnapshot;
use App\Models\Operations\SystemOperationLog;
use App\Services\Operations\Checks\ApplicationHealthCheck;
use App\Services\Operations\Checks\BackupHealthCheck;
use App\Services\Operations\Checks\CacheHealthCheck;
use App\Services\Operations\Checks\DatabaseHealthCheck;
use App\Services\Operations\Checks\ErrorLogHealthCheck;
use App\Services\Operations\Checks\ExternalServicesHealthCheck;
use App\Services\Operations\Checks\HealthCheckContract;
use App\Services\Operations\Checks\HealthCheckResult;
use App\Services\Operations\Checks\NotificationHealthCheck;
use App\Services\Operations\Checks\QueueHealthCheck;
use App\Services\Operations\Checks\SchedulerHealthCheck;
use App\Services\Operations\Checks\ServerHealthCheck;
use App\Services\Operations\Checks\StorageHealthCheck;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class HealthCheckService
{
    /** @var list<class-string<HealthCheckContract>> */
    protected array $checks = [
        ApplicationHealthCheck::class,
        DatabaseHealthCheck::class,
        ServerHealthCheck::class,
        StorageHealthCheck::class,
        QueueHealthCheck::class,
        SchedulerHealthCheck::class,
        CacheHealthCheck::class,
        NotificationHealthCheck::class,
        ExternalServicesHealthCheck::class,
        ErrorLogHealthCheck::class,
        BackupHealthCheck::class,
    ];

    public function __construct(
        private readonly HealthAlertService $alerts,
    ) {}

    /**
     * @return array{
     *     overall_status: HealthStatus,
     *     checks: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     checked_at: string,
     * }
     */
    public function run(string $triggeredBy = 'scheduled', ?User $user = null): array
    {
        $results = collect($this->checks)
            ->map(fn (string $class): HealthCheckResult => app($class)->run())
            ->values();

        $overall = HealthStatus::worst(...$results->map->status->all());

        $payload = [
            'overall_status' => $overall,
            'checks'         => $results->map->toArray()->all(),
            'summary'        => [
                'healthy'  => $results->filter(fn (HealthCheckResult $result): bool => $result->status === HealthStatus::Healthy)->count(),
                'warning'  => $results->filter(fn (HealthCheckResult $result): bool => $result->status === HealthStatus::Warning)->count(),
                'critical' => $results->filter(fn (HealthCheckResult $result): bool => $result->status === HealthStatus::Critical)->count(),
                'unknown'  => $results->filter(fn (HealthCheckResult $result): bool => $result->status === HealthStatus::Unknown)->count(),
            ],
            'checked_at' => now()->toDateTimeString(),
        ];

        $this->storeSnapshot($payload, $triggeredBy, $user);
        $this->alerts->process($results->all(), $overall);
        $this->pruneHistory();

        if ($triggeredBy === 'manual' && Schema::hasTable('system_operation_logs')) {
            SystemOperationLog::record('health_check.manual', metadata: [
                'overall_status' => $overall->value,
            ], user: $user);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latest(): ?array
    {
        $cached = Cache::get(config('operations.cache.latest_snapshot'));

        if (is_array($cached)) {
            return $cached;
        }

        if (! Schema::hasTable('system_health_snapshots')) {
            return null;
        }

        $snapshot = SystemHealthSnapshot::query()->latest('checked_at')->first();

        if (! $snapshot) {
            return null;
        }

        return [
            'overall_status' => $snapshot->overall_status,
            'checks'         => $snapshot->checks,
            'summary'        => $snapshot->summary,
            'checked_at'     => $snapshot->checked_at?->toDateTimeString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function storeSnapshot(array $payload, string $triggeredBy, ?User $user): void
    {
        Cache::put(
            config('operations.cache.latest_snapshot'),
            $payload,
            now()->addSeconds((int) config('operations.cache.ttl_seconds', 300)),
        );

        if (! Schema::hasTable('system_health_snapshots')) {
            return;
        }

        SystemHealthSnapshot::query()->create([
            'overall_status'          => $payload['overall_status'],
            'checks'                  => $payload['checks'],
            'summary'                 => $payload['summary'],
            'triggered_by'            => $triggeredBy,
            'triggered_by_user_id'    => $user?->getKey(),
            'checked_at'              => $payload['checked_at'],
        ]);
    }

    protected function pruneHistory(): void
    {
        if (! Schema::hasTable('system_health_snapshots')) {
            return;
        }

        $settings = OperationsSettingsResolver::resolve();

        SystemHealthSnapshot::query()
            ->where('checked_at', '<', now()->subDays($settings->health_history_retention_days))
            ->delete();
    }
}
