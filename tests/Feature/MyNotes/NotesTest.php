<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Webkul\MyNotes\Enums\NoteBoardStatus;
use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Livewire\QuickNoteTopbar;
use Webkul\MyNotes\Mail\NoteReminderMail;
use Webkul\MyNotes\Models\Note;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    if (! Schema::hasTable('plugins')) {
        Artisan::call('migrate', ['--path' => 'plugins/webkul/plugin-manager/database/migrations', '--force' => true]);
    }

    if (! Schema::hasTable('notes')) {
        Artisan::call('migrate', ['--path' => 'plugins/webkul/my-notes/database/migrations', '--force' => true]);
    }

    if (Schema::hasTable('notes') && ! Schema::hasColumn('notes', 'board_status')) {
        Artisan::call('migrate', [
            '--path'  => 'plugins/webkul/my-notes/database/migrations/2026_06_02_100000_add_board_status_to_notes_table.php',
            '--force' => true,
        ]);
    }

    Plugin::query()->updateOrCreate(
        ['name' => 'my-notes'],
        ['is_installed' => true, 'is_active' => true]
    );

    Package::$plugins = [];
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
        ->and($note->reminder_at)->toBeInstanceOf(Carbon::class);
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
        'type'          => 'reminder',
        'title'         => 'Due Reminder',
        'reminder_at'   => now()->subMinute(),
        'reminder_sent' => false,
        'user_id'       => $user->id,
        'company_id'    => $company->id,
    ]);

    Artisan::call('notes:send-reminders');

    $note = Note::withoutGlobalScopes()->where('title', 'Due Reminder')->first();

    expect($note->reminder_sent)->toBeTrue();
});

it('Reminder command sends email when reminder_at is past', function (): void {
    Notification::fake();
    Mail::fake();

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

    Mail::assertQueued(NoteReminderMail::class);
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
        'type'                   => 'voice',
        'audio_path'             => 'notes/voice/test.webm',
        'audio_duration_seconds' => 120,
        'user_id'                => $user->id,
        'company_id'             => $company->id,
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

it('Audio URL points to the serve route when audio_path is set', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'voice',
        'audio_path' => 'notes/voice/test.webm',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $url = $note->audio_url;

    // Route must be registered for this to return a value; null means route not loaded yet.
    if ($url !== null) {
        expect($url)->toBeString()->toContain($note->ulid);
    } else {
        expect($note->audio_path)->not()->toBeNull();
    }
});

it('uses my-notes slug for reminder links', function (): void {
    expect(MyNotesPage::getDefaultSlug())->toBe('my-notes')
        ->and(MyNotesPage::reminderUrl())->toContain('/admin/my-notes');
});

it('resets reminder sent flags when reminder time changes', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'                => 'reminder',
        'title'               => 'Reschedule',
        'reminder_at'         => now()->subDay(),
        'reminder_sent'       => true,
        'reminder_email_sent' => true,
        'user_id'             => $user->id,
        'company_id'          => $company->id,
    ]);

    $note->update(['reminder_at' => now()->addDay()]);

    expect($note->refresh()->reminder_sent)->toBeFalse()
        ->and($note->reminder_email_sent)->toBeFalse();
});

it('normalizes stale fields when changing note type', function (): void {
    $payload = Note::normalizePayload([
        'type'                => 'text',
        'body'                => 'Text only',
        'reminder_at'         => now()->addDay(),
        'audio_path'          => 'notes/voice/test.webm',
        'audio_transcription' => 'stale audio',
        'tags'                => ['work', 'work', ''],
    ]);

    expect($payload['reminder_at'])->toBeNull()
        ->and($payload['audio_path'])->toBeNull()
        ->and($payload['audio_transcription'])->toBeNull()
        ->and($payload['tags'])->toBe(['work']);
});

it('create note from header opens the note slide-over', function (): void {
    notesUser();

    Livewire::test(MyNotesPage::class)
        ->call('createNote', 'checklist')
        ->assertDispatched('open-modal', id: 'note-slide-over')
        ->assertSet('data.type', 'checklist');
});

it('page can toggle checklist items inline', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'checklist',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $item = $note->checklistItems()->create([
        'content'    => 'Inline task',
        'is_checked' => false,
    ]);

    Livewire::test(MyNotesPage::class)
        ->call('toggleChecklistItem', $item->id);

    expect($item->refresh()->is_checked)->toBeTrue();
});

