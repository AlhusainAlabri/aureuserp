<?php

beforeEach(function (): void {
    require_once base_path('plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php');
});

it('refuses destructive bootstrap on non-test database names', function (): void {
    config([
        'database.default'                    => 'mysql',
        'database.connections.mysql.database' => 'nodhum_erp',
    ]);

    expect(fn (): mixed => TestBootstrapHelper::assertSafeTestDatabase())
        ->toThrow(RuntimeException::class);
});

it('allows bootstrap on test database names', function (): void {
    config([
        'database.default'                    => 'mysql',
        'database.connections.mysql.database' => 'nodhum_erp_test',
    ]);

    TestBootstrapHelper::assertSafeTestDatabase();

    expect(true)->toBeTrue();
});
