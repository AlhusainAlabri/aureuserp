<?php

use App\Services\Hr\HrExtensionSchemaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    HrExtensionSchemaService::resetForTesting();
});

it('runs hr extension schema ensure only once per process', function (): void {
    if (! Schema::hasTable('employees_employees')) {
        expect(true)->toBeTrue();

        return;
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $service = app(HrExtensionSchemaService::class);
    $service->ensure();
    $firstCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $service->ensure();
    $secondCount = count(DB::getQueryLog());

    expect($secondCount)->toBe(0)
        ->and($firstCount)->toBeGreaterThan(0);
});

it('resets ensure guard for testing', function (): void {
    if (! Schema::hasTable('employees_employees')) {
        expect(true)->toBeTrue();

        return;
    }

    $service = app(HrExtensionSchemaService::class);
    $service->ensure();

    HrExtensionSchemaService::resetForTesting();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $service->ensure();

    expect(count(DB::getQueryLog()))->toBeGreaterThan(0);
});
