<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;

class ExternalServicesHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'external_services';
    }

    public function label(): string
    {
        return __('operations.health.checks.external_services');
    }

    public function run(): HealthCheckResult
    {
        $services = [
            'mail' => $this->mailStatus(),
            's3'   => $this->s3Status(),
        ];

        $statuses = collect($services)->pluck('status');

        $overall = match (true) {
            $statuses->contains('down')                                                                         => HealthStatus::Critical,
            $statuses->contains('warning')                                                                      => HealthStatus::Warning,
            $statuses->every(fn (string $state): bool => in_array($state, ['healthy', 'not_configured'], true)) => HealthStatus::Healthy,
            default                                                                                             => HealthStatus::Unknown,
        };

        $configuredCount = collect($services)
            ->filter(fn (array $service): bool => $service['status'] !== 'not_configured')
            ->count();

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $overall,
            summary: __('operations.health.external_services.summary', [
                'configured' => $configuredCount,
                'total'      => count($services),
            ]),
            metrics: [
                'services' => $services,
            ],
        );
    }

    /**
     * @return array{status: string, label: string, driver: string|null}
     */
    protected function mailStatus(): array
    {
        $driver = (string) config('mail.default');

        if (in_array($driver, ['log', 'array'], true)) {
            return [
                'status' => 'healthy',
                'label'  => __('operations.health.external_services.mail_log'),
                'driver' => $driver,
            ];
        }

        if ($driver === 'smtp' && ! filled(config('mail.mailers.smtp.host')) && ! filled(config('mail.mailers.smtp.url'))) {
            return [
                'status' => 'not_configured',
                'label'  => __('operations.health.external_services.mail_not_configured'),
                'driver' => $driver,
            ];
        }

        return [
            'status' => 'healthy',
            'label'  => __('operations.health.external_services.mail_ready'),
            'driver' => $driver,
        ];
    }

    /**
     * @return array{status: string, label: string, disk: string|null}
     */
    protected function s3Status(): array
    {
        $key = config('filesystems.disks.s3.key');
        $bucket = config('filesystems.disks.s3.bucket');

        if (! filled($key) || ! filled($bucket)) {
            return [
                'status' => 'not_configured',
                'label'  => __('operations.health.external_services.s3_not_configured'),
                'disk'   => null,
            ];
        }

        return [
            'status' => 'healthy',
            'label'  => __('operations.health.external_services.s3_ready'),
            'disk'   => 's3',
        ];
    }
}
