<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use Illuminate\Support\Facades\File;

class StorageHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'storage';
    }

    public function label(): string
    {
        return __('operations.health.checks.storage');
    }

    public function run(): HealthCheckResult
    {
        $directories = [
            'application' => storage_path('app'),
            'logs'        => storage_path('logs'),
            'framework'   => storage_path('framework'),
            'private'     => storage_path('app/private'),
            'public'      => storage_path('app/public'),
            'backups'     => storage_path('app/private/backups'),
        ];

        $sizes = [];
        $largest = ['path' => null, 'bytes' => 0];

        foreach ($directories as $name => $path) {
            if (! is_dir($path)) {
                $sizes[$name] = ['size' => '0 B', 'bytes' => 0, 'exists' => false];

                continue;
            }

            $bytes = $this->directorySize($path);
            $sizes[$name] = [
                'size'   => $this->formatBytes($bytes),
                'bytes'  => $bytes,
                'exists' => true,
            ];

            if ($bytes > $largest['bytes']) {
                $largest = ['path' => $name, 'bytes' => $bytes];
            }
        }

        $disk = $this->diskSummary();

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: HealthStatus::Healthy,
            summary: __('operations.health.storage.summary'),
            metrics: [
                'directories'      => $sizes,
                'largest_directory'=> $largest['path'],
                'disk'             => $disk,
            ],
        );
    }

    protected function directorySize(string $path, int $maxDepth = 3): int
    {
        if ($maxDepth < 0 || ! is_dir($path)) {
            return 0;
        }

        $size = 0;

        foreach (File::directories($path) as $directory) {
            $size += $this->directorySize($directory, $maxDepth - 1);
        }

        foreach (File::files($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * @return array<string, mixed>
     */
    protected function diskSummary(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if ($free === false || $total === false || $total <= 0) {
            return ['status' => __('operations.health.unavailable')];
        }

        $used = $total - $free;

        return [
            'free'         => $this->formatBytes((int) $free),
            'total'        => $this->formatBytes((int) $total),
            'used'         => $this->formatBytes((int) $used),
            'percent_used' => round(($used / $total) * 100, 1),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
