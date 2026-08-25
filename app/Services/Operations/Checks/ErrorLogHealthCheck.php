<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use Illuminate\Support\Facades\File;

class ErrorLogHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'errors';
    }

    public function label(): string
    {
        return __('operations.health.checks.errors');
    }

    public function run(): HealthCheckResult
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: HealthStatus::Healthy,
                summary: __('operations.health.errors.none'),
                metrics: ['recent' => []],
            );
        }

        $entries = $this->recentErrors($logPath);
        $criticalCount = collect($entries)->where('level', 'ERROR')->count();

        $status = match (true) {
            $criticalCount >= 10 => HealthStatus::Critical,
            $criticalCount >= 3  => HealthStatus::Warning,
            default              => HealthStatus::Healthy,
        };

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            summary: $entries === []
                ? __('operations.health.errors.none_recent')
                : __('operations.health.errors.recent_count', ['count' => count($entries)]),
            metrics: [
                'recent'         => $entries,
                'error_count'    => $criticalCount,
            ],
        );
    }

    /**
     * @return array<int, array{time: ?string, level: string, message: string}>
     */
    protected function recentErrors(string $logPath): array
    {
        $lines = $this->tailLines($logPath, (int) config('operations.health.error_log_lines', 50));
        $entries = [];

        foreach ($lines as $line) {
            if (! preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY): /', $line, $matches)) {
                continue;
            }

            $entries[] = [
                'time'    => $this->extractTimestamp($line),
                'level'   => $matches[1],
                'message' => $this->sanitizeMessage($line),
            ];
        }

        return array_slice(array_reverse($entries), 0, 10);
    }

    /**
     * @return array<int, string>
     */
    protected function tailLines(string $path, int $lines): array
    {
        $content = File::get($path);

        return array_slice(explode("\n", $content), -$lines);
    }

    protected function extractTimestamp(string $line): ?string
    {
        if (preg_match('/^\[([^\]]+)\]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function sanitizeMessage(string $line): string
    {
        $message = preg_replace('/^\[[^\]]+\]\s+\w+\.\w+:\s*/', '', $line) ?? $line;
        $message = preg_replace('/(password|token|secret|api_key|authorization)[^\s]*/i', '[redacted]', $message) ?? $message;

        return str($message)->limit(200)->toString();
    }
}
