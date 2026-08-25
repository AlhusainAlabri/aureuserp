<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use Illuminate\Support\Facades\App;

class ApplicationHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'application';
    }

    public function label(): string
    {
        return __('operations.health.checks.application');
    }

    public function run(): HealthCheckResult
    {
        $maintenance = App::isDownForMaintenance();

        $status = $maintenance ? HealthStatus::Warning : HealthStatus::Healthy;

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: $maintenance
                ? __('operations.health.application.maintenance')
                : __('operations.health.application.running'),
            metrics: [
                'environment'      => App::environment(),
                'php_version'      => PHP_VERSION,
                'laravel_version'  => App::version(),
                'timezone'         => (string) config('app.timezone'),
                'debug_mode'       => (bool) config('app.debug'),
                'maintenance_mode' => $maintenance,
                'cache_driver'     => (string) config('cache.default'),
                'session_driver'   => (string) config('session.driver'),
                'server_time'      => now()->toDateTimeString(),
            ],
            message: $maintenance ? __('operations.health.application.maintenance_hint') : null,
        );
    }
}
