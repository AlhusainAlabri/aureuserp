<?php

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Enums\DistanceUnit;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\OverviewEmployee;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Employee\Models\EmploymentType;
use Webkul\Employee\Models\WarningType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

require_once __DIR__.'/Employees/EmployeeTestHelpers.php';

function createEmployeeForResourceTest(bool $isActive = true): Employee
{
    $user = User::factory()->create();
    $company = Company::create([
        'name'       => 'Test Company',
        'company_id' => 'test-'.uniqid(),
    ]);
    $department = Department::query()->create([
        'name'       => 'Resource Test Department',
        'creator_id' => $user->id,
    ]);
    $employmentType = EmploymentType::create([
        'name'       => 'Permanent',
        'code'       => 'permanent-'.uniqid(),
        'creator_id' => $user->id,
    ]);

    $employee = Employee::create([
        'user_id'            => $user->id,
        'creator_id'         => $user->id,
        'company_id'         => $company->id,
        'department_id'      => $department->id,
        'employment_type_id' => $employmentType->id,
        'mobile_phone'       => '+96890000001',
        'civil_id'           => '98765432109',
        'name'               => $user->name,
        'work_email'         => $user->email,
        'is_active'          => $isActive,
    ]);

    if (Schema::hasTable('department_employee')) {
        $employee->departments()->sync([
            $department->id => ['is_primary' => true, 'start_date' => now()->toDateString()],
        ]);
    }

    return $employee;
}

