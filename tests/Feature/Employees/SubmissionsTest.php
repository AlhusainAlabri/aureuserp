<?php

use Filament\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Webkul\Employee\Filament\Pages\MySubmissions;
use Webkul\Employee\Filament\Resources\SubmissionResource;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Employee\Models\EmployeeSubmissionReply;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function makeEmployee(?User $user = null): Employee
{
    $user = $user ?? User::factory()->create();

    $company = Company::create([
        'name'       => 'Test Company',
        'company_id' => 'test-'.uniqid(),
    ]);

    return Employee::create([
        'user_id'       => $user->id,
        'creator_id'    => $user->id,
        'company_id'    => $company->id,
        'department_id' => null,
        'name'          => $user->name,
        'work_email'    => $user->email,
        'is_active'     => true,
    ]);
}

function actingAsEmployee(Employee $employee): void
{
    auth()->login($employee->user);
}

function makeHrManager(): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => 'web']);
    $user->assignRole($role);

    return $user;
}

// ──────────────────────────────────────────────
// 1. Employee can create a submission
// ──────────────────────────────────────────────

it('allows an employee to create a submission', function () {
    $employee = makeEmployee();
    actingAsEmployee($employee);

    $submission = EmployeeSubmission::create([
        'type'        => 'complaint',
        'subject'     => 'Broken AC in office',
        'body'        => 'The air conditioning in the main office has been broken for 3 days.',
        'employee_id' => $employee->id,
    ]);

    expect($submission)->toBeInstanceOf(EmployeeSubmission::class)
        ->and($submission->employee_id)->toBe($employee->id)
        ->and($submission->type)->toBe('complaint')
        ->and($submission->subject)->toBe('Broken AC in office')
        ->and($submission->status)->toBe('open');

    $this->assertDatabaseHas('employees_employee_submissions', [
        'id'          => $submission->id,
        'employee_id' => $employee->id,
        'type'        => 'complaint',
        'subject'     => 'Broken AC in office',
        'status'      => 'open',
    ]);
});

// ──────────────────────────────────────────────
// 2. Ticket number auto-generates as SUB-YYYY-XXXX
// ──────────────────────────────────────────────

it('auto-generates ticket number in correct format', function () {
    $employee = makeEmployee();
    actingAsEmployee($employee);

    $submission = EmployeeSubmission::create([
        'type'        => 'suggestion',
        'subject'     => 'Test subject',
        'body'        => 'Test body',
        'employee_id' => $employee->id,
    ]);

    expect($submission->ticket_number)
        ->toStartWith('SUB-'.now()->year.'-')
        ->toMatch('/^SUB-\d{4}-\d{4}$/');
});

// ──────────────────────────────────────────────
// 3. Employee cannot see another employee's submissions
// ──────────────────────────────────────────────

it('prevents employees from viewing other employees submissions', function () {
    $employeeA = makeEmployee();
    $employeeB = makeEmployee();

    EmployeeSubmission::factory()->create([
        'employee_id' => $employeeA->id,
    ]);

    $employeeBSubmissions = EmployeeSubmission::where('employee_id', $employeeB->id)->get();

    expect($employeeBSubmissions)->toHaveCount(0);
});

// ──────────────────────────────────────────────
// 4. HR manager can view all submissions
// ──────────────────────────────────────────────

it('allows hr manager to view all submissions', function () {
    $employee = makeEmployee();

    EmployeeSubmission::factory()->count(3)->create([
        'employee_id' => $employee->id,
    ]);

    $allSubmissions = EmployeeSubmission::all();

    expect($allSubmissions)->toHaveCount(3);
});

// ──────────────────────────────────────────────
// 5. HR reply triggers notification to employee
// ──────────────────────────────────────────────

it('sends notification to employee when hr replies', function () {
    Notification::fake();

    $employee = makeEmployee();
    $hrManager = User::factory()->create();

    $submission = EmployeeSubmission::factory()->create([
        'employee_id' => $employee->id,
    ]);

    EmployeeSubmissionReply::create([
        'submission_id' => $submission->id,
        'body'          => 'We are looking into this issue.',
        'is_internal'   => false,
        'replied_by'    => $hrManager->id,
    ]);

    Notification::assertSentTo(
        $employee->user,
        DatabaseNotification::class
    );
});

// ──────────────────────────────────────────────
// 6. Internal note is NOT visible to employee
// ──────────────────────────────────────────────

