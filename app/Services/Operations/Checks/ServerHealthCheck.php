<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use App\Settings\OperationsSettings;
use App\Support\Operations\OperationsSettingsResolver;

class ServerHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'server';
    }

    public function label(): string
    {
        return __('operations.health.checks.server');
    }

    public function run(): HealthCheckResult
    {
        $settings = OperationsSettingsResolver::resolve();

        $disk = $this->diskMetrics();
        $memory = $this->memoryMetrics();
        $load = $this->loadMetrics();

        $status = HealthStatus::Healthy;

        if (($disk['percent_used'] ?? null) !== null) {
            $status = HealthStatus::worst(
                $status,
                $this->diskStatus((float) $disk['percent_used'], $settings),
            );
        }

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: ($disk['percent_used'] ?? null) !== null
                ? __('operations.health.server.disk_used', ['percent' => $disk['percent_used']])
                : __('operations.health.server.partial'),
            metrics: [
                'os'                  => php_uname('s').' '.php_uname('r'),
                'php_memory_limit'    => ini_get('memory_limit') ?: __('operations.health.unavailable'),
                'upload_max_filesize' => ini_get('upload_max_filesize') ?: __('operations.health.unavailable'),
                'disk'                => $disk,
                'memory'              => $memory,
                'load'                => $load,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function diskMetrics(): array
    {
        $path = storage_path();

        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return [
                'status'       => __('operations.health.unavailable'),
                'free'         => null,
                'total'        => null,
                'percent_used' => null,
            ];
        }

        $used = $total - $free;

        return [
            'status'       => 'available',
            'free'         => $this->formatBytes((int) $free),
            'total'        => $this->formatBytes((int) $total),
            'used'         => $this->formatBytes((int) $used),
            'percent_used' => round(($used / $total) * 100, 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function memoryMetrics(): array
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseIniBytes(ini_get('memory_limit'));

        $metrics = [
            'current' => $this->formatBytes($usage),
            'peak'    => $this->formatBytes($peak),
            'limit'   => $limit > 0 ? $this->formatBytes($limit) : __('operations.health.unavailable'),
        ];

        if ($limit > 0) {
            $metrics['percent_used'] = round(($usage / $limit) * 100, 1);
        }

        return $metrics;
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadMetrics(): array
    {
        if (! function_exists('sys_getloadavg')) {
            return ['status' => __('operations.health.unavailable')];
        }

        $load = sys_getloadavg();

        if ($load === false) {
            return ['status' => __('operations.health.unavailable')];
        }

        return [
            'status' => 'available',
            '1m'     => round($load[0], 2),
            '5m'     => round($load[1], 2),
            '15m'    => round($load[2], 2),
        ];
    }

    protected function diskStatus(float $percentUsed, OperationsSettings $settings): HealthStatus
    {
        return match (true) {
            $percentUsed >= $settings->disk_critical_threshold  => HealthStatus::Critical,
            $percentUsed >= $settings->disk_warning_threshold   => HealthStatus::Warning,
            default                                             => HealthStatus::Healthy,
        };
    }

    protected function parseIniBytes(string|false $value): int
    {
        if ($value === false || $value === '-1') {
            return 0;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g'     => $number * 1024 * 1024 * 1024,
            'm'     => $number * 1024 * 1024,
            'k'     => $number * 1024,
            default => (int) $value,
        };
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
