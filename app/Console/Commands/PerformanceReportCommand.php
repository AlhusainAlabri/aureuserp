<?php

namespace App\Console\Commands;

use App\Support\Performance\SlowQueryRecorder;
use Illuminate\Console\Command;

class PerformanceReportCommand extends Command
{
    protected $signature = 'performance:report
                            {--date= : Report date (Y-m-d), defaults to today}
                            {--limit=25 : Number of requests to show}
                            {--slow-only : Only show requests with slow queries}
                            {--path= : Filter by URL path fragment}
                            {--min-queries=0 : Only show requests with at least this many queries}';

    protected $description = 'Show a readable summary of recorded slow queries and heavy requests';

    public function handle(): int
    {
        if (! config('performance.slow_query_log.enabled', false)) {
            $this->warn('Slow query logging is disabled. Set SLOW_QUERY_LOG_ENABLED=true in .env while testing pages.');
        }

        $date = $this->option('date') ?: now()->format('Y-m-d');
        $entries = collect(SlowQueryRecorder::readEntries($date, 500));

        if ($entries->isEmpty()) {
            $this->info("No performance records found for {$date}.");
            $this->line('Browse the admin panel with SLOW_QUERY_LOG_ENABLED=true, then run this command again.');

            return self::SUCCESS;
        }

        if ($path = $this->option('path')) {
            $entries = $entries->filter(fn (array $entry): bool => str_contains((string) ($entry['path'] ?? ''), (string) $path));
        }

        if ($this->option('slow-only')) {
            $entries = $entries->filter(fn (array $entry): bool => ($entry['slow_query_count'] ?? 0) > 0);
        }

        $minQueries = (int) $this->option('min-queries');

        if ($minQueries > 0) {
            $entries = $entries->filter(fn (array $entry): bool => ($entry['query_count'] ?? 0) >= $minQueries);
        }

        $entries = $entries->take((int) $this->option('limit'));

        if ($entries->isEmpty()) {
            $this->info('No records matched the selected filters.');

            return self::SUCCESS;
        }

        $this->info("Performance report for {$date}");
        $this->newLine();

        $this->table(
            ['Time', 'Method', 'Path', 'Queries', 'Slow', 'Total ms', 'Status'],
            $entries->map(fn (array $entry): array => [
                isset($entry['recorded_at']) ? date('H:i:s', strtotime((string) $entry['recorded_at'])) : '—',
                $entry['method'] ?? '—',
                $this->shortPath((string) ($entry['path'] ?? '—')),
                $entry['query_count'] ?? 0,
                $entry['slow_query_count'] ?? 0,
                $entry['total_query_ms'] ?? 0,
                $entry['status'] ?? '—',
            ])->all(),
        );

        $this->newLine();
        $this->components->info('Slowest requests');

        foreach ($entries->sortByDesc('total_query_ms')->take(5) as $entry) {
            $this->line(sprintf(
                '%s %s — %s queries, %sms total, %s slow',
                $entry['method'] ?? 'GET',
                $entry['url'] ?? ($entry['path'] ?? 'unknown'),
                $entry['query_count'] ?? 0,
                $entry['total_query_ms'] ?? 0,
                $entry['slow_query_count'] ?? 0,
            ));

            foreach (($entry['slow_queries'] ?? []) as $query) {
                $this->line(sprintf(
                    '  • %sms  %s',
                    $query['time_ms'] ?? 0,
                    $this->truncate((string) ($query['sql'] ?? ''), 140),
                ));
            }

            foreach (($entry['duplicate_patterns'] ?? []) as $pattern) {
                if (($pattern['count'] ?? 0) < 3) {
                    continue;
                }

                $this->line(sprintf(
                    '  ↺ %sx (%sms) %s',
                    $pattern['count'] ?? 0,
                    $pattern['total_ms'] ?? 0,
                    $this->truncate((string) ($pattern['sql'] ?? ''), 120),
                ));
            }

            $this->newLine();
        }

        $this->line('Raw log: '.config('performance.slow_query_log.directory')."/requests-{$date}.jsonl");

        return self::SUCCESS;
    }

    protected function shortPath(string $path): string
    {
        return strlen($path) > 60 ? substr($path, 0, 57).'...' : $path;
    }

    protected function truncate(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, $length - 3).'...' : $value;
    }
}
