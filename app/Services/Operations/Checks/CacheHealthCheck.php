<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use Illuminate\Support\Facades\Cache;

class CacheHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return __('operations.health.checks.cache');
    }

    public function run(): HealthCheckResult
    {
        $driver = (string) config('cache.default');
        $probeKey = 'operations:health:cache:'.uniqid('', true);

        try {
            $started = microtime(true);
            Cache::put($probeKey, 'ok', now()->addMinute());
            $value = Cache::get($probeKey);
            Cache::forget($probeKey);
            $responseMs = (int) round((microtime(true) - $started) * 1000);

            $healthy = $value === 'ok';

            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: $healthy ? HealthStatus::Healthy : HealthStatus::Critical,
                summary: $healthy
                    ? __('operations.health.cache.connected', ['ms' => $responseMs])
                    : __('operations.health.cache.failed'),
                metrics: [
                    'driver'      => $driver,
                    'response_ms' => $responseMs,
                ],
            );
        } catch (\Throwable) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: HealthStatus::Critical,
                summary: __('operations.health.cache.failed'),
                metrics: ['driver' => $driver],
                message: __('operations.health.cache.error'),
            );
        }
    }
}
