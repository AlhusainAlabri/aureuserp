<?php

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Resources\MeetingResource\Pages\ViewMeeting;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\MeetingAttendeeRelationManager;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\MeetingTaskRelationManager;
use Webkul\Meetings\Filament\Widgets\MeetingCalendarWidget;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingAttendee;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

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

function relationManagersUser(array $permissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

function relationManagersCompany(): Company
{
    return Company::query()->first() ?? Company::factory()->create(['currency_id' => null]);
}

function relationManagersMeeting(array $attributes = []): Meeting
{
    return Meeting::factory()->create([
        'company_id' => relationManagersCompany()->id,
        ...$attributes,
    ]);
}

it('MeetingRelationManagersTest: task relation manager can create edit complete and delete tasks', function (): void {
    $user = relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'manage_tasks_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['chair_person_id' => $user->id]);
    $assignee = User::withoutEvents(fn (): User => User::factory()->create());

    Livewire::test(MeetingTaskRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'title'       => 'Follow up budget',
            'assigned_to' => $assignee->id,
            'priority'    => 'high',
            'status'      => 'pending',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $task = MeetingTask::query()->where('meeting_id', $meeting->id)->first();

    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Follow up budget');

    Livewire::test(MeetingTaskRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('edit')->table($task), [
            'title'       => 'Follow up budget',
            'assigned_to' => $assignee->id,
            'priority'    => 'high',
            'status'      => 'in_progress',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($task->refresh()->status)->toBe('in_progress');

    Livewire::test(MeetingTaskRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('complete')->table($task))
        ->assertNotified();

    expect($task->refresh()->status)->toBe('completed');

    Livewire::test(MeetingTaskRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('delete')->table($task))
        ->assertNotified();

    expect(MeetingTask::query()->find($task->id))->toBeNull();
});

it('MeetingRelationManagersTest: attendee relation manager rejects duplicate users', function (): void {
    $user = relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'manage_attendees_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['chair_person_id' => $user->id]);
    $attendee = User::withoutEvents(fn (): User => User::factory()->create());

    MeetingAttendee::factory()->create([
        'meeting_id' => $meeting->id,
        'user_id'    => $attendee->id,
    ]);

    Livewire::test(MeetingAttendeeRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'user_id'  => $attendee->id,
            'role'     => 'member',
            'attended' => false,
        ])
        ->assertHasFormErrors(['user_id']);
});

it('MeetingRelationManagersTest: attendee relation manager can sign attendee', function (): void {
    $user = relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'manage_attendees_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['chair_person_id' => $user->id]);
    $guest = User::withoutEvents(fn (): User => User::factory()->create());
    $attendee = MeetingAttendee::factory()->create([
        'meeting_id' => $meeting->id,
        'user_id'    => $guest->id,
        'signed_at'  => null,
    ]);

    Livewire::test(MeetingAttendeeRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('sign')->table($attendee));

    expect($attendee->refresh()->signed_at)->not->toBeNull();
});

it('MeetingRelationManagersTest: attachment creation requires manage attachments permission', function (): void {
    Storage::fake('private');

    $user = relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'manage_attachments_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['chair_person_id' => $user->id]);

    $path = 'meetings/2026/minutes.pdf';
    Storage::disk('private')->put($path, 'attachment body');

    expect(Gate::forUser($user)->allows('manageAttachments', $meeting))->toBeTrue();

    $attachment = $meeting->attachments()->create([
        'file_path'  => $path,
        'file_name'  => 'minutes.pdf',
        'file_size'  => Storage::disk('private')->size($path),
        'mime_type'  => Storage::disk('private')->mimeType($path),
        'creator_id' => $user->id,
    ]);

    expect($attachment->file_name)->toBe('minutes.pdf')
        ->and($attachment->mime_type)->toBe('application/pdf');
});

it('MeetingRelationManagersTest: users without manage attachments permission cannot upload', function (): void {
    $user = relationManagersUser(['view_meetings_meeting']);
    $meeting = relationManagersMeeting();

    expect(Gate::forUser($user)->allows('manageAttachments', $meeting))->toBeFalse();
});

it('MeetingRelationManagersTest: relation manager empty states use Arabic copy without English model names', function (): void {
    app()->setLocale('ar');

    expect(__('meetings::meetings.empty.no_meeting_tasks'))
        ->toBe('لا توجد مهام')
        ->and(__('meetings::meetings.empty.no_attendees'))
        ->toBe('لا يوجد حضور')
        ->and(__('meetings::meetings.validation.duplicate_attendee'))
        ->not->toContain('unique');
});

it('MeetingRelationManagersTest: manage mutations denied on archived meetings', function (): void {
    $user = relationManagersUser([
        'manage_tasks_meetings_meeting',
        'manage_attendees_meetings_meeting',
        'manage_attachments_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['status' => 'archived']);

    expect(Gate::forUser($user)->allows('manageTasks', $meeting))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageAttendees', $meeting))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageAttachments', $meeting))->toBeFalse();
});

it('MeetingRelationManagersTest: export pdf remains blocked on draft meetings', function (): void {
    $user = relationManagersUser(['export_pdf_meetings_meeting']);
    $draft = relationManagersMeeting(['status' => 'draft']);
    $approved = relationManagersMeeting(['status' => 'approved', 'chair_person_id' => $user->id]);

    expect(Gate::forUser($user)->allows('exportPdf', $draft))->toBeFalse()
        ->and(Gate::forUser($user)->allows('exportPdf', $approved))->toBeTrue();
});

it('MeetingRelationManagersTest: relation managers expose record counts as tab badges', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create());
    $meeting = relationManagersMeeting(['chair_person_id' => $user->id]);
    $assignee = User::withoutEvents(fn (): User => User::factory()->create());

    MeetingTask::factory()->count(2)->create([
        'meeting_id'   => $meeting->id,
        'assigned_to'  => $assignee->id,
    ]);

    expect(MeetingTaskRelationManager::getBadge($meeting->fresh(), ViewMeeting::class))->toBe('2')
        ->and(MeetingAttendeeRelationManager::getBadge($meeting->fresh(), ViewMeeting::class))->toBe('1');
});

it('MeetingRelationManagersTest: attendee relation manager can quick add secretary', function (): void {
    relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'manage_attendees_meetings_meeting',
    ]);
    $secretary = User::withoutEvents(fn (): User => User::factory()->create());
    $chair = User::withoutEvents(fn (): User => User::factory()->create());
    $meeting = relationManagersMeeting([
        'chair_person_id' => $chair->id,
        'secretary_id'    => $secretary->id,
    ])->refresh();

    Livewire::test(MeetingAttendeeRelationManager::class, [
        'ownerRecord' => $meeting,
        'pageClass'   => ViewMeeting::class,
    ])
        ->callAction(TestAction::make('addSecretary')->table())
        ->assertNotified();

    expect($meeting->fresh()->attendees()->where('user_id', $secretary->id)->where('role', 'secretary')->exists())->toBeTrue();
});

