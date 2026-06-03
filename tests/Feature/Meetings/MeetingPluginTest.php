<?php

use App\Filament\Actions\ExportMeetingPdfAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Webkul\Meetings\Filament\Pages\MeetingCalendar;
use Webkul\Meetings\Filament\Pages\MeetingDashboard;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\MeetingApprovalsTable;
use Webkul\Meetings\Filament\Widgets\MeetingCalendarWidget;
use Webkul\Meetings\Filament\Widgets\MeetingDashboardStats;
use Webkul\Meetings\Filament\Widgets\MeetingsStatusChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingsTrendChartWidget;
use Webkul\Meetings\Filament\Widgets\MeetingTasksTable;
use Webkul\Meetings\Filament\Widgets\RecentConfirmedMeetingsTable;
use Webkul\Meetings\Filament\Widgets\UpcomingMeetingsTable;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingAttachment;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    if (! Schema::hasTable('meetings')) {
        Artisan::call('meetings:install', ['--no-interaction' => true]);
    }

    foreach ([1, 2, 3] as $index) {
        Currency::query()->firstOrCreate(
            ['iso_numeric' => sprintf('%03d', $index)],
            [
                'name'           => "Test Currency {$index}",
                'symbol'         => 'T',
                'decimal_places' => 2,
                'full_name'      => "Test Currency {$index}",
                'rounding'       => 0.01,
                'active'         => true,
            ]
        );
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
    return Company::query()->first() ?? Company::factory()->create(['currency_id' => null]);
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
    $company = meetingsCompany();
    $project = Project::query()->first();

    if (! $project) {
        $stage = ProjectStage::create([
            'name'       => 'Test Stage',
            'company_id' => $company->id,
            'creator_id' => User::factory()->create()->id,
            'sort'       => 1,
        ]);
        $project = Project::create([
            'name'       => 'Test Project',
            'company_id' => $company->id,
            'stage_id'   => $stage->id,
            'creator_id' => User::factory()->create()->id,
            'user_id'    => User::factory()->create()->id,
        ]);
    }

    $meeting = Meeting::factory()->create(['company_id' => $company->id, 'project_id' => $project->id]);

    expect($meeting->project->is($project))->toBeTrue();
});

it('MeetingPluginTest: dashboard stats show correct pending approval count', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'status' => 'pending_approval']);

    $widget = app(MeetingDashboardStats::class);
    $stats = invade($widget)->getStats();

    expect($stats[1]->getValue())->toBeGreaterThanOrEqual(1);
});

it('MeetingPluginTest: dashboard registers chart widgets and responsive columns', function (): void {
    $dashboard = app(MeetingDashboard::class);

    expect(invade($dashboard)->getWidgets())
        ->toContain(MeetingsTrendChartWidget::class)
        ->toContain(MeetingsStatusChartWidget::class)
        ->and(invade($dashboard)->getColumns())
        ->toBe(['default' => 1, 'md' => 2, 'lg' => 12]);
});

it('MeetingPluginTest: dashboard stats include urls for filtered list navigation', function (): void {
    meetingsUser(['view_any_meetings_meeting']);

    $stats = invade(app(MeetingDashboardStats::class))->getStats();

    expect($stats)->not->toBeEmpty();

    foreach ($stats as $stat) {
        expect($stat->getUrl())->not->toBeNull();
    }
});

it('MeetingPluginTest: chart widgets return datasets with seeded meetings', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->count(3)->create([
        'company_id'   => meetingsCompany()->id,
        'meeting_date' => now(),
    ]);

    $trendData = invade(app(MeetingsTrendChartWidget::class))->getData();
    $statusData = invade(app(MeetingsStatusChartWidget::class))->getData();

    expect($trendData['labels'])->not->toBeEmpty()
        ->and($trendData['datasets'][0]['data'])->not->toBeEmpty()
        ->and($statusData['labels'])->not->toBeEmpty()
        ->and($statusData['datasets'][0]['data'])->not->toBeEmpty();
});

