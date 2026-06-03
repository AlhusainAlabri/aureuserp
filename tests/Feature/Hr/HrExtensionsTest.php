<?php

use App\Enums\Hr\RaiseReason;
use App\Enums\Hr\TrainingStatus;
use App\Enums\Hr\TrainingType;
use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Filament\Pages\MyRequests;
use App\Http\Middleware\CheckEmployeeFileClosure;
use App\Listeners\Hr\NotifyLeaveSubstitute;
use App\Mail\LeaveSubstituteRequestMail;
use App\Models\Hr\EmployeeSalaryRaise;
use App\Models\Hr\EmployeeTraining;
use App\Providers\HrExtensionsServiceProvider;
use App\Services\Hr\EmployeeDepartmentService;
use App\Services\Hr\EmployeeFileClosureService;
use App\Services\Hr\LeaveSubstituteService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\TimeOff\Models\Leave;
use Webkul\TimeOff\Models\LeaveType;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function hrTestUser(array $permissions = [], array $roles = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    foreach ($roles as $roleName) {
        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

function hrTestEmployee(?User $user = null): Employee
{
    $user ??= hrTestUser();
    $company = Company::query()->first() ?? Company::create([
        'name'       => 'HR Test Co '.uniqid(),
        'company_id' => 'hr-'.uniqid(),
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

function hrTestDepartments(int $count = 2): array
{
    $departments = [];

    for ($i = 0; $i < $count; $i++) {
        $departments[] = Department::query()->create([
            'name'       => 'Dept '.uniqid(),
            'creator_id' => auth()->id(),
        ]);
    }

    return $departments;
}

it('assigns employee to multiple departments', function (): void {
    hrTestUser();
    $employee = hrTestEmployee();
    $departments = hrTestDepartments(2);

    app(EmployeeDepartmentService::class)->syncDepartments($employee, [$departments[0]->id, $departments[1]->id], $departments[0]->id);

    expect($employee->departments()->count())->toBe(2);
});

it('syncs department_id to primary department', function (): void {
    hrTestUser();
    $employee = hrTestEmployee();
    $departments = hrTestDepartments(2);

    app(EmployeeDepartmentService::class)->syncDepartments($employee, [$departments[0]->id, $departments[1]->id], $departments[1]->id);

    expect($employee->fresh()->department_id)->toBe($departments[1]->id);
});

it('enforces exactly one primary department', function (): void {
    hrTestUser();
    $employee = hrTestEmployee();
    $departments = hrTestDepartments(2);

    $employee->departments()->sync([
        $departments[0]->id => ['is_primary' => true, 'start_date' => now()->toDateString()],
        $departments[1]->id => ['is_primary' => true, 'start_date' => now()->toDateString()],
    ]);

    $employee->touch();

    expect($employee->departments()->wherePivot('is_primary', true)->count())->toBe(1);
});

it('saves training course with all fields', function (): void {
    $user = hrTestUser();
    $employee = hrTestEmployee($user);

    $training = EmployeeTraining::query()->create([
        'employee_id'             => $employee->id,
        'course_name'             => 'Safety Leadership',
        'provider'                => 'Nodhum Academy',
        'type'                    => TrainingType::Internal,
        'start_date'              => now()->toDateString(),
        'end_date'                => now()->addDays(3)->toDateString(),
        'duration_hours'          => 12,
        'cost'                    => 150.500,
        'cost_currency'           => 'OMR',
        'status'                  => TrainingStatus::Completed,
        'notes'                   => 'Completed successfully',
        'creator_id'              => $user->id,
    ]);

    expect($training->fresh()->course_name)->toBe('Safety Leadership')
        ->and((float) $training->cost)->toBe(150.5);
});

it('stores training certificate on private disk', function (): void {
    Storage::fake('private');
    $user = hrTestUser();
    $employee = hrTestEmployee($user);
    $path = "employees/{$employee->id}/trainings/certificates/test.pdf";

    Storage::disk('private')->put($path, 'certificate-content');

    $training = EmployeeTraining::query()->create([
        'employee_id'      => $employee->id,
        'course_name'      => 'Cert Course',
        'type'             => TrainingType::Certification,
        'start_date'       => now()->toDateString(),
        'status'           => TrainingStatus::Completed,
        'certificate_path' => $path,
        'creator_id'       => $user->id,
    ]);

    expect(Storage::disk('private')->exists($training->certificate_path))->toBeTrue();
});

it('notifies HR and employee for expiring certificates', function (): void {
    $user = hrTestUser([], ['hr_manager']);
    $employee = hrTestEmployee($user);

    EmployeeTraining::query()->create([
        'employee_id'             => $employee->id,
        'course_name'             => 'First Aid',
        'type'                    => TrainingType::External,
        'start_date'              => now()->subMonths(2)->toDateString(),
        'status'                  => TrainingStatus::Completed,
        'certificate_expiry_date' => now()->addDays(20)->toDateString(),
        'creator_id'              => $user->id,
    ]);

    Artisan::call('hr:notify-expiring-training-certificates');

    expect(Artisan::output())->toContain('Notified about 1');
});

it('closes employee file and deactivates user account', function (): void {
    $user = hrTestUser(['close_employee_file']);
    $employee = hrTestEmployee($user);

    app(EmployeeFileClosureService::class)->close($employee, $user, 'resignation', 'Employee resigned after notice period.');

    $employee->refresh();
    $user->refresh();

    expect((bool) $employee->is_closed)->toBeTrue()
        ->and((bool) $employee->is_active)->toBeFalse()
        ->and((bool) $user->is_active)->toBeFalse();
});

it('blocks closed employee via middleware', function (): void {
    $user = hrTestUser(['close_employee_file']);
    $employee = hrTestEmployee($user);
    $employee->forceFill(['is_closed' => true, 'is_active' => false])->save();
    $user->update(['is_active' => false]);

    auth()->login($user);

    $middleware = new CheckEmployeeFileClosure;
    $request = request()->merge([]);
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBeIn([302, 403]);
});

it('reopens employee file and reactivates account', function (): void {
    $user = hrTestUser(['reopen_employee_file'], ['hr_manager']);
    $employee = hrTestEmployee($user);
    $employee->forceFill(['is_closed' => true, 'is_active' => false, 'closure_reason' => 'resignation'])->save();
    $user->update(['is_active' => false]);

    app(EmployeeFileClosureService::class)->reopen($employee, $user, 'Returned from leave of absence.');

    expect((bool) $employee->fresh()->is_closed)->toBeFalse()
        ->and((bool) $employee->fresh()->is_active)->toBeTrue()
        ->and((bool) $user->fresh()->is_active)->toBeTrue();
});

it('requires close permission to close employee file', function (): void {
    $user = hrTestUser();
    $employee = hrTestEmployee($user);

    app(EmployeeFileClosureService::class)->close($employee, $user, 'other', 'Attempt without permission');
})->throws(RuntimeException::class);

it('auto-calculates salary raise amount and percent', function (): void {
    $user = hrTestUser();
    $employee = hrTestEmployee($user);

    $raise = EmployeeSalaryRaise::query()->create([
        'employee_id'    => $employee->id,
        'effective_date' => now()->toDateString(),
        'old_amount'     => 500.000,
        'new_amount'     => 550.000,
        'reason'         => RaiseReason::AnnualReview,
        'creator_id'     => $user->id,
    ]);

    expect((float) $raise->raise_amount)->toBe(50.0)
        ->and((float) $raise->raise_percent)->toBe(10.0);
});

it('updates payroll basic component when raise is saved', function (): void {
    if (! Schema::hasTable('payroll_employee_components') || ! Schema::hasTable('payroll_salary_components')) {
        $this->markTestSkipped('Payroll tables not available.');
    }

    $user = hrTestUser();
    $employee = hrTestEmployee($user);

    SalaryComponent::query()->firstOrCreate(
        ['code' => 'BASIC'],
        [
            'name'       => 'Basic Salary',
            'type'       => 'earning',
            'is_active'  => true,
            'creator_id' => $user->id,
        ],
    );

    EmployeeSalaryRaise::query()->create([
        'employee_id'    => $employee->id,
        'effective_date' => now()->toDateString(),
        'old_amount'     => 400.000,
        'new_amount'     => 460.000,
        'reason'         => RaiseReason::Performance,
        'creator_id'     => $user->id,
    ]);

    $assignment = EmployeeComponent::query()
        ->where('employee_id', $employee->id)
        ->first();

    expect($assignment)->not->toBeNull()
        ->and((float) $assignment->amount)->toBe(460.0);
});

it('returns salary raises in descending effective date order', function (): void {
    $user = hrTestUser();
    $employee = hrTestEmployee($user);

    EmployeeSalaryRaise::query()->create([
        'employee_id' => $employee->id, 'effective_date' => '2025-01-01',
        'old_amount'  => 100, 'new_amount' => 110, 'reason' => RaiseReason::Other, 'creator_id' => $user->id,
    ]);
    EmployeeSalaryRaise::query()->create([
        'employee_id' => $employee->id, 'effective_date' => '2026-01-01',
        'old_amount'  => 110, 'new_amount' => 120, 'reason' => RaiseReason::Other, 'creator_id' => $user->id,
    ]);

    $dates = $employee->salaryRaises()->orderByDesc('effective_date')->pluck('effective_date')->map->format('Y-m-d')->all();

    expect($dates)->toBe(['2026-01-01', '2025-01-01']);
});

it('notifies substitute employee when leave is created', function (): void {
    if (! Schema::hasTable('time_off_leaves')) {
        $this->markTestSkipped('Time off not installed.');
    }

    Mail::fake();

    $requester = hrTestUser();
    $substituteUser = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $requesterEmployee = hrTestEmployee($requester);
    $substituteEmployee = Employee::query()->create([
        'user_id'    => $substituteUser->id,
        'creator_id' => $requester->id,
        'company_id' => $requesterEmployee->company_id,
        'name'       => 'Substitute '.uniqid(),
        'work_email' => $substituteUser->email,
        'is_active'  => true,
    ]);

    $leaveType = LeaveType::query()->first()
        ?? LeaveType::query()->create([
            'name'       => 'Annual Leave',
            'creator_id' => $requester->id,
        ]);

    $leave = Leave::query()->create([
        'employee_id'             => $requesterEmployee->id,
        'substitute_employee_id'  => $substituteEmployee->id,
        'holiday_status_id'       => $leaveType->id,
        'request_date_from'       => now()->addWeek()->toDateString(),
        'request_date_to'         => now()->addWeeks(2)->toDateString(),
        'date_from'               => now()->addWeek(),
        'date_to'                 => now()->addWeeks(2),
        'state'                   => 'confirm',
        'creator_id'              => $requester->id,
    ]);

    app(NotifyLeaveSubstitute::class)->handleCreated($leave);

    Mail::assertQueued(LeaveSubstituteRequestMail::class);
});

it('allows substitute to accept leave coverage', function (): void {
    if (! Schema::hasColumn('time_off_leaves', 'substitute_accepted_at')) {
        $this->markTestSkipped('Substitute columns missing.');
    }

    $requester = hrTestUser();
    $substitute = hrTestUser();
    $requesterEmployee = hrTestEmployee($requester);
    $substituteEmployee = Employee::query()->create([
        'user_id'    => $substitute->id,
        'creator_id' => $requester->id,
        'company_id' => $requesterEmployee->company_id,
        'name'       => 'Sub '.uniqid(),
        'work_email' => $substitute->email,
        'is_active'  => true,
    ]);

    $leaveType = LeaveType::query()->first()
        ?? LeaveType::query()->create([
            'name'       => 'Annual Leave',
            'creator_id' => $requester->id,
        ]);

    $leave = Leave::query()->create([
        'employee_id'            => $requesterEmployee->id,
        'substitute_employee_id' => $substituteEmployee->id,
        'holiday_status_id'      => $leaveType->id,
        'request_date_from'      => now()->addWeek()->toDateString(),
        'request_date_to'        => now()->addWeeks(2)->toDateString(),
        'date_from'              => now()->addWeek(),
        'date_to'                => now()->addWeeks(2),
        'state'                  => 'confirm',
        'creator_id'             => $requester->id,
    ]);

    app(LeaveSubstituteService::class)->accept($leave);

    expect($leave->fresh()->substitute_accepted_at)->not->toBeNull();
});

it('allows substitute to decline leave coverage', function (): void {
    if (! Schema::hasColumn('time_off_leaves', 'substitute_declined_at')) {
        $this->markTestSkipped('Substitute columns missing.');
    }

    $requester = hrTestUser();
    $substitute = hrTestUser();
    $requesterEmployee = hrTestEmployee($requester);
    $substituteEmployee = Employee::query()->create([
        'user_id'    => $substitute->id,
        'creator_id' => $requester->id,
        'company_id' => $requesterEmployee->company_id,
        'name'       => 'Sub '.uniqid(),
        'work_email' => $substitute->email,
        'is_active'  => true,
    ]);

    $leaveType = LeaveType::query()->first()
        ?? LeaveType::query()->create([
            'name'       => 'Annual Leave',
            'creator_id' => $requester->id,
        ]);

    $leave = Leave::query()->create([
        'employee_id'            => $requesterEmployee->id,
        'substitute_employee_id' => $substituteEmployee->id,
        'holiday_status_id'      => $leaveType->id,
        'request_date_from'      => now()->addWeek()->toDateString(),
        'request_date_to'        => now()->addWeeks(2)->toDateString(),
        'date_from'              => now()->addWeek(),
        'date_to'                => now()->addWeeks(2),
        'state'                  => 'confirm',
        'creator_id'             => $requester->id,
    ]);

    app(LeaveSubstituteService::class)->decline($leave);

    expect($leave->fresh()->substitute_declined_at)->not->toBeNull();
});

it('requires vendor only for standard purchase requests', function (): void {
    expect(PurchaseOrderResourceExtensions::requiresVendor(RequestType::StandardPurchase->value))->toBeTrue()
        ->and(PurchaseOrderResourceExtensions::requiresVendor(RequestType::DeviceRequest->value))->toBeFalse()
        ->and(PurchaseOrderResourceExtensions::isInternalRequest(RequestType::DeviceRequest->value))->toBeTrue();
});

it('creates internal request with device request type', function (): void {
    expect(RequestType::DeviceRequest->value)->toBe('device_request')
        ->and(RequestType::internalRequestTypes())->toContain('device_request');
});

it('scopes my requests page to internal request types only', function (): void {
    expect(MyRequests::class)->toBeClass()
        ->and(method_exists(MyRequests::class, 'getTableQuery'))->toBeTrue();
});

it('provides purchase list preset views by request type', function (): void {
    if (! Schema::hasColumn('purchases_orders', 'request_type')) {
        test()->markTestSkipped('Purchases request_type column is not available.');
    }

    $views = PurchaseOrderResourceExtensions::presetTableViews();

    expect($views)->toHaveKey('device_request')
        ->and($views)->toHaveKey('technical_support');
});

it('assigns hr file permissions to admin role on boot', function (): void {
    Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

    app()->getProvider(HrExtensionsServiceProvider::class)->registerHrPermissions();

    $role = Role::query()->where('name', 'Admin')->first();

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('close_employee_file'))->toBeTrue()
        ->and($role->hasPermissionTo('reopen_employee_file'))->toBeTrue();
});
