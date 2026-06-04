<?php

use App\Support\PermissionTables;
use Illuminate\Support\Facades\Schema;

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