it('MeetingRelationManagersTest: archived meeting view shows read only subheading', function (): void {
    app()->setLocale('ar');

    relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['status' => 'archived']);

    Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
        ->assertSuccessful();

    expect(Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])->instance()->getSubheading())
        ->toBe(__('meetings::meetings.archived.read_only_notice'));
});

it('MeetingRelationManagersTest: calendar widget exposes status legend items', function (): void {
    app()->setLocale('ar');

    $widget = new MeetingCalendarWidget;
    $widget->showUnconfirmedMeetings = true;

    expect($widget->getStatusLegendItems())->toHaveCount(4)
        ->and($widget->getStatusLegendItems()[0]['label'])->toBe(__('meetings::meetings.calendar.status_legend.confirmed'));
});

it('MeetingRelationManagersTest: authorized users can update meeting status', function (): void {
    $user = relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'update_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['status' => 'draft']);

    expect(Gate::forUser($user)->allows('updateStatus', $meeting))->toBeTrue();

    MeetingResource::applyStatusChange($meeting, 'pending_approval');

    expect($meeting->refresh()->status)->toBe('pending_approval');
});

it('MeetingRelationManagersTest: confirming a meeting without approval throws', function (): void {
    $meeting = relationManagersMeeting(['status' => 'draft']);

    MeetingResource::applyStatusChange($meeting, 'confirmed');
})->throws(RuntimeException::class);

it('MeetingRelationManagersTest: view page can change meeting status via action', function (): void {
    relationManagersUser([
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'update_meetings_meeting',
    ]);
    $meeting = relationManagersMeeting(['status' => 'draft']);

    Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
        ->callAction('changeStatus', data: ['status' => 'pending_approval'])
        ->assertNotified();

    expect($meeting->refresh()->status)->toBe('pending_approval');
});
