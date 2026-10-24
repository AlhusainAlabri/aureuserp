<?php

use Webkul\Employee\Database\Seeders\EmployeeSeeder;
use Webkul\Employee\Models\Employee;

it('does not seed or wipe employees unless demo data is enabled', function (): void {
    config(['erp.seed_demo_data' => false]);

    $before = Employee::query()->count();

    (new EmployeeSeeder)->run();

    expect(Employee::query()->count())->toBe($before)
        ->and(config('erp.seed_demo_data'))->toBeFalse();
});

it('seeds the sample employees when demo data is explicitly enabled', function (): void {
    config(['erp.seed_demo_data' => true]);

    (new EmployeeSeeder)->run();

    expect(Employee::query()->where('name', 'Paul Williams')->exists())->toBeTrue();
});