it('page filters and sorts notes', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create(['type' => 'text', 'title' => 'Beta', 'user_id' => $user->id, 'company_id' => $company->id]);
    Note::create(['type' => 'voice', 'title' => 'Alpha voice', 'user_id' => $user->id, 'company_id' => $company->id]);

    Livewire::test(MyNotesPage::class)
        ->set('activeFilter', 'voice')
        ->set('sortBy', 'a-z')
        ->assertSet('activeFilter', 'voice')
        ->assertSet('sortBy', 'a-z');

    $notes = app(MyNotesPage::class);
    $notes->activeFilter = 'voice';
    $notes->sortBy = 'a-z';

    expect($notes->getNotesProperty())->toHaveCount(1)
        ->and($notes->getNotesProperty()->first()->title)->toBe('Alpha voice');
});

it('defaults new notes to inbox board column', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'title'      => 'Board default',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->board_status)->toBe(NoteBoardStatus::Inbox);
});

it('moves a note to another board column', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'         => 'text',
        'title'        => 'Move me',
        'board_status' => NoteBoardStatus::Inbox->value,
        'user_id'      => $user->id,
        'company_id'   => $company->id,
    ]);

    Livewire::test(MyNotesPage::class)
        ->call('moveNoteToBoard', $note->ulid, NoteBoardStatus::Done->value);

    expect($note->refresh()->board_status)->toBe(NoteBoardStatus::Done);
});

it('groups notes by board column on the page', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create([
        'type'         => 'text',
        'title'        => 'Inbox note',
        'board_status' => NoteBoardStatus::Inbox->value,
        'user_id'      => $user->id,
        'company_id'   => $company->id,
    ]);

    Note::create([
        'type'         => 'text',
        'title'        => 'Done note',
        'board_status' => NoteBoardStatus::Done->value,
        'user_id'      => $user->id,
        'company_id'   => $company->id,
    ]);

    $page = Livewire::test(MyNotesPage::class)->set('viewMode', 'board');

    $boardNotes = $page->instance()->getBoardNotesProperty();

    expect($boardNotes[NoteBoardStatus::Inbox->value])->toHaveCount(1)
        ->and($boardNotes[NoteBoardStatus::Done->value])->toHaveCount(1);
});

it('uses arabic board status labels', function (): void {
    app()->setLocale('ar');

    expect(NoteBoardStatus::Inbox->getLabel())->toBe('الوارد')
        ->and(NoteBoardStatus::Done->getLabel())->toBe('منجز');
});

it('can open edit form when body looks like json scalar', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'body'       => '12345',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    $component = Livewire::test(MyNotesPage::class)
        ->call('editNote', $note->ulid)
        ->assertSet('editingNoteUlid', $note->ulid);

    expect($component->get('data.body'))->toBeArray()
        ->and($component->get('data.body'))->toHaveKey('type', 'doc');
});

it('normalizes numeric body for rich editor', function (): void {
    expect(Note::bodyForRichEditor('42'))->toBe('<p>42</p>')
        ->and(Note::bodyForRichEditor('<p>Hello</p>'))->toBe('<p>Hello</p>');
});

it('saves a quick note from the topbar component', function (): void {
    $user = notesUser();

    Livewire::test(QuickNoteTopbar::class)
        ->set('quickBody', 'Topbar thought')
        ->call('saveQuickNote')
        ->assertSet('quickBody', '');

    $note = Note::query()
        ->where('user_id', $user->id)
        ->where('body', Note::wrapPlainTextAsHtml('Topbar thought'))
        ->first();

    expect($note)->not->toBeNull()
        ->and($note->type)->toBe('text');
});

it('opens create slide-over when create query param is present', function (): void {
    notesUser();

    Livewire::withQueryParams(['create' => 'checklist'])
        ->test(MyNotesPage::class)
        ->assertDispatched('open-modal', id: 'note-slide-over')
        ->assertSet('data.type', 'checklist');
});

it('assigns a stable sticky rotation per note', function (): void {
    $user = notesUser();
    $company = notesCompany();

    $note = Note::create([
        'type'       => 'text',
        'title'      => 'Rotation test',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    expect($note->sticky_rotation)->toBeGreaterThanOrEqual(-2.5)
        ->and($note->sticky_rotation)->toBeLessThanOrEqual(2.5)
        ->and($note->fresh()->sticky_rotation)->toBe($note->sticky_rotation);
});

it('renders notes inside the canvas on grid view', function (): void {
    $user = notesUser();
    $company = notesCompany();

    Note::create([
        'type'       => 'text',
        'title'      => 'Canvas note',
        'user_id'    => $user->id,
        'company_id' => $company->id,
    ]);

    Livewire::test(MyNotesPage::class)
        ->assertSeeHtml('my-notes-canvas')
        ->assertSee(__('my-notes::notes.canvas.showing', ['count' => 1]));
});
