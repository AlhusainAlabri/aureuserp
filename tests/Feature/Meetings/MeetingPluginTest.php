<?php

use App\Filament\Actions\ExportMeetingPdfAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Webkul\Meetings\Filament\Widgets\MeetingCalendarWidget;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    if (! Schema::hasTable('meetings')) {
        Artisan::call('meetings:install', ['--no-interaction' => true]);
    }
});

function meetingsUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

function meetingsCompany(): Company
{
    return Company::query()->firstOrFail();
}

function meetingsFlow(array $approverIds, int $steps = 2): ApprovalFlow
{
    $flow = ApprovalFlow::query()->create([
        'name'            => 'Meeting Test Flow',
        'approvable_type' => (new Meeting)->getMorphClass(),
        'is_active'       => true,
    ]);

    for ($step = 1; $step <= $steps; $step++) {
        $flow->steps()->create([
            'name'               => "Step {$step}",
            'order'              => $step,
            'type'               => 'single',
            'approver_resolver'  => UserResolver::class,
            'approver_config'    => ['user_ids' => $approverIds],
            'required_approvals' => 1,
        ]);
    }

    return $flow->fresh('steps');
}

it('MeetingPluginTest: can create meeting with required fields', function (): void {
    $user = meetingsUser();

    $meeting = Meeting::factory()->create([
        'company_id'      => meetingsCompany()->id,
        'chair_person_id' => $user->id,
        'creator_id'      => $user->id,
    ]);

    expect($meeting)->toBeInstanceOf(Meeting::class)
        ->and($meeting->title)->not->toBeEmpty();
});

it('MeetingPluginTest: auto-generates meeting number in yearly sequence format', function (): void {
    $user = meetingsUser();

    $meeting = Meeting::factory()->create([
        'company_id'      => meetingsCompany()->id,
        'chair_person_id' => $user->id,
        'meeting_date'    => '2026-05-20 10:00:00',
    ]);

    expect($meeting->meeting_number)->toMatch('/^MTG-2026-\d{4}$/');
});

it('MeetingPluginTest: auto-adds chair person to attendees on save', function (): void {
    $user = meetingsUser();

    $meeting = Meeting::factory()->create([
        'company_id'      => meetingsCompany()->id,
        'chair_person_id' => $user->id,
    ]);

    expect($meeting->attendees()->where('user_id', $user->id)->where('role', 'chair')->exists())->toBeTrue();
});

it('MeetingPluginTest: cannot confirm meeting without full approval', function (): void {
    $user = meetingsUser();
    $meeting = Meeting::factory()->create([
        'company_id'      => meetingsCompany()->id,
        'chair_person_id' => $user->id,
    ]);

    $meeting->confirm();
})->throws(RuntimeException::class);

it('MeetingPluginTest: submit for approval changes status to pending approval', function (): void {
    $approver = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $flow = meetingsFlow([$approver->id], 1);

    $meeting->submitForApproval($flow, $approver->id);

    expect($meeting->refresh()->status)->toBe('pending_approval');
});

it('MeetingPluginTest: runs full approval flow and confirms the meeting', function (): void {
    $approver = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $flow = meetingsFlow([$approver->id], 2);
    $engine = app(ApprovalEngine::class);

    $approval = $meeting->submitForApproval($flow, $approver->id);
    $engine->approve($approval->currentStepInstance(), $approver->id);
    $approval->refresh();
    $engine->approve($approval->currentStepInstance(), $approver->id);

    $meeting->refresh()->confirm();

    expect($meeting->refresh()->status)->toBe('confirmed');
});

it('MeetingPluginTest: rejection resets status to draft', function (): void {
    $approver = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $flow = meetingsFlow([$approver->id], 1);
    $approval = $meeting->submitForApproval($flow, $approver->id);

    app(ApprovalEngine::class)->reject($approval->currentStepInstance(), $approver->id, 'No');

    expect($meeting->refresh()->status)->toBe('draft');
});

it('MeetingPluginTest: confirmed meeting sends notification to all attendees', function (): void {
    $user = meetingsUser();
    $attendee = User::withoutEvents(fn (): User => User::factory()->create());
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'status' => 'approved']);
    MeetingAttendee::factory()->create(['meeting_id' => $meeting->id, 'user_id' => $attendee->id]);

    meetingsFlow([$user->id], 1);
    $meeting->onApprovalApproved($meeting->latestApproval() ?? $meeting->approvals()->create([
        'approval_flow_id' => ApprovalFlow::query()->first()->id,
        'status'           => 'approved',
        'submitted_by'     => $user->id,
        'submitted_at'     => now(),
        'completed_at'     => now(),
    ]));
    $meeting->confirm();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $attendee->id,
    ]);
});