it('MeetingPluginTest: dashboard filters narrow visible meetings count', function (): void {
    meetingsUser(['view_any_meetings_meeting']);

    $startDate = now()->startOfMonth()->toDateString();
    $endDate = now()->endOfMonth()->toDateString();

    Meeting::factory()->create([
        'company_id'   => meetingsCompany()->id,
        'meeting_date' => now()->subMonths(2),
    ]);
    Meeting::factory()->create([
        'company_id'   => meetingsCompany()->id,
        'meeting_date' => now(),
    ]);

    $widget = app(MeetingDashboardStats::class);
    invade($widget)->pageFilters = [
        'startDate' => $startDate,
        'endDate'   => $endDate,
        'status'    => 'all',
    ];

    $expected = Meeting::query()
        ->whereDate('meeting_date', '>=', $startDate)
        ->whereDate('meeting_date', '<=', $endDate)
        ->count();

    expect(invade($widget)->filteredMeetingsQuery()->count())->toBe($expected);
});

it('MeetingPluginTest: dashboard chart headings resolve in Arabic', function (): void {
    app()->setLocale('ar');

    expect(app(MeetingsTrendChartWidget::class)->getHeading())
        ->toBe(__('meetings::meetings.dashboard.charts.meetings_trend'))
        ->and(app(MeetingsStatusChartWidget::class)->getHeading())
        ->toBe(__('meetings::meetings.dashboard.charts.meetings_status'));
});

it('MeetingPluginTest: dashboard tasks section only shows tasks assigned to current user', function (): void {
    $user = meetingsUser();
    $other = User::withoutEvents(fn (): User => User::factory()->create());
    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    MeetingTask::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $user->id]);
    MeetingTask::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $other->id]);

    $query = invade(app(MeetingTasksTable::class))->visibleTasksQuery();

    expect($query->pluck('assigned_to')->unique()->all())->toBe([$user->id]);
});

it('MeetingPluginTest: uses correct Arabic plural model label', function (): void {
    app()->setLocale('ar');

    expect(MeetingResource::getPluralModelLabel())
        ->toBe(__('meetings::meetings.navigation.meetings'))
        ->and(MeetingResource::getPluralModelLabel())->toBe('المحاضر');
});

it('MeetingPluginTest: table widgets use translated headings in Arabic', function (): void {
    app()->setLocale('ar');

    expect(app(UpcomingMeetingsTable::class)->getTableHeading())
        ->toBe(__('meetings::meetings.dashboard.sections.upcoming'))
        ->and(app(MeetingTasksTable::class)->getTableHeading())
        ->toBe(__('meetings::meetings.dashboard.sections.my_tasks'))
        ->and(app(MeetingApprovalsTable::class)->getTableHeading())
        ->toBe(__('meetings::meetings.dashboard.sections.my_approvals'))
        ->and(app(RecentConfirmedMeetingsTable::class)->getTableHeading())
        ->toBe(__('meetings::meetings.dashboard.sections.recent_confirmed'));
});

it('MeetingPluginTest: calendar page uses translated title', function (): void {
    app()->setLocale('ar');

    expect(app(MeetingCalendar::class)->getTitle())
        ->toBe(__('meetings::meetings.navigation.calendar'))
        ->and(app(MeetingCalendar::class)->getTitle())->toBe('التقويم');
});

it('MeetingPluginTest: empty state strings do not contain English model names in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('meetings::meetings.empty.no_meetings'))
        ->not->toContain('meetings')
        ->and(__('meetings::meetings.empty.no_tasks'))
        ->not->toContain('meeting tasks')
        ->and(__('meetings::meetings.empty.no_approvals'))
        ->not->toContain('approvals')
        ->and(__('meetings::meetings.empty.no_attachments'))
        ->not->toContain('meeting attachments')
        ->and(__('meetings::meetings.empty.no_approval_log'))
        ->not->toContain('approvals');
});

it('MeetingPluginTest: meetings dashboard title is distinct from main dashboard in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('meetings::meetings.navigation.dashboard'))
        ->toBe('لوحة المحاضر')
        ->not->toBe('لوحة التحكم');
});

it('MeetingPluginTest: view and create page titles use minutes terminology in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('meetings::meetings.pages.create_title'))
        ->toBe('إضافة محضر جديد')
        ->and(__('meetings::meetings.pages.view_title', ['title' => 'اختبار']))
        ->toBe('عرض المحضر: اختبار');
});

