<?php

use App\Support\SchemaMemo;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    SchemaMemo::resetForTesting();
});

it('memoizes table existence checks', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('projects_tasks')
        ->once()
        ->andReturn(true);

    expect(SchemaMemo::hasTable('projects_tasks'))->toBeTrue()
        ->and(SchemaMemo::hasTable('projects_tasks'))->toBeTrue();
});

it('memoizes column existence checks', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('employees_employees')
        ->once()
        ->andReturn(true)
        ->shouldReceive('hasColumn')
        ->with('employees_employees', 'is_closed')
        ->once()
        ->andReturn(true);

    expect(SchemaMemo::hasColumn('employees_employees', 'is_closed'))->toBeTrue()
        ->and(SchemaMemo::hasColumn('employees_employees', 'is_closed'))->toBeTrue();
});

it('returns false for columns when table is missing without checking column schema', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('missing_table')
        ->once()
        ->andReturn(false);

    expect(SchemaMemo::hasColumn('missing_table', 'any_column'))->toBeFalse();
});
