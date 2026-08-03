<?php

use App\Support\Performance\RecommendedIndexCatalog;
use App\Support\Performance\SlowQueryRecorder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    Config::set('performance.slow_query_log.enabled', true);
    Config::set('performance.slow_query_log.threshold_ms', 50);
    Config::set('performance.slow_query_log.directory', storage_path('framework/testing/performance-logs'));

    File::cleanDirectory(storage_path('framework/testing/performance-logs'));
});

function recordTestQuery(string $sql, float $timeMs, array $bindings = []): void
{
    app(SlowQueryRecorder::class)->record(new QueryExecuted(
        $sql,
        $bindings,
        $timeMs,
        DB::connection(),
    ));
}

it('records slow queries for a request', function (): void {
    $recorder = app(SlowQueryRecorder::class);
    $request = Request::create('/admin/dashboard', 'GET');

    $recorder->beginRequest($request);

    recordTestQuery('select * from `users` where `id` = ?', 120, [1]);
    recordTestQuery('select * from `settings` where `group` = ?', 5, ['products_product']);

    $recorder->finishRequest(new Response('', 200));

    $entries = SlowQueryRecorder::readEntries(now()->format('Y-m-d'), 10);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['path'])->toBe('admin/dashboard')
        ->and($entries[0]['query_count'])->toBe(2)
        ->and($entries[0]['slow_query_count'])->toBe(1)
        ->and($entries[0]['slow_queries'][0]['sql'])->toContain('from `users`');
});

it('flags duplicate query patterns', function (): void {
    $recorder = app(SlowQueryRecorder::class);
    $recorder->beginRequest(Request::create('/admin', 'GET'));

    foreach (range(1, 4) as $ignored) {
        recordTestQuery('select * from `settings` where `group` = ?', 2, ['products_product']);
    }

    expect($recorder->duplicatePatterns())->toHaveCount(1)
        ->and($recorder->duplicatePatterns()[0]['count'])->toBe(4);
});

it('lists missing recommended indexes from the catalog', function (): void {
    $missing = RecommendedIndexCatalog::missing();

    expect($missing)->toBeArray();

    foreach ($missing as $definition) {
        expect($definition)->toHaveKeys(['table', 'name', 'columns', 'reason', 'priority', 'present'])
            ->and($definition['present'])->toBeFalse();
    }
});
