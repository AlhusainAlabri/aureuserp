<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Webkul\MyNotes\Console\Commands\SendNoteReminders;
use Webkul\MyNotes\Models\Note;
use Webkul\MyNotes\Models\NoteChecklistItem;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    if (! Schema::hasTable('notes')) {
        Artisan::call('migrate', ['--path' => 'plugins/webkul/my-notes/database/migrations', '--force' => true]);
    }
});

function notesUser(): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());
    test()->actingAs($user);

    return $user;
}

function notesCompany(): Company
{
    return Company::query()->first() ?? Company::factory()->create(['currency_id' => null]);
}

it('User can create a text note', function (): void {
    $user = notesUser();

    $note = Note::create([
        'type'       => 'text',
        'title'      => 'Test Note',
        'body'       => 'This is a test note body.',
        'user_id'    => $user->id,
        'company_id' => notesCompany()->id,
    ]);

    expect($note)->toBeInstanceOf(Note::class)
        ->and($note->title)->toBe('Test Note')
        ->and($note->type)->toBe('text');
});

it('User can create a checklist with items', function (): void {
    $user = notesUser();

    $note = Note::create([
        'type'       => 'checklist',
        'title'      => 'Shopping List',
        'user_id'    => $user->id,
        'company_id' => notesCompany()->id,
    ]);

    $note->checklistItems()->createMany([
        ['content' => 'Milk', 'is_checked' => true, 'sort_order' => 0],
        ['content' => 'Bread', 'is_checked' => false, 'sort_order' => 1],
        ['content' => 'Eggs', 'is_checked' => false, 'sort_order' => 2],
    ]);

    expect($note->checklistItems)->toHaveCount(3)
        ->and($note->getChecklistProgress()['done'])->toBe(1)
        ->and($note->getChecklistProgress()['total'])->toBe(3);
});

it('User can create a reminder with future date', function (): void {
    $user = notesUser();

    $note = Note::create([
        'type'        => 'reminder',
        'title'       => 'Doctor Appointment',
        'reminder_at' => now()->addDay(),
        'user_id'     => $user->id,
        'company_id'  => notesCompany()->id,
    ]);

    expect($note->isReminder())->toBeTrue()
        ->and($note->reminder_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('Note auto-generates title from body if title empty', function (): void {
    $user = notesUser();

    $note = Note::create([
        'type'       => 'text',
        'body'       => 'This is the content of my note that should become the title.',
        'user_id'    => $user->id,
        'company_id' => notesCompany()->id,
    ]);

    expect($note->auto_title)->toContain('This is the content');
});

it('User cannot see another user notes', function (): void {
    $userA = notesUser();
    $company = notesCompany();

    $noteA = Note::create([
        'type'       => 'text',
        'title'      => 'User A Note',
        'user_id'    => $userA->id,
        'company_id' => $company->id,
    ]);

    $userB = User::withoutEvents(fn (): User => User::factory()->create());
    test()->actingAs($userB);

    $found = Note::query()->where('ulid', $noteA->ulid)->first();

    expect($found)->toBeNull();
});

it('Pinned notes appear first in query results', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create(['type' => 'text', 'title' => 'Normal', 'user_id' => $user->id, 'company_id' => $company->id, 'is_pinned' => false]);
    Note::create(['type' => 'text', 'title' => 'Pinned', 'user_id' => $user->id, 'company_id' => $company->id, 'is_pinned' => true]);

    $first = Note::query()->orderByRaw('is_pinned DESC, created_at DESC')->first();

    expect($first->is_pinned)->toBeTrue();
});

it('Archived notes excluded from default query', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create(['type' => 'text', 'title' => 'Active', 'user_id' => $user->id, 'company_id' => $company->id, 'is_archived' => false]);
    Note::create(['type' => 'text', 'title' => 'Archived', 'user_id' => $user->id, 'company_id' => $company->id, 'is_archived' => true]);

    $activeCount = Note::query()->notArchived()->count();

    expect($activeCount)->toBe(1);
});

it('Archived notes returned when filter active', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create(['type' => 'text', 'title' => 'Archived', 'user_id' => $user->id, 'company_id' => $company->id, 'is_archived' => true]);

    $archivedCount = Note::query()->archived()->count();

    expect($archivedCount)->toBe(1);
});