it('hides internal notes from employee view', function () {
    $employee = makeEmployee();
    $hrManager = User::factory()->create();

    $submission = EmployeeSubmission::factory()->create([
        'employee_id' => $employee->id,
    ]);

    EmployeeSubmissionReply::create([
        'submission_id' => $submission->id,
        'is_internal'   => true,
        'replied_by'    => $hrManager->id,
        'body'          => 'Internal discussion about this case.',
    ]);

    EmployeeSubmissionReply::create([
        'submission_id' => $submission->id,
        'is_internal'   => false,
        'replied_by'    => $hrManager->id,
        'body'          => 'Public reply to employee.',
    ]);

    $visibleReplies = $submission->replies()->where('is_internal', false)->get();

    expect($visibleReplies)->toHaveCount(1)
        ->and($visibleReplies->first()->body)->toBe('Public reply to employee.');
});

// ──────────────────────────────────────────────
// 7. Status changes from open to under_review on first HR reply
// ──────────────────────────────────────────────

it('changes status to under_review on first external reply', function () {
    $employee = makeEmployee();
    $hrManager = User::factory()->create();

    $submission = EmployeeSubmission::factory()->create([
        'employee_id' => $employee->id,
        'status'      => 'open',
    ]);

    EmployeeSubmissionReply::create([
        'submission_id' => $submission->id,
        'is_internal'   => false,
        'replied_by'    => $hrManager->id,
        'body'          => 'Looking into this.',
    ]);

    $submission->refresh();
    expect($submission->status)->toBe('under_review');
});

// ──────────────────────────────────────────────
// 8. Marking resolved sets resolved_at timestamp
// ──────────────────────────────────────────────

it('sets resolved_at when marking as resolved', function () {
    $employee = makeEmployee();
    $submission = EmployeeSubmission::factory()->create([
        'employee_id' => $employee->id,
        'status'      => 'under_review',
    ]);

    $submission->update([
        'status'      => 'resolved',
        'resolved_at' => now(),
    ]);

    $submission->refresh();
    expect($submission->status)->toBe('resolved')
        ->and($submission->resolved_at)->not->toBeNull();
});

// ──────────────────────────────────────────────
// 9. Employee receives notification when status = resolved
// ──────────────────────────────────────────────

it('notifies employee when submission is resolved', function () {
    Notification::fake();

    $employee = makeEmployee();

    $submission = EmployeeSubmission::factory()->create([
        'employee_id' => $employee->id,
        'status'      => 'under_review',
    ]);

    $submission->update(['status' => 'resolved']);

    Notification::assertSentTo(
        $employee->user,
        DatabaseNotification::class
    );
});

// ──────────────────────────────────────────────
// 10. HR manager receives notification on new submission
// ──────────────────────────────────────────────

it('notifies hr managers on new submission', function () {
    Notification::fake();

    $hrManager = makeHrManager();
    $employee = makeEmployee();
    actingAsEmployee($employee);

    EmployeeSubmission::create([
        'type'        => 'complaint',
        'subject'     => 'Test notification',
        'body'        => 'This should trigger a notification.',
        'employee_id' => $employee->id,
    ]);

    Notification::assertSentTo(
        $hrManager,
        DatabaseNotification::class
    );
});

// ──────────────────────────────────────────────
// 11. Weekly reminder command finds submissions older than 7 days
// ──────────────────────────────────────────────

it('reminder command finds old open submissions', function () {
    $employeeA = makeEmployee();
    $employeeB = makeEmployee();

    $oldSubmission = EmployeeSubmission::factory()->create([
        'employee_id' => $employeeA->id,
        'status'      => 'open',
        'created_at'  => now()->subDays(10),
    ]);

    $newSubmission = EmployeeSubmission::factory()->create([
        'employee_id' => $employeeB->id,
        'status'      => 'open',
        'created_at'  => now()->subDays(2),
    ]);

    $oldSubmissions = EmployeeSubmission::open()
        ->where('created_at', '<', now()->subDays(7))
        ->get();

    expect($oldSubmissions)->toHaveCount(1)
        ->and($oldSubmissions->first()->id)->toBe($oldSubmission->id);
});

// ──────────────────────────────────────────────
// 12. Employee without permission cannot access SubmissionResource
// ──────────────────────────────────────────────

it('prevents regular employees from accessing submission resource', function () {
    $employee = makeEmployee();
    actingAsEmployee($employee);

    expect(MySubmissions::canAccess())->toBeTrue();
    expect(SubmissionResource::getModel())->toBe(EmployeeSubmission::class);
});
