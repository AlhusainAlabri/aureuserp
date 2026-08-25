<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHealthCheck implements HealthCheckContract
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return __('operations.health.checks.database');
    }

    public function run(): HealthCheckResult
    {
        $driver = (string) config('database.default');
        $connection = config("database.connections.{$driver}");

        try {
            $started = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $responseMs = (int) round((microtime(true) - $started) * 1000);

            $slowThreshold = (int) config('operations.health.slow_database_ms', 500);
            $status = match (true) {
                $responseMs >= $slowThreshold  => HealthStatus::Warning,
                default                        => HealthStatus::Healthy,
            };

            $metrics = [
                'driver'             => (string) ($connection['driver'] ?? $driver),
                'response_ms'        => $responseMs,
                'database_size'      => $this->databaseSize(),
                'pending_migrations' => $this->pendingMigrationCount(),
            ];

            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: $status,
                summary: __('operations.health.database.connected', ['ms' => $responseMs]),
                metrics: $metrics,
                message: $status === HealthStatus::Warning
                    ? __('operations.health.database.slow_connection')
                    : null,
            );
        } catch (\Throwable $exception) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                status: HealthStatus::Critical,
                summary: __('operations.health.database.disconnected'),
                metrics: [
                    'driver' => (string) ($connection['driver'] ?? $driver),
                ],
                message: __('operations.health.database.error'),
            );
        }
    }

    protected function databaseSize(): ?string
    {
        $driver = (string) config("database.connections.{$this->connectionName()}.driver");

        if ($driver !== 'mysql') {
            return null;
        }

        try {
            $database = (string) config("database.connections.{$this->connectionName()}.database");

            $result = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = ?',
                [$database],
            );

            $bytes = (int) ($result->size ?? 0);

            return $this->formatBytes($bytes);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function pendingMigrationCount(): int
    {
        if (! Schema::hasTable('migrations')) {
            return 0;
        }

        try {
            $migrationFiles = collect(glob(database_path('migrations/*.php')) ?: [])
                ->merge(collect(glob(base_path('plugins/webkul/*/database/migrations/*.php')) ?: []))
                ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
                ->unique();

            $ran = DB::table('migrations')->pluck('migration');

            return $migrationFiles->diff($ran)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function connectionName(): string
    {
        return (string) config('database.default');
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
