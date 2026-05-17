<?php

namespace Webkul\MyNotes\Filament\Pages;

use BackedEnum;
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
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Webkul\Meetings\Models\Meeting;
use Webkul\MyNotes\Models\Note;
use Webkul\Project\Models\Project;

class MyNotesPage extends Page
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

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

    public bool $showSlideOver = false;

    public ?string $editingNoteUlid = null;

    public bool $showCalendar = false;

    public static function getNavigationLabel(): string
    {
        return __('my-notes::notes.my_notes');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.my-notes');
    }

    public function getTitle(): string|Htmlable
    {
        return __('my-notes::notes.my_notes');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model(Note::class)
            ->statePath('data')
            ->components([
                Select::make('type')
                    ->label(__('my-notes::notes.type'))
                    ->options([
                        'text'      => __('my-notes::notes.text_note'),
                        'checklist' => __('my-notes::notes.checklist'),
                        'reminder'  => __('my-notes::notes.reminder'),
                        'voice'     => __('my-notes::notes.voice_memo'),
                    ])
                    ->default('text')
                    ->live()
                    ->required(),

                TextInput::make('title')
                    ->label(__('my-notes::notes.title'))
                    ->placeholder(__('my-notes::notes.title')),

                Select::make('color')
                    ->label(__('my-notes::notes.color_label'))
                    ->options([
                        'default' => __('my-notes::notes.color.default'),
                        'red'     => __('my-notes::notes.color.red'),
                        'orange'  => __('my-notes::notes.color.orange'),
                        'yellow'  => __('my-notes::notes.color.yellow'),
                        'green'   => __('my-notes::notes.color.green'),
                        'teal'    => __('my-notes::notes.color.teal'),
                        'blue'    => __('my-notes::notes.color.blue'),
                        'purple'  => __('my-notes::notes.color.purple'),
                        'pink'    => __('my-notes::notes.color.pink'),
                        'gray'    => __('my-notes::notes.color.gray'),
                    ])
                    ->default('default')
                    ->required(),

                RichEditor::make('body')
                    ->label(__('my-notes::notes.body'))
                    ->visible(fn (callable $get): bool => in_array($get('type'), ['text', 'reminder']))
                    ->columnSpanFull(),

                Repeater::make('checklist_items')
                    ->label(__('my-notes::notes.checklist'))
                    ->visible(fn (callable $get): bool => $get('type') === 'checklist')
                    ->schema([
                        TextInput::make('content')
                            ->label(__('my-notes::notes.title'))
                            ->required(),
                        Toggle::make('is_checked')
                            ->label(__('my-notes::notes.checklist')),
                    ])
                    ->addActionLabel(__('my-notes::notes.add_item'))
                    ->columnSpanFull(),

                DateTimePicker::make('reminder_at')
                    ->label(__('my-notes::notes.remind_me_on'))
                    ->visible(fn (callable $get): bool => $get('type') === 'reminder')
                    ->native(false)
                    ->seconds(false),

                FileUpload::make('audio_path')
                    ->label(__('my-notes::notes.voice_memo'))
                    ->visible(fn (callable $get): bool => $get('type') === 'voice')
                    ->disk('local')
                    ->directory(fn () => 'notes/voice/'.Auth::id())
                    ->acceptedFileTypes(['audio/webm', 'audio/mp3', 'audio/wav', 'audio/mpeg'])
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Textarea::make('audio_transcription')
                    ->label(__('my-notes::notes.transcription'))
                    ->visible(fn (callable $get): bool => $get('type') === 'voice')
                    ->columnSpanFull(),

                TagsInput::make('tags')
                    ->label(__('my-notes::notes.tags'))
                    ->placeholder(__('my-notes::notes.tags')),

                Select::make('meeting_id')
                    ->label(__('my-notes::notes.link_to'))
                    ->options(function (): array {
                        if (! class_exists(Meeting::class)) {
                            return [];
                        }

                        return Meeting::query()
                            ->pluck('title', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->visible(fn (): bool => class_exists(Meeting::class))
                    ->nullable(),

                Select::make('project_id')
                    ->label(__('my-notes::notes.link_to'))
                    ->options(function (): array {
                        if (! class_exists(Project::class)) {
                            return [];
                        }

                        return Project::query()
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->visible(fn (): bool => class_exists(Project::class))
                    ->nullable(),

                Toggle::make('is_pinned')
                    ->label(__('my-notes::notes.pin')),
            ]);
    }

    public function getNotesProperty()
    {
        $query = Note::query()
            ->with('checklistItems')
            ->when($this->activeFilter === 'text', fn ($q) => $q->ofType('text'))
            ->when($this->activeFilter === 'checklist', fn ($q) => $q->ofType('checklist'))
            ->when($this->activeFilter === 'reminder', fn ($q) => $q->ofType('reminder'))
            ->when($this->activeFilter === 'voice', fn ($q) => $q->ofType('voice'))
            ->when($this->activeFilter === 'pinned', fn ($q) => $q->pinned())
            ->when($this->activeFilter === 'archived', fn ($q) => $q->archived())
            ->when($this->activeFilter === 'all', fn ($q) => $q->notArchived())
            ->when(filled($this->search), fn ($q) => $q->search($this->search));

        return match ($this->sortBy) {
            'oldest'      => $query->orderBy('created_at', 'asc'),
            'a-z'         => $query->orderBy('title', 'asc'),
            'pinned-first'=> $query->orderByRaw('is_pinned DESC, created_at DESC'),
            default       => $query->orderByRaw('is_pinned DESC, created_at DESC'),
        };
    }

    public function getPinnedNotesProperty()
    {
        if ($this->activeFilter === 'archived') {
            return collect();
        }

        return $this->getNotesProperty()->get()->where('is_pinned', true);
    }

    public function getUnpinnedNotesProperty()
    {
        return $this->getNotesProperty()->get()->where('is_pinned', false);
    }

    public function createNote(string $type = 'text'): void
    {
        $this->editingNoteUlid = null;
        $this->form->fill([
            'type'      => $type,
            'color'     => 'default',
            'is_pinned' => false,
            'tags'      => [],
        ]);
        $this->showSlideOver = true;
    }

    public function editNote(string $ulid): void
    {
        $note = Note::query()->where('ulid', $ulid)->firstOrFail();

        $this->editingNoteUlid = $ulid;
        $this->form->fill([
            'type'            => $note->type,
            'title'           => $note->title,
            'body'            => $note->body,
            'color'           => $note->color,
            'tags'            => $note->tags ?? [],
            'is_pinned'       => $note->is_pinned,
            'reminder_at'     => $note->reminder_at,
            'meeting_id'      => $note->meeting_id,
            'project_id'      => $note->project_id,
            'checklist_items' => $note->checklistItems->map(fn ($item) => [
                'content'    => $item->content,
                'is_checked' => $item->is_checked,
            ])->toArray(),
            'audio_path'         => $note->audio_path,
            'audio_transcription'=> $note->audio_transcription,
        ]);
        $this->showSlideOver = true;
    }

    public function saveNote(): void
    {
        $data = $this->form->getState();

        if ($this->editingNoteUlid) {
            $note = Note::query()->where('ulid', $this->editingNoteUlid)->firstOrFail();
            $note->update([
                'type'                 => $data['type'],
                'title'                => $data['title'] ?? null,
                'body'                 => $data['body'] ?? null,
                'color'                => $data['color'],
                'tags'                 => $data['tags'] ?? null,
                'is_pinned'            => $data['is_pinned'] ?? false,
                'reminder_at'          => $data['reminder_at'] ?? null,
                'meeting_id'           => $data['meeting_id'] ?? null,
                'project_id'           => $data['project_id'] ?? null,
                'audio_path'           => $data['audio_path'] ?? null,
                'audio_transcription'  => $data['audio_transcription'] ?? null,
            ]);
        } else {
            $note = Note::create([
                'type'                => $data['type'],
                'title'               => $data['title'] ?? null,
                'body'                => $data['body'] ?? null,
                'color'               => $data['color'],
                'tags'                => $data['tags'] ?? null,
                'is_pinned'           => $data['is_pinned'] ?? false,
                'reminder_at'         => $data['reminder_at'] ?? null,
                'meeting_id'          => $data['meeting_id'] ?? null,
                'project_id'          => $data['project_id'] ?? null,
                'audio_path'          => $data['audio_path'] ?? null,
                'audio_transcription' => $data['audio_transcription'] ?? null,
            ]);
        }

        if ($data['type'] === 'checklist' && isset($data['checklist_items'])) {
            $note->checklistItems()->delete();
            foreach ($data['checklist_items'] as $index => $item) {
                $note->checklistItems()->create([
                    'content'    => $item['content'],
                    'is_checked' => $item['is_checked'] ?? false,
                    'sort_order' => $index,
                ]);
            }
        }

        $this->showSlideOver = false;
        $this->form->fill();

        Notification::make()
            ->success()
            ->title(__('my-notes::notes.saved'))
            ->send();
    }

    public function deleteNote(string $ulid): void
    {
        $note = Note::query()->where('ulid', $ulid)->firstOrFail();
        $note->delete();

        Notification::make()
            ->success()
            ->title(__('my-notes::notes.delete'))
            ->send();
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
    }

    public function closeSlideOver(): void
    {
        $this->showSlideOver = false;
        $this->editingNoteUlid = null;
        $this->form->fill();
    }

    public function getHeaderActions(): array
    {
        return [];
    }
}
