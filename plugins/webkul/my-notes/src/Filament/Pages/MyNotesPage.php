<?php

namespace Webkul\MyNotes\Filament\Pages;

use App\Support\FilamentUrl;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Attributes\Url;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Meetings\Models\Meeting;
use Webkul\MyNotes\Enums\NoteBoardStatus;
use Webkul\MyNotes\Models\Note;
use Webkul\MyNotes\Models\NoteChecklistItem;
use Webkul\Project\Models\Project;

class MyNotesPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'my-notes';

    protected string $view = 'my-notes::pages.my-notes';

    public ?array $data = [];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'filter')]
    public string $activeFilter = 'all';

    #[Url(as: 'view')]
    public string $viewMode = 'grid';

    #[Url(as: 'sort')]
    public string $sortBy = 'newest';

    public ?string $editingNoteUlid = null;

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public static function getNavigationLabel(): string
    {
        return __('my-notes::notes.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.my-notes');
    }

    public function getTitle(): string|Htmlable
    {
        return __('my-notes::notes.navigation.label');
    }

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        if (class_exists(FilamentUrl::class)) {
            $parameters = FilamentUrl::withLocale($parameters);
        }

        $url = parent::getUrl($parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);

        if (class_exists(FilamentUrl::class)) {
            return FilamentUrl::appendLocaleToUrl($url);
        }

        return $url;
    }

    public static function reminderUrl(): string
    {
        try {
            return static::getUrl();
        } catch (\Throwable) {
            return url('/admin/'.static::getDefaultSlug());
        }
    }

    public function mount(): void
    {
        if (! in_array($this->viewMode, ['grid', 'list', 'calendar', 'board'], true)) {
            $this->viewMode = 'grid';
        }

        $this->form->fill($this->defaultFormData());

        $createType = request()->query('create');

        if (is_string($createType) && in_array($createType, Note::TYPES, true)) {
            $this->createNote($createType);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(Note::class)
            ->statePath('data')
            ->columns(2)
            ->components([
                Select::make('type')
                    ->label(__('my-notes::notes.form.fields.type'))
                    ->options([
                        'text'      => __('my-notes::notes.types.text'),
                        'checklist' => __('my-notes::notes.types.checklist'),
                        'reminder'  => __('my-notes::notes.types.reminder'),
                        'voice'     => __('my-notes::notes.types.voice'),
                    ])
                    ->default('text')
                    ->live()
                    ->required(),

                Select::make('board_status')
                    ->label(__('my-notes::notes.form.fields.board_status'))
                    ->options(NoteBoardStatus::options())
                    ->default(NoteBoardStatus::Inbox->value)
                    ->native(false)
                    ->searchable()
                    ->preload(),

                Select::make('color')
                    ->label(__('my-notes::notes.form.fields.color'))
                    ->options([
                        'default' => __('my-notes::notes.colors.default'),
                        'red'     => __('my-notes::notes.colors.red'),
                        'orange'  => __('my-notes::notes.colors.orange'),
                        'yellow'  => __('my-notes::notes.colors.yellow'),
                        'green'   => __('my-notes::notes.colors.green'),
                        'teal'    => __('my-notes::notes.colors.teal'),
                        'blue'    => __('my-notes::notes.colors.blue'),
                        'purple'  => __('my-notes::notes.colors.purple'),
                        'pink'    => __('my-notes::notes.colors.pink'),
                        'gray'    => __('my-notes::notes.colors.gray'),
                    ])
                    ->default('default')
                    ->rules(['in:'.implode(',', Note::COLORS)])
                    ->required(),

                TextInput::make('title')
                    ->label(__('my-notes::notes.form.fields.title'))
                    ->placeholder(__('my-notes::notes.form.fields.title'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                RichEditor::make('body')
                    ->label(__('my-notes::notes.form.fields.body'))
                    ->maxLength(20000)
                    ->visible(fn (callable $get): bool => in_array($get('type'), ['text', 'reminder']))
                    ->columnSpanFull(),

                Repeater::make('checklist_items')
                    ->label(__('my-notes::notes.form.fields.checklist_items'))
                    ->visible(fn (callable $get): bool => $get('type') === 'checklist')
                    ->schema([
                        TextInput::make('content')
                            ->label(__('my-notes::notes.form.fields.item_content'))
                            ->maxLength(255)
                            ->required(),
                        Toggle::make('is_checked')
                            ->label(__('my-notes::notes.form.fields.is_checked'))
                            ->inline(false),
                    ])
                    ->maxItems(100)
                    ->addActionLabel(__('my-notes::notes.actions.add_item'))
                    ->columnSpanFull(),

                DateTimePicker::make('reminder_at')
                    ->label(__('my-notes::notes.form.fields.reminder_at'))
                    ->visible(fn (callable $get): bool => $get('type') === 'reminder')
                    ->native(false)
                    ->seconds(false)
                    ->locale(fn (): string => app()->getLocale())
                    ->displayFormat(fn (): string => app()->getLocale() === 'ar' ? 'j F Y H:i' : 'M j, Y H:i')
                    ->required(fn (callable $get): bool => $get('type') === 'reminder')
                    ->columnSpanFull(),

                FileUpload::make('audio_path')
                    ->label(__('my-notes::notes.form.fields.audio_path'))
                    ->visible(fn (callable $get): bool => $get('type') === 'voice')
                    ->disk('local')
                    ->directory(fn () => 'notes/voice/'.Auth::id())
                    ->acceptedFileTypes(['audio/webm', 'audio/mp3', 'audio/wav', 'audio/mpeg'])
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Textarea::make('audio_transcription')
                    ->label(__('my-notes::notes.form.fields.audio_transcription'))
                    ->maxLength(10000)
                    ->visible(fn (callable $get): bool => $get('type') === 'voice')
                    ->columnSpanFull(),

                TagsInput::make('tags')
                    ->label(__('my-notes::notes.form.fields.tags'))
                    ->placeholder(__('my-notes::notes.form.fields.tags'))
                    ->columnSpanFull(),

                Select::make('meeting_id')
                    ->label(__('my-notes::notes.form.fields.link_meeting'))
                    ->options(function (): array {
                        if (! class_exists(Meeting::class)) {
                            return [];
                        }

                        return Meeting::query()
                            ->when(
                                method_exists(Meeting::class, 'scopeActive'),
                                fn (Builder $query): Builder => $query->active()
                            )
                            ->limit(50)
                            ->pluck('title', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->visible(fn (): bool => class_exists(Meeting::class))
                    ->nullable(),

                Select::make('project_id')
                    ->label(__('my-notes::notes.form.fields.link_project'))
                    ->options(function (): array {
                        if (! class_exists(Project::class)) {
                            return [];
                        }

                        return Project::query()
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => class_exists(Project::class))
                    ->nullable(),

                Select::make('correspondence_id')
                    ->label(__('my-notes::notes.form.fields.link_correspondence'))
                    ->options(function (): array {
                        if (! class_exists(Correspondence::class) || ! DbSchema::hasTable('correspondences')) {
                            return [];
                        }

                        return Correspondence::query()
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Correspondence $record): array => [
                                $record->id => trim(($record->reference_number ?? '').' — '.($record->subject ?? '')),
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => class_exists(Correspondence::class) && DbSchema::hasTable('correspondences'))
                    ->nullable(),

                Toggle::make('is_pinned')
                    ->label(__('my-notes::notes.form.fields.is_pinned'))
                    ->inline(false),
            ]);
    }

    public function getNotesProperty(): Collection
    {
        $query = Note::query()
            ->with($this->noteRelations())
            ->when($this->activeFilter === 'text', fn ($q) => $q->ofType('text'))
            ->when($this->activeFilter === 'checklist', fn ($q) => $q->ofType('checklist'))
            ->when($this->activeFilter === 'reminder', fn ($q) => $q->ofType('reminder'))
            ->when($this->activeFilter === 'voice', fn ($q) => $q->ofType('voice'))
            ->when($this->activeFilter === 'pinned', fn ($q) => $q->pinned())
            ->when($this->activeFilter === 'archived', fn ($q) => $q->archived())
            ->when($this->activeFilter === 'all', fn ($q) => $q->notArchived())
            ->when(filled($this->search), fn ($q) => $q->search($this->search));

        $query = match ($this->sortBy) {
            'oldest'       => $query->orderBy('created_at', 'asc'),
            'a-z'          => $query->orderBy('title', 'asc'),
            'reminder'     => $query->orderByRaw('reminder_at IS NULL, reminder_at ASC'),
            'pinned-first' => $query->orderByRaw('is_pinned DESC, created_at DESC'),
            default        => $query->orderByRaw('is_pinned DESC, board_sort ASC, created_at DESC'),
        };

        return $query->get();
    }

    /**
     * @return array<string, Collection<int, Note>>
     */
    public function getBoardNotesProperty(): array
    {
        $grouped = [];

        foreach (NoteBoardStatus::cases() as $status) {
            $grouped[$status->value] = $this->notes
                ->filter(fn (Note $note): bool => NoteBoardStatus::tryFromValue(
                    $note->board_status instanceof NoteBoardStatus
                        ? $note->board_status->value
                        : (string) $note->board_status
                )->value === $status->value)
                ->sortBy([
                    ['is_pinned', 'desc'],
                    ['board_sort', 'asc'],
                    ['created_at', 'desc'],
                ])
                ->values();
        }

        return $grouped;
    }

    public function getPinnedNotesProperty(): Collection
    {
        if ($this->activeFilter === 'archived') {
            return collect();
        }

        return $this->notes->where('is_pinned', true);
    }

    public function getUnpinnedNotesProperty(): Collection
    {
        return $this->notes->where('is_pinned', false);
    }

    public function getCalendarNotesProperty(): Collection
    {
        return $this->notes
            ->filter(fn (Note $note): bool => $note->isReminder() && $note->reminder_at !== null)
            ->sortBy('reminder_at')
            ->groupBy(fn (Note $note): string => $note->reminder_at->toDateString());
    }

    public function createNote(string $type = 'text'): void
    {
        $this->editingNoteUlid = null;
        $this->form->fill($this->defaultFormData(['type' => $type]));
        $this->dispatch('open-modal', id: 'note-slide-over');
    }

    public function editNote(string $ulid): void
    {
        $note = Note::query()->where('ulid', $ulid)->firstOrFail();

        $this->editingNoteUlid = $ulid;
        $this->form->fill([
            'type'                => $note->type,
            'title'               => $note->title,
            'body'                => Note::bodyForRichEditor($note->body),
            'color'               => $note->color,
            'tags'                => $note->tags ?? [],
            'is_pinned'           => $note->is_pinned,
            'reminder_at'         => $note->reminder_at,
            'board_status'        => $note->board_status instanceof NoteBoardStatus
                ? $note->board_status->value
                : (string) ($note->board_status ?? NoteBoardStatus::Inbox->value),
            'meeting_id'          => $note->meeting_id,
            'project_id'          => $note->project_id,
            'correspondence_id'   => $note->correspondence_id,
            'checklist_items'     => $note->checklistItems->map(fn ($item) => [
                'content'    => $item->content,
                'is_checked' => $item->is_checked,
            ])->toArray(),
            'audio_path'          => $note->audio_path,
            'audio_transcription' => $note->audio_transcription,
        ]);
        $this->dispatch('open-modal', id: 'note-slide-over');
    }

    public function saveNote(): void
    {
        $data = $this->form->getState();
        $payload = Note::normalizePayload($data);

        if ($this->editingNoteUlid) {
            $note = Note::query()->where('ulid', $this->editingNoteUlid)->firstOrFail();
            $note->update($payload);
        } else {
            $note = Note::create($payload);
        }

        $note->checklistItems()->delete();

        if ($payload['type'] === 'checklist' && isset($data['checklist_items'])) {
            foreach ($data['checklist_items'] as $index => $item) {
                if (! filled($item['content'] ?? null)) {
                    continue;
                }

                $note->checklistItems()->create([
                    'content'    => str($item['content'])->limit(255, '')->toString(),
                    'is_checked' => $item['is_checked'] ?? false,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->dispatch('close-modal', id: 'note-slide-over');
        $this->editingNoteUlid = null;
        $this->form->fill($this->defaultFormData());

        Notification::make()
            ->success()
            ->title(__('my-notes::notes.notifications.saved'))
            ->send();
    }

    public function deleteNote(string $ulid): void
    {
        Note::query()->where('ulid', $ulid)->firstOrFail()->delete();

        Notification::make()
            ->success()
            ->title(__('my-notes::notes.notifications.deleted'))
            ->send();
    }

    public function moveNoteToBoard(string $ulid, string $status): void
    {
        Note::query()
            ->where('ulid', $ulid)
            ->firstOrFail()
            ->update(['board_status' => NoteBoardStatus::tryFromValue($status)->value]);
    }

    public function togglePin(string $ulid): void
    {
        $note = Note::query()->where('ulid', $ulid)->firstOrFail();
        $note->update(['is_pinned' => ! $note->is_pinned]);
    }

    public function toggleArchive(string $ulid): void
    {
        $note = Note::query()->where('ulid', $ulid)->firstOrFail();
        $note->update(['is_archived' => ! $note->is_archived]);

        Notification::make()
            ->success()
            ->title(
                $note->is_archived
                    ? __('my-notes::notes.notifications.archived')
                    : __('my-notes::notes.notifications.unarchived')
            )
            ->send();
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = NoteChecklistItem::query()
            ->whereHas('note')
            ->findOrFail($itemId);

        $item->update(['is_checked' => ! $item->is_checked]);
    }

    public function applyReminderPreset(string $preset): void
    {
        $this->data['type'] = 'reminder';
        $this->data['reminder_at'] = match ($preset) {
            'hour'   => now()->addHour()->seconds(0),
            'day'    => now()->addDay()->setTime(9, 0),
            'monday' => now()->next('Monday')->setTime(9, 0),
            default  => $this->data['reminder_at'] ?? null,
        };
    }

    public function closeSlideOver(): void
    {
        $this->dispatch('close-modal', id: 'note-slide-over');
        $this->editingNoteUlid = null;
        $this->form->fill($this->defaultFormData());
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make($this->getCreateNoteHeaderActions())
                ->label(__('my-notes::notes.toolbar.new_note'))
                ->icon('heroicon-m-plus')
                ->button()
                ->color('primary'),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getCreateNoteHeaderActions(): array
    {
        $icons = [
            'text'      => 'heroicon-m-document-text',
            'checklist' => 'heroicon-m-check-circle',
            'reminder'  => 'heroicon-m-bell',
            'voice'     => 'heroicon-m-microphone',
        ];

        return collect(Note::TYPES)
            ->map(fn (string $type): Action => Action::make('create_'.$type)
                ->label(__('my-notes::notes.types.'.$type))
                ->icon($icons[$type] ?? 'heroicon-m-document-text')
                ->action(fn (): mixed => $this->createNote($type)))
            ->all();
    }

    protected function defaultFormData(array $overrides = []): array
    {
        return [
            'type'                => 'text',
            'title'               => null,
            'body'                => null,
            'color'               => 'default',
            'board_status'        => NoteBoardStatus::Inbox->value,
            'tags'                => [],
            'is_pinned'           => false,
            'reminder_at'         => null,
            'meeting_id'          => null,
            'project_id'          => null,
            'correspondence_id'   => null,
            'checklist_items'     => [],
            'audio_path'          => null,
            'audio_transcription' => null,
            ...$overrides,
        ];
    }

    protected function noteRelations(): array
    {
        $relations = ['checklistItems', 'user'];

        if (class_exists(Meeting::class)) {
            $relations[] = 'meeting';
        }

        if (class_exists(Project::class)) {
            $relations[] = 'project';
        }

        if (class_exists(Correspondence::class)) {
            $relations[] = 'correspondence';
        }

        return $relations;
    }
}
