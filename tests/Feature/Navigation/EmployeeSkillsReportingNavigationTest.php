<?php

use Livewire\Livewire;
use Webkul\Employee\Filament\Clusters\Configurations as EmployeeConfigurations;
use Webkul\Employee\Filament\Clusters\Reportings;
use Webkul\Employee\Filament\Clusters\Reportings\Resources\EmployeeSkillResource;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ListSubmissions;

require_once __DIR__.'/../Employees/EmployeeTestHelpers.php';

it('hides the employees configurations cluster from main navigation', function (): void {
    if (! class_exists(EmployeeConfigurations::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    expect(EmployeeConfigurations::shouldRegisterNavigation())->toBeFalse();
});

it('hides the employees reportings cluster from main navigation', function (): void {
    if (! class_exists(Reportings::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    expect(Reportings::shouldRegisterNavigation())->toBeFalse();
});

it('keeps the employees resource visible in main navigation', function (): void {
    if (! class_exists(EmployeeResource::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    expect(EmployeeResource::shouldRegisterNavigation())->toBeTrue();
});

it('keeps employee skills reporting accessible via resource url', function (): void {
    if (! class_exists(EmployeeSkillResource::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    expect(EmployeeSkillResource::getSlug())->toBe('employees/skills');
});

it('sorts submissions after employees in navigation', function (): void {
    if (! class_exists(SubmissionResource::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    expect(EmployeeResource::getNavigationSort())->toBe(1)
        ->and(SubmissionResource::getNavigationSort())->toBe(10);
});

it('loads the submissions list page without tab class errors', function (): void {
    if (! class_exists(ListSubmissions::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    $user = createEmployeeAdminUser([
        'view_any_employee_submission',
    ]);

    $this->actingAs($user);

    Livewire::test(ListSubmissions::class)
        ->assertSuccessful();
});

it('shows employee skills reporting action on the employees list page', function (): void {
    if (! class_exists(EmployeeSkillResource::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    $user = createEmployeeAdminUser([
        'view_any_employee_employee::skill',
    ]);

    $this->actingAs($user);

    Livewire::test(ListEmployees::class)
        ->assertSuccessful()
        ->assertSee(__('employees::filament/clusters/reportings/resources/employee-skill.navigation.title'));
});