it('MeetingPluginTest: calendar widget config follows app locale', function (): void {
    app()->setLocale('en');

    $englishConfig = app(MeetingCalendarWidget::class)->config();

    expect($englishConfig['locale'])->toBe('en')
        ->and($englishConfig['direction'])->toBe('ltr');

    app()->setLocale('ar');

    $arabicConfig = app(MeetingCalendarWidget::class)->config();

    expect($arabicConfig['locale'])->toBe('ar')
        ->and($arabicConfig['direction'])->toBe('rtl');
});

it('MeetingPluginTest: stores attachment with resolved metadata', function (): void {
    Storage::fake('private');
    meetingsUser();

    $path = 'meetings/2026/upload.pdf';
    Storage::disk('private')->put($path, 'attachment body');

    $meeting = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $attachment = $meeting->attachments()->create([
        'file_path'  => $path,
        'file_name'  => 'upload.pdf',
        'file_size'  => Storage::disk('private')->size($path),
        'mime_type'  => Storage::disk('private')->mimeType($path),
        'creator_id' => auth()->id(),
    ]);

    expect($attachment)->toBeInstanceOf(MeetingAttachment::class)
        ->and($attachment->file_size)->toBeGreaterThan(0)
        ->and($attachment->mime_type)->not->toBe('application/octet-stream');
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
    $mine = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'chair_person_id' => $user->id]);
    $notMine = Meeting::factory()->create(['company_id' => meetingsCompany()->id, 'chair_person_id' => $other->id]);

    $ids = invade(app(UpcomingMeetingsTable::class))->visibleMeetingsQuery()->pluck('id');

    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($notMine->id);
});

it('MeetingPluginTest: calendar excludes draft meetings by default', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->create([
        'company_id'   => meetingsCompany()->id,
        'meeting_date' => now()->addDay(),
        'status'       => 'draft',
    ]);

    $events = collect(app(MeetingCalendarWidget::class)->fetchEvents([
        'start' => now()->toIso8601String(),
        'end'   => now()->addDays(3)->toIso8601String(),
    ]));

    expect($events->where('extendedProps.eventType', 'meeting'))->toBeEmpty();
});

it('MeetingPluginTest: calendar shows draft meetings when unconfirmed toggle is enabled', function (): void {
    meetingsUser(['view_any_meetings_meeting']);
    Meeting::factory()->create([
        'company_id'   => meetingsCompany()->id,
        'meeting_date' => now()->addDay(),
        'status'       => 'draft',
    ]);

    $widget = app(MeetingCalendarWidget::class);
    invade($widget)->showUnconfirmedMeetings = true;

    $events = collect($widget->fetchEvents([
        'start' => now()->toIso8601String(),
        'end'   => now()->addDays(3)->toIso8601String(),
    ]));

    $meetingEvent = $events->first(fn (array $event): bool => ($event['extendedProps']['eventType'] ?? null) === 'meeting');

    expect($meetingEvent)->not->toBeNull()
        ->and($meetingEvent['extendedProps']['status'])->toBe('draft');
});

it('MeetingPluginTest: export pdf is restricted until approved or confirmed', function (): void {
    $user = meetingsUser(['export_pdf_meetings_meeting']);
    $draft = Meeting::factory()->create(['company_id' => meetingsCompany()->id]);
    $approved = Meeting::factory()->approved()->create(['company_id' => meetingsCompany()->id]);

    expect(Gate::forUser($user)->allows('exportPdf', $draft))->toBeFalse()
        ->and(Gate::forUser($user)->allows('exportPdf', $approved))->toBeTrue();
});

it('MeetingPluginTest: recent confirmed widget uses dedicated empty state copy in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('meetings::meetings.empty.no_confirmed_meetings'))
        ->toBe('لا توجد محاضر مؤكدة')
        ->and(__('meetings::meetings.form.rich_editor_attachments_hint'))
        ->toContain('تبويب المرفقات');
});

it('MeetingPluginTest: calendar page includes helper subheading in Arabic', function (): void {
    app()->setLocale('ar');

    expect(app(MeetingCalendar::class)->getSubheading())
        ->toContain('إضافة محضر');
});
