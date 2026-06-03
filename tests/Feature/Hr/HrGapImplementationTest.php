<?php

use App\Enums\Hr\ContractType;
use App\Enums\Hr\SelfAssessmentStatus;
use App\Filament\Pages\MyEmployeeProfile;
use App\Filament\Pages\MySelfAssessment;
use App\Filament\Pages\MyWarnings;
use App\Models\Hr\EmployeeContract;
use App\Models\Hr\EmployeeSelfAssessment;
use App\Services\Hr\EmployeeFileClosureService;
use App\Services\Hr\HrExtensionSchemaService;
use Database\Seeders\EmployeeRoleSeeder;
use Database\Seeders\HrDemoEmployeeSeeder;
use Database\Seeders\OmanOrgStructureSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Employee\Models\WarningType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    app(HrExtensionSchemaService::class)->ensure();
});

function gapTestUser(): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    test()->actingAs($user);

    return $user;
}

function gapTestEmployee(?User $user = null): Employee
{
    $user ??= gapTestUser();
    $company = Company::query()->first() ?? Company::create([
        'name'       => 'Gap Test Co '.uniqid(),
        'company_id' => 'gap-'.uniqid(),
    ]);

    return Employee::query()->create([
        'user_id'    => $user->id,
        'creator_id' => $user->id,
        'company_id' => $company->id,
        'name'       => $user->name,
        'work_email' => $user->email,
        'is_active'  => true,
    ]);
}

it('creates employee contracts with dates and wage', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();

    $contract = EmployeeContract::query()->create([
        'employee_id'        => $employee->id,
        'contract_type'      => ContractType::FixedTerm,
        'start_date'         => now()->subYear(),
        'end_date'           => now()->addMonths(6),
        'first_joining_date' => now()->subYears(2),
        'wage'               => 850.000,
        'is_active'          => true,
        'creator_id'         => auth()->id(),
    ]);

    expect($contract->employee_id)->toBe($employee->id)
        ->and($employee->contracts()->count())->toBe(1);
});

it('seeds oman org structure idempotently', function (): void {
    gapTestUser();

    $this->seed(OmanOrgStructureSeeder::class);
    $this->seed(OmanOrgStructureSeeder::class);

    expect(Department::query()->where('name', 'Social Research Department')->count())->toBe(1)
        ->and(Department::query()->where('name', 'Reception Section')->count())->toBe(1);
});

it('stores primary job responsibilities on employee', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();

    $employee->forceFill([
        'primary_job_responsibilities' => 'Manage daily operations and reporting.',
    ])->saveQuietly();

    expect($employee->fresh()->primary_job_responsibilities)->toContain('operations');
});

it('submits monthly self assessment', function (): void {
    $user = gapTestUser();
    $employee = gapTestEmployee($user);

    $assessment = EmployeeSelfAssessment::query()->create([
        'employee_id'       => $employee->id,
        'period_year'       => now()->year,
        'period_month'      => now()->month,
        'employee_comments' => 'Completed all assigned tasks.',
        'status'            => SelfAssessmentStatus::Submitted,
        'submitted_at'      => now(),
        'creator_id'        => $user->id,
    ]);

    expect($assessment->status)->toBe(SelfAssessmentStatus::Submitted);
});

it('runs self assessment reminder command', function (): void {
    Mail::fake();
    gapTestUser();
    gapTestEmployee();

    Artisan::call('hr:remind-self-assessments');

    expect(Artisan::output())->toContain('Reminded');
});

it('creates anonymous employee submission', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();

    $submission = new EmployeeSubmission([
        'type'    => 'complaint',
        'subject' => 'Test complaint',
        'body'    => 'Anonymous body',
    ]);
    $submission->forceFill([
        'employee_id'    => $employee->id,
        'is_anonymous'   => true,
        'submitter_name' => __('hr-extensions::submissions.anonymous_label'),
    ]);
    $submission->save();

    expect((bool) $submission->fresh()->is_anonymous)->toBeTrue()
        ->and($submission->submitter_name)->toBe(__('hr-extensions::submissions.anonymous_label'));
});

it('notifies on employee file closure', function (): void {
    $user = gapTestUser();
    $user->givePermissionTo('close_employee_file');
    $employee = gapTestEmployee();

    app(EmployeeFileClosureService::class)->close($employee, $user, 'administrative', 'Test closure');

    expect((bool) $employee->fresh()->is_closed)->toBeTrue();
});

it('runs expiring contracts notification command', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();

    EmployeeContract::query()->create([
        'employee_id'   => $employee->id,
        'contract_type' => ContractType::FixedTerm,
        'start_date'    => now()->subYear(),
        'end_date'      => now()->addDays(10),
        'creator_id'    => auth()->id(),
    ]);

    Artisan::call('hr:notify-expiring-contracts');

    expect(Artisan::output())->toContain('Notified');
});

it('runs civil id expiry notification command', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();
    $employee->forceFill([
        'civil_id'        => '12345678',
        'civil_id_expiry' => now()->addDays(15),
    ])->saveQuietly();

    Artisan::call('hr:notify-expiring-civil-id');

    expect(Artisan::output())->toContain('Notified');
});

it('seeds employee role permissions idempotently', function (): void {
    Permission::query()->firstOrCreate(['name' => 'page_MyEmployeeProfile', 'guard_name' => 'web']);

    $this->seed(EmployeeRoleSeeder::class);
    $this->seed(EmployeeRoleSeeder::class);

    expect(Role::query()->where('name', 'employee')->exists())->toBeTrue();
});

it('allows employee self service pages when linked', function (): void {
    $user = gapTestUser();
    gapTestEmployee($user);

    expect(MyEmployeeProfile::canAccess())->toBeTrue()
        ->and(MySelfAssessment::canAccess())->toBeTrue()
        ->and(MyWarnings::canAccess())->toBeTrue();
});

it('has employee self assessment table', function (): void {
    expect(Schema::hasTable('employee_self_assessments'))->toBeTrue()
        ->and(Schema::hasTable('employee_contracts'))->toBeTrue();
});

it('stores warning acknowledgment fields', function (): void {
    gapTestUser();
    $employee = gapTestEmployee();

    if (! Schema::hasTable('employees_warning_types')) {
        $this->markTestSkipped('Warning types table not available.');
    }

    $warningType = WarningType::query()->first() ?? WarningType::query()->create([
        'name'       => 'Written Warning',
        'creator_id' => auth()->id(),
    ]);

    $warning = EmployeeWarning::query()->create([
        'employee_id'     => $employee->id,
        'warning_type_id' => $warningType->id,
        'subject'         => 'Late arrival',
        'description'     => 'Repeated tardiness',
        'issued_at'       => now(),
        'creator_id'      => auth()->id(),
    ]);

    $warning->forceFill([
        'acknowledgment_signature'   => 'signed-data',
        'employee_acknowledged_at'   => now(),
    ])->save();

    expect($warning->fresh()->employee_acknowledged_at)->not->toBeNull();
});

it('seeds demo employee profile data idempotently', function (): void {
    gapTestUser();

    $this->seed(HrDemoEmployeeSeeder::class);
    $this->seed(HrDemoEmployeeSeeder::class);

    $employee = Employee::query()->where('work_email', 'toney.morar@example.org')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->mobile_phone)->not->toBeEmpty();

    if (Schema::hasColumn('employees_employees', 'civil_id')) {
        expect($employee->civil_id)->not->toBeEmpty();
    }
});
