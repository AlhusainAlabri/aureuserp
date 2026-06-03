<?php

use Livewire\Livewire;
use Webkul\Employee\Enums\DistanceUnit;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
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
            'name'            => 'CRUD Flow Employee',
            'membership_type' => 'employee',
            'work_email'      => 'crud.flow@example.com',
            'job_title'       => 'Operations Lead',
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
