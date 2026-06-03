<?php

use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\OverviewEmployee;

require_once __DIR__.'/EmployeeTestHelpers.php';

beforeEach(function (): void {
    App::setLocale('ar');

    $this->user = createEmployeeAdminUser();
    $this->user->update(['language' => 'ar']);
    $this->employee = createEmployeeForFlowTest($this->user);
    $this->actingAs($this->user);
});

it('renders the employee overview page in arabic', function (): void {
    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.title', locale: 'ar'))
        ->assertSee(__('employees::filament/resources/employee/pages/overview-employee.header-actions.edit', locale: 'ar'));
});

it('renders the employee list layout controls in arabic', function (): void {
    Livewire::test(ListEmployees::class)
        ->assertSuccessful()
        ->assertSee(__('employees::filament/resources/employee/pages/list-employee.header-actions.layout.label', locale: 'ar'))
        ->assertSee(__('employees::filament/resources/employee/pages/list-employee.tabs.compliance-issues', locale: 'ar'));
});

it('renders the employee create form labels in arabic', function (): void {
    Livewire::test(CreateEmployee::class)
        ->assertSuccessful()
        ->assertSee(__('employees::filament/resources/employee.form.sections.fields.name', locale: 'ar'));
});
