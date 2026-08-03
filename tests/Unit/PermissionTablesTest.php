<?php

use App\Support\PermissionTables;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    PermissionTables::resetForTesting();
});

it('reports permission tables as not ready when permissions table is missing', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('permissions')
        ->andReturn(false);

    expect(PermissionTables::areReady())->toBeFalse();
});

it('reports permission tables as not ready when roles table is missing', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('permissions')
        ->andReturn(true)
        ->shouldReceive('hasTable')
        ->with('roles')
        ->andReturn(false);

    expect(PermissionTables::areReady())->toBeFalse();
});

it('reports permission tables as ready when both tables exist', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('permissions')
        ->andReturn(true)
        ->shouldReceive('hasTable')
        ->with('roles')
        ->andReturn(true);

    expect(PermissionTables::areReady())->toBeTrue();
});

it('memoizes permission table readiness within a request', function (): void {
    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('permissions')
        ->once()
        ->andReturn(true)
        ->shouldReceive('hasTable')
        ->with('roles')
        ->once()
        ->andReturn(true);

    expect(PermissionTables::areReady())->toBeTrue()
        ->and(PermissionTables::areReady())->toBeTrue();
});
