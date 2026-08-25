<?php

use App\Enums\Operations\HealthStatus;
use App\Services\Operations\Checks\ApplicationHealthCheck;
use App\Services\Operations\HealthCheckService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('system_health_snapshots')) {
        Artisan::call('migrate', [
            '--path'           => 'database/migrations/2026_08_25_164423_create_system_operations_tables.php',
            '--force'          => true,
            '--no-interaction' => true,
        ]);
    }
});

it('returns ok from the public health endpoint when the database is reachable', function (): void {
    $response = $this->get('/health');

    $response->assertSuccessful()->assertJson(['status' => 'ok']);
});

it('runs application health check', function (): void {
    $result = (new ApplicationHealthCheck)->run();

    expect($result->status)->toBeInstanceOf(HealthStatus::class)
        ->and($result->key)->toBe('application');
});

it('stores a health snapshot when running the health check service', function (): void {
    Cache::forget(config('operations.cache.latest_snapshot'));

    $payload = app(HealthCheckService::class)->run('manual');

    expect($payload['overall_status'])->toBeInstanceOf(HealthStatus::class)
        ->and($payload['checks'])->not->toBeEmpty();

    expect(app(HealthCheckService::class)->latest())->not->toBeNull();
});

it('records scheduler heartbeat', function (): void {
    Artisan::call('system:scheduler-heartbeat');

    expect(Cache::get(config('operations.cache.scheduler_heartbeat')))->not->toBeNull();
});

it('skips automatic backup when disabled in settings', function (): void {
    Artisan::call('system:backup-run', ['--automatic' => true]);

    expect(Artisan::output())->toBe('');
});
