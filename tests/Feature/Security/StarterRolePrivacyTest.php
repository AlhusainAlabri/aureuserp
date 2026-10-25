<?php

use App\Services\Security\RoleTemplateService;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('role-templates', require config_path('role-templates.php'));
});

it('keeps payroll data away from the plain employee role', function (): void {
    $permissions = app(RoleTemplateService::class)->resolvePermissions('employee');

    $payrollData = $permissions->filter(fn (string $permission): bool => str_contains($permission, 'payroll_'));

    expect($payrollData->all())->toBe([]);
});

it('lets the HR manager role work with payroll', function (): void {
    $permissions = app(RoleTemplateService::class)->resolvePermissions('hr_manager');

    expect($permissions->contains('view_any_payroll_payslip'))->toBeTrue();
});