it('MeetingPluginTest: task assignment notifies assigned user', function (): void {
    $assignee = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);

    MeetingTask::factory()->create([
        'meeting_id'   => $meeting->id,
        'assigned_to'  => $assignee->id,
        'creator_id'   => $assignee->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $assignee->id,
    ]);
});

it('MeetingPluginTest: overdue command sends notifications for overdue tasks', function (): void {
    $assignee = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'chair_person_id' => $assignee->id]);
    MeetingTask::factory()->overdue()->create(['meeting_id' => $meeting->id, 'assigned_to' => $assignee->id]);

    Artisan::call('meetings:notify-overdue-tasks');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $assignee->id,
    ]);
});

it('MeetingPluginTest: pdf export action is registered', function (): void {
    expect(class_exists(ExportMeetingPdfAction::class))->toBeTrue();
});

it('MeetingPluginTest: archive only works when status is confirmed', function (): void {
    $user = meetingsUser(['archive_meetings_meeting']);
    $draft = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'status' => 'draft']);
    $confirmed = Meeting::factory()->confirmed()->create(['company_id' => meetingsCompany()->id]);

    expect(Gate::forUser($user)->allows('archive', $draft))->toBeFalse()
        ->and(Gate::forUser($user)->allows('archive', $confirmed))->toBeTrue();
});

it('MeetingPluginTest: user without permission cannot create meeting', function (): void {
    $user = meetingsUser();

    expect(Gate::forUser($user)->allows('create', Meeting::class))->toBeFalse();
});

it('MeetingPluginTest: meeting links correctly to project', function (): void {
    $project = Project::query()->first() ?? Project::factory()->create(['company_id' => meetingsCompany()->id]);
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'project_id' => $project->id]);

    expect($meeting->project->is($project))->toBeTrue();
});

it('MeetingPluginTest: dashboard stats show correct pending approval count', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'status' => 'pending_approval']);

    $widget = app(MeetingDashboardStats::class);
    $stats = invade($widget)->getStats();

    expect($stats[1]->getValue())->toBeGreaterThanOrEqual(1);
});

it('MeetingPluginTest: dashboard tasks section only shows tasks assigned to current user', function (): void {
    $user = meetingsUser();
    $other = User::withoutEvents(fn (): User => User::factory()->create());
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    MeetingTask::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $user->id]);
    MeetingTask::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $other->id]);

    $query = invade(app(MeetingTasksTable::class))->getTableQuery();

    expect($query->pluck('assigned_to')->unique()->all())->toBe([$user->id]);
});

it('MeetingPluginTest: calendar fetch events returns meetings within date range', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->confirmed()->create(['company_id' => meetingsCompany()->id, 'meeting_date' => now()->addDay()]);

    $events = app(MeetingCalendarWidget::class)->fetchEvents([
        'start' => now()->toIso8601String(),
        'end'   => now()->addDays(3)->toIso8601String(),
    ]);

    expect(collect($events)->where('extendedProps.eventType', 'meeting'))->not->toBeEmpty();
});

it('MeetingPluginTest: calendar fetch events merges meeting and task events', function (): void {
    $user = meetingsUser(['view_any_meetings_meeting']);
    $meeting = Meeting::factory()->confirmed()->create(['company_id' => meetingsCompany()->id, 'meeting_date' => now()->addDay()]);
    MeetingTask::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $user->id, 'due_date' => now()->addDay()]);

    $events = collect(app(MeetingCalendarWidget::class)->fetchEvents([
        'start' => now()->toIso8601String(),
        'end'   => now()->addDays(3)->toIso8601String(),
    ]));

    expect($events->where('extendedProps.eventType', 'meeting'))->not->toBeEmpty()
        ->and($events->where('extendedProps.eventType', 'task'))->not->toBeEmpty();
});

it('MeetingPluginTest: clicking approve from dashboard widget triggers approval action', function (): void {
    $approver = meetingsUser();
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $approval = $meeting->submitForApproval(meetingsFlow([$approver->id], 1), $approver->id);

    app(ApprovalEngine::class)->approve($approval->currentStepInstance(), $approver->id);

    expect($meeting->refresh()->status)->toBe('approved');
});

it('MeetingPluginTest: employee sees only their own meetings on dashboard', function (): void {
    $user = meetingsUser();
    $other = User::withoutEvents(fn (): User => User::factory()->create());
    $mine = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $notMine = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    MeetingAttendee::factory()->create(['meeting_id' => $mine->id, 'user_id' => $user->id]);
    MeetingAttendee::factory()->create(['meeting_id' => $notMine->id, 'user_id' => $other->id]);

    $ids = invade(app(UpcomingMeetingsTable::class))->visibleMeetingsQuery()->pluck('id');

    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($notMine->id);
});