it('Checklist progress calculates correctly', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'checklist',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $note->checklistItems()->createMany([
        ['content' => 'A', 'is_checked' => true],
        ['content' => 'B', 'is_checked' => true],
        ['content' => 'C', 'is_checked' => false],
        ['content' => 'D', 'is_checked' => false],
    ]);

    $progress = $note->getChecklistProgress();

    expect($progress['done'])->toBe(2)
        ->and($progress['total'])->toBe(4)
        ->and($progress['percent'])->toBe(50.0);
});

it('Reminder command sends notification at correct time', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create([
        'type'        => 'reminder',
        'title'       => 'Due Reminder',
        'reminder_at' => now()->subMinute(),
        'reminder_sent' => false,
        'user_id'     => $user->id,
        'company_id'  => $company->id,
    ]);

    Artisan::call('notes:send-reminders');

    $note = Note::withoutGlobalScopes()->where('title', 'Due Reminder')->first();

    expect($note->reminder_sent)->toBeTrue();
});

it('Reminder command sends email when reminder_at is past', function (): void {
    Notification::fake();

    $user = notesUser();
    $company = notesCompany();

    Note::create([
        'type'         => 'reminder',
        'title'        => 'Email Reminder',
        'reminder_at'  => now()->subMinute(),
        'reminder_sent'=> false,
        'user_id'      => $user->id,
        'company_id'   => $company->id,
    ]);

    Artisan::call('notes:send-reminders');

    $note = Note::withoutGlobalScopes()->where('title', 'Email Reminder')->first();

    expect($note->reminder_email_sent)->toBeTrue();
});

it('reminder_sent set to true after notification sent', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'          => 'reminder',
        'title'         => 'Test',
        'reminder_at'   => now()->subMinute(),
        'reminder_sent' => false,
        'user_id'       => $user->id,
        'company_id'    => $company->id,
    ]);

    expect($note->reminder_sent)->toBeFalse();

    Artisan::call('notes:send-reminders');

    $note->refresh();

    expect($note->reminder_sent)->toBeTrue();
});

it('Overdue reminder identified correctly', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $overdue = Note::create([
        'type'          => 'reminder',
        'reminder_at'   => now()->subHour(),
        'reminder_sent' => false,
        'user_id'       => $user->id,
        'company_id'    => $company->id,
    ]);

    $future = Note::create([
        'type'          => 'reminder',
        'reminder_at'   => now()->addHour(),
        'reminder_sent' => false,
        'user_id'       => $user->id,
        'company_id'    => $company->id,
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($future->isOverdue())->toBeFalse();
});

it('Color field accepts only valid color names', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'color'      => 'red',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->getColorHexAttribute())->toBe('#EF4444');
});

it('Tags stored and retrieved correctly as array', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'tags'       => ['work', 'urgent'],
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->tags)->toBe(['work', 'urgent']);
});

it('Soft-deleted note not returned in queries', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'title'      => 'To Delete',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $note->delete();

    $found = Note::query()->where('ulid', $note->ulid)->first();

    expect($found)->toBeNull();
});

it('Note linked to meeting references correct meeting_id', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'meeting_id' => 42,
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->meeting_id)->toBe(42);
});

it('Checklist item sort_order saved correctly', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'checklist',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $item = $note->checklistItems()->create([
        'content'    => 'Item',
        'sort_order' => 5,
    ]);

    expect($item->sort_order)->toBe(5);
});

it('Voice note saves audio_path correctly', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'voice',
        'audio_path' => 'notes/voice/test.webm',
        'audio_duration_seconds' => 120,
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->audio_path)->toBe('notes/voice/test.webm')
        ->and($note->audio_duration_seconds)->toBe(120);
});

it('Voice note with transcription appears in search results', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create([
        'type'                 => 'voice',
        'audio_transcription'  => 'Meeting minutes from Monday',
        'user_id'              => $user->id,
        'company_id'           => $company->id,
    ]);

    $results = Note::query()->search('Meeting minutes')->get();

    expect($results)->toHaveCount(1);
});

it('Audio URL is a signed temporary URL', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'voice',
        'audio_path' => 'notes/voice/test.webm',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $url = $note->audio_url;

    expect($url)->toBeString()
        ->and($url)->toContain('expires=');
});