beforeEach(function (): void {
    $this->user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ([
        'view_any_employee_employee',
        'view_employee_employee',
        'create_employee_employee',
        'update_employee_employee',
    ] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $this->user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->employee = createEmployeeForResourceTest();
    $this->actingAs($this->user);
});

it('uses singular and plural model labels', function (): void {
    expect(EmployeeResource::getModelLabel())->toBe(__('employees::filament/resources/employee.singular'));
    expect(EmployeeResource::getPluralModelLabel())->toBe(__('employees::filament/resources/employee.title'));
});

it('resolves employment type through employment_type_id', function (): void {
    $employmentType = EmploymentType::create([
        'name'       => 'Permanent',
        'code'       => 'permanent',
        'creator_id' => $this->user->id,
    ]);

    $this->employee->update(['employment_type_id' => $employmentType->id]);

    expect($this->employee->fresh()->employmentType?->id)->toBe($employmentType->id);
});

it('redirects to overview after creating an employee', function (): void {
    expect(EmployeeResource::getPages())->toHaveKey('overview');

    Livewire::test(CreateEmployee::class)
        ->fillForm([
            'name' => 'Overview Redirect Employee',
            ...requiredEmployeeFormData($this->user),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $employee = Employee::query()->where('name', 'Overview Redirect Employee')->firstOrFail();

    expect(EmployeeResource::getUrl('overview', ['record' => $employee]))
        ->toContain('/overview');
});

it('uses unified record navigation without embedded relation managers', function (): void {
    expect(EmployeeResource::getRelations())->toBeEmpty();
    expect(EmployeeResource::getPages())->toHaveKeys(['documents', 'warnings', 'meetings']);

    $navigationPages = collect(EmployeeResource::getRecordSubNavigation(
        Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])->instance()
    ))->map(fn ($item) => $item->getLabel())->values()->all();

    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/manage-documents.navigation.title'));
    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/manage-warnings.navigation.title'));
    expect($navigationPages)->toContain(__('employees::filament/resources/employee/pages/manage-meetings.navigation.title'));
});

it('uses sticky form actions on the edit page', function (): void {
    expect(EditEmployee::$formActionsAreSticky)->toBeTrue();
});

it('shows civil id on the overview page when present', function (): void {
    $this->employee->update([
        'civil_id'        => '12345678901',
        'civil_id_expiry' => now()->addYear()->toDateString(),
    ]);

    Livewire::test(OverviewEmployee::class, ['record' => $this->employee->id])
        ->assertSee('12345678901');
});

it('syncs the legacy km field when home distance is saved', function (): void {
    $this->employee->update([
        'distance_home_work'      => 15,
        'distance_home_work_unit' => DistanceUnit::KILOMETER->value,
    ]);

    expect($this->employee->fresh()->km_home_work)->toBe(15);
});

it('formats home distance with the selected unit', function (): void {
    $this->employee->update([
        'distance_home_work'      => 500,
        'distance_home_work_unit' => DistanceUnit::METER->value,
    ]);

    expect($this->employee->fresh()->formatted_home_distance)->toBe('500 m');
});

it('returns danger color for expired civil id dates', function (): void {
    $this->employee->update(['civil_id_expiry' => now()->subDay()->toDateString()]);

    expect($this->employee->fresh()->getCivilIdExpiryColor())->toBe('danger');
});

it('detects when profile field groups have content', function (): void {
    expect($this->employee->hasAnyFilledAttributes(['private_street1', 'private_city']))->toBeFalse();

    $this->employee->update(['private_city' => 'Muscat']);

    expect($this->employee->fresh()->hasAnyFilledAttributes(['private_street1', 'private_city']))->toBeTrue();
});

it('switches between grid and table layout on the list page', function (): void {
    Livewire::test(ListEmployees::class)
        ->assertSet('tableLayout', 'grid')
        ->call('setTableLayout', 'table')
        ->assertSet('tableLayout', 'table')
        ->call('setTableLayout', 'grid')
        ->assertSet('tableLayout', 'grid');
});

it('builds compliance badges from employee compliance signals', function (): void {
    $this->employee->update(['is_active' => false]);

    EmployeeDocument::create([
        'employee_id'   => $this->employee->id,
        'document_type' => 'passport',
        'document_name' => 'Expired Passport',
        'file_path'     => 'employees/'.$this->employee->id.'/documents/passport.pdf',
        'expiry_date'   => now()->subDay()->toDateString(),
        'creator_id'    => $this->user->id,
    ]);

    $warningType = WarningType::create([
        'name'       => 'Late Attendance',
        'creator_id' => $this->user->id,
    ]);

    EmployeeWarning::create([
        'employee_id'     => $this->employee->id,
        'warning_type_id' => $warningType->id,
        'subject'         => 'Late arrival',
        'issued_at'       => now(),
        'is_acknowledged' => false,
        'creator_id'      => $this->user->id,
    ]);

    $employee = Employee::query()
        ->withCount([
            'documents as expired_documents_count'  => fn ($query) => $query->expired(),
            'documents as expiring_documents_count' => fn ($query) => $query->expiringSoon(),
            'warnings as active_warnings_count'     => fn ($query) => $query->where('is_acknowledged', false),
        ])
        ->findOrFail($this->employee->id);

    $badges = $employee->getListComplianceBadges();

    expect($badges)->toHaveCount(3);
    expect(collect($badges)->pluck('color'))->toContain('gray', 'danger');
});

it('scopes employees with compliance issues', function (): void {
    $this->employee->update(['is_active' => false]);

    $completeEmployee = createEmployeeForResourceTest();
    $completeEmployee->update([
        'employment_type_id' => EmploymentType::create([
            'name'       => 'Contract',
            'code'       => 'contract',
            'creator_id' => $this->user->id,
        ])->id,
        'job_title' => 'Engineer',
    ]);

    $issueIds = Employee::query()->withComplianceIssues()->pluck('id');

    expect($issueIds)->toContain($this->employee->id);
    expect($issueIds)->not->toContain($completeEmployee->id);
});

it('scopes employees with incomplete profiles', function (): void {
    $this->employee->update([
        'department_id'      => null,
        'parent_id'          => null,
        'employment_type_id' => null,
        'job_title'          => null,
    ]);

    expect(Employee::query()->incompleteProfile()->whereKey($this->employee->id)->exists())->toBeTrue();
});

it('registers compliance and incomplete profile list presets', function (): void {
    $presets = Livewire::test(ListEmployees::class)->instance()->getPresetTableViews();

    expect($presets)->toHaveKeys(['compliance_issues', 'incomplete_profile']);
});
