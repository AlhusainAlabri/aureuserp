<?php

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Webkul\Employee\Enums\DistanceUnit;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\OverviewEmployee;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use Webkul\Employee\Models\Employee;

require_once __DIR__.'/EmployeeTestHelpers.php';

beforeEach(function (): void {
    $this->user = createEmployeeAdminUser();
    $this->employee = createEmployeeForFlowTest($this->user);
    $this->actingAs($this->user);
});

it('completes the employee create to overview flow', function (): void {
    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name'       => 'CRUD Flow Employee',
            'work_email' => 'crud.flow@example.com',
            'job_title'  => 'Operations Lead',
            ...requiredEmployeeFormData($this->user),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $employee = Employee::query()->where('name', 'CRUD Flow Employee')->firstOrFail();

    expect(EmployeeResource::getUrl('overview', ['record' => $employee]))
        ->toContain('/overview');

    Livewire::test(OverviewEmployee::class, ['record' => $employee->id])
        ->assertSuccessful()
        ->assertSee('CRUD Flow Employee')
        ->assertSee('Operations Lead');
});

it('creates an employee when optional schema columns are absent from the database payload', function (): void {
    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name'       => 'Schema Safe Employee',
            'work_email' => 'schema.safe@example.com',
            'job_title'  => 'Coordinator',
            ...requiredEmployeeFormData($this->user),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Employee::query()->where('name', 'Schema Safe Employee')->exists())->toBeTrue();
});

it('requires mandatory employee fields on create', function (): void {
    $expectedErrors = [
        'mobile_phone'       => 'required',
        'civil_id'           => 'required',
        'employment_type_id' => 'required',
        'company_id'         => 'required',
    ];

    if (Schema::hasTable('department_employee')) {
        $expectedErrors['departments'] = 'required';
    } else {
        $expectedErrors['department_id'] = 'required';
    }

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name'            => 'Incomplete Employee',
            'membership_type' => 'employee',
        ])
        ->call('create')
        ->assertHasFormErrors($expectedErrors)
        ->assertNotNotified();
});

it('completes the employee edit to overview flow', function (): void {
    Livewire::test(EditEmployee::class, ['record' => $this->employee->id])
        ->fillForm([
            'job_title'               => 'Updated Job Title',
            'distance_home_work_unit' => DistanceUnit::KILOMETER->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($this->employee->fresh()->job_title)->toBe('Updated Job Title');

    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->assertSee('Updated Job Title');
});

it('renders the details view page for an employee record', function (): void {
    $this->employee->update([
        'job_title'  => 'Detail View Title',
        'work_email' => 'detail.view@example.com',
    ]);

    Livewire::test(ViewEmployee::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->assertSee('Detail View Title')
        ->assertSee('detail.view@example.com');
});

it('exposes unified record navigation across overview, details, and edit', function (): void {
    $navigationPages = collect(EmployeeResource::getRecordSubNavigation(
        Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])->instance()
    ))->map(fn ($item) => $item->getLabel())->values()->all();

    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/overview-employee.navigation.title'));
    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/view-employee.navigation.title'));
    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/manage-documents.navigation.title'));
});
