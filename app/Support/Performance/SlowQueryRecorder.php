<?php

namespace App\Support\Performance;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SlowQueryRecorder
{
    protected ?string $requestId = null;

    /** @var array<string, mixed>|null */
    protected ?array $requestContext = null;

    /** @var list<array{sql: string, time_ms: float, connection: string}> */
    protected array $queries = [];

    /** @var list<array{sql: string, time_ms: float, connection: string}> */
    protected array $slowQueries = [];

    public function isEnabled(): bool
    {
        return (bool) config('performance.slow_query_log.enabled', false);
    }

    public function beginRequest(Request $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->requestId = (string) Str::uuid();
        $this->queries = [];
        $this->slowQueries = [];
        $this->requestContext = [
            'request_id'   => $this->requestId,
            'recorded_at'  => now()->toIso8601String(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'path'         => $request->path(),
            'route'        => $request->route()?->getName(),
            'livewire'     => $request->header('X-Livewire'),
            'user_id'      => $request->user()?->getAuthIdentifier(),
            'ip'           => $request->ip(),
        ];
    }

    public function record(QueryExecuted $query): void
    {
        if (! $this->isEnabled() || $this->requestContext === null) {
            return;
        }

        $entry = [
            'sql'        => $this->normalizeSql($query->sql),
            'time_ms'    => round($query->time, 2),
            'connection' => $query->connectionName,
        ];

        $this->queries[] = $entry;

        if ($query->time >= (int) config('performance.slow_query_log.threshold_ms', 100)) {
            $this->slowQueries[] = $entry;
        }
    }

    public function finishRequest(Response $response): void
    {
        if (! $this->isEnabled() || $this->requestContext === null) {
            return;
        }

        $totalQueryTime = round(collect($this->queries)->sum('time_ms'), 2);
        $queryCount = count($this->queries);
        $slowCount = count($this->slowQueries);
        $duplicatePatterns = $this->duplicatePatterns();

        $shouldPersist = (bool) config('performance.slow_query_log.log_all_requests', true)
            || $slowCount > 0
            || $queryCount >= (int) config('performance.slow_query_log.heavy_query_count', 75);

        if ($shouldPersist) {
            $payload = [
                ...$this->requestContext,
                'status'             => $response->getStatusCode(),
                'query_count'        => $queryCount,
                'slow_query_count'   => $slowCount,
                'total_query_ms'     => $totalQueryTime,
                'slow_queries'       => collect($this->slowQueries)
                    ->sortByDesc('time_ms')
                    ->values()
                    ->take(25)
                    ->all(),
                'duplicate_patterns' => $duplicatePatterns,
            ];

            $this->appendPayload($payload);
        }

        $this->requestId = null;
        $this->requestContext = null;
        $this->queries = [];
        $this->slowQueries = [];
    }

    /**
     * @return list<array{sql: string, count: int, total_ms: float}>
     */
    public function duplicatePatterns(): array
    {
        return collect($this->queries)
            ->groupBy(fn (array $query): string => $query['sql'])
            ->map(fn ($group, string $sql): array => [
                'sql'      => $sql,
                'count'    => $group->count(),
                'total_ms' => round($group->sum('time_ms'), 2),
            ])
            ->filter(fn (array $pattern): bool => $pattern['count'] > 1)
            ->sortByDesc('count')
            ->values()
            ->take(15)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function appendPayload(array $payload): void
    {
        $directory = (string) config('performance.slow_query_log.directory', storage_path('logs/performance'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $directory.'/requests-'.now()->format('Y-m-d').'.jsonl';

        file_put_contents(
            $filename,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }

    protected function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql)) ?? $sql;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function readEntries(?string $date = null, int $limit = 100): array
    {
        $directory = (string) config('performance.slow_query_log.directory', storage_path('logs/performance'));
        $filename = $directory.'/requests-'.($date ?? now()->format('Y-m-d')).'.jsonl';

        if (! is_file($filename)) {
            return [];
        }

        $entries = [];

        foreach (file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }
}
