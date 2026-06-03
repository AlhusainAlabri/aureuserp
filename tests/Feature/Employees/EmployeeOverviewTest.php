<?php

use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\OverviewEmployee;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Employee\Models\WarningType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

function createOverviewEmployee(bool $isActive = true): Employee
{
    $user = User::factory()->create();
    $company = Company::create([
        'name'       => 'Test Company',
        'company_id' => 'test-'.uniqid(),
    ]);

    return Employee::create([
        'user_id'    => $user->id,
        'creator_id' => $user->id,
        'company_id' => $company->id,
        'name'       => $user->name,
        'work_email' => $user->email,
        'is_active'  => $isActive,
    ]);
}

function createDocument(Employee $employee, ?string $expiryDate = null): EmployeeDocument
{
    return EmployeeDocument::create([
        'employee_id'   => $employee->id,
        'document_type' => 'passport',
        'document_name' => 'Test Document',
        'file_path'     => 'employees/'.$employee->id.'/documents/test.pdf',
        'expiry_date'   => $expiryDate,
        'creator_id'    => $employee->creator_id,
    ]);
}

function createWarning(Employee $employee, bool $isAcknowledged = false): EmployeeWarning
{
    $warningType = WarningType::create([
        'name'       => 'Test Warning',
        'creator_id' => $employee->creator_id,
    ]);

    return EmployeeWarning::create([
        'employee_id'     => $employee->id,
        'warning_type_id' => $warningType->id,
        'subject'         => 'Test Subject',
        'issued_at'       => now(),
        'is_acknowledged' => $isAcknowledged,
        'creator_id'      => $employee->creator_id,
    ]);
}

beforeEach(function (): void {
    $this->user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach (['view_any_employee_employee', 'view_employee_employee'] as $perm) {
        Permission::query()->firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        $this->user->givePermissionTo($perm);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->employee = createOverviewEmployee();
    $this->actingAs($this->user);
});

it('is registered in the resource pages', function (): void {
    expect(EmployeeResource::getPages())->toHaveKey('overview');
});

it('renders the overview page', function (): void {
    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSuccessful();
});

it('shows the inactive banner when the employee is not active', function (): void {
    $inactive = createOverviewEmployee(isActive: false);

    Livewire::test(OverviewEmployee::class, ['record' => $inactive->id])
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.banner.inactive-title'));
});

it('shows the all-clear state when there are no alerts', function (): void {
    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.all-clear.title'));
});

it('counts expired documents correctly', function (): void {
    createDocument($this->employee, now()->subDays(10)->toDateString());

    $component = Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id]);

    expect($component->instance()->getExpiredDocuments())->toHaveCount(1);
    expect($component->instance()->getExpiringSoonDocuments())->toHaveCount(0);
    expect($component->instance()->hasAnyAlerts())->toBeTrue();
});

it('counts expiring-soon documents correctly', function (): void {
    createDocument($this->employee, now()->addDays(15)->toDateString());

    $component = Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id]);

    expect($component->instance()->getExpiringSoonDocuments())->toHaveCount(1);
    expect($component->instance()->getExpiredDocuments())->toHaveCount(0);
});

it('shows unacknowledged warnings as active', function (): void {
    createWarning($this->employee, isAcknowledged: false);

    $component = Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id]);

    expect($component->instance()->getActiveWarnings())->toHaveCount(1);
    expect($component->instance()->hasAnyAlerts())->toBeTrue();
});

it('does not count acknowledged warnings as active', function (): void {
    createWarning($this->employee, isAcknowledged: true);

    $component = Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id]);

    expect($component->instance()->getActiveWarnings())->toHaveCount(0);
});

it('shows compliance alerts for an expired visa', function (): void {
    $this->employee->update(['visa_expire' => now()->subMonth()->toDateString()]);

    $component = Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id]);

    $alerts = $component->instance()->getComplianceAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]['color'])->toBe('danger');
});

it('shows overview quick actions in the header', function (): void {
    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.header-actions.edit'))
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.header-actions.add-document'))
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.header-actions.issue-warning'));
});
