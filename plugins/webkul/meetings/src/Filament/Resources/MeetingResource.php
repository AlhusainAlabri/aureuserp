<?php

namespace Webkul\Meetings\Filament\Resources;

use App\Filament\Actions\ExportMeetingPdfAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Correspondence\Filament\Resources\MeetingCorrespondencesRelationManager;
use Webkul\Meetings\Filament\Resources\MeetingResource\Pages\CreateMeeting;
use Webkul\Meetings\Filament\Resources\MeetingResource\Pages\EditMeeting;
use Webkul\Meetings\Filament\Resources\MeetingResource\Pages\ListMeetings;
use Webkul\Meetings\Filament\Resources\MeetingResource\Pages\ViewMeeting;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\MeetingAttachmentRelationManager;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\MeetingAttendeeRelationManager;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\MeetingTaskRelationManager;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;
use Webkul\Purchases\Models\PurchaseOrder;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\Infolists\ApprovalStatusSection;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'meetings/meetings';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('meetings::meetings.navigation.meetings');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.meetings');
    }

    public static function getModelLabel(): string
    {
        return __('meetings::meetings.models.meeting');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'meeting_number', 'notes'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('meetings::meetings.form.sections.meeting_data'))
                    ->schema([
                        Placeholder::make('meeting_number')
                            ->label(__('meetings::meetings.fields.meeting_number'))
                            ->content(fn (?Meeting $record): string => $record?->meeting_number ?? __('meetings::meetings.form.auto_generated')),
                        TextInput::make('title')
                            ->label(__('meetings::meetings.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('meetings::meetings.fields.type'))
                            ->options(static::typeOptions())
                            ->required(),
                        DateTimePicker::make('meeting_date')
                            ->label(__('meetings::meetings.fields.meeting_date'))
                            ->native(false)
                            ->seconds(false)
                            ->required(),
                        TextInput::make('duration_minutes')
                            ->label(__('meetings::meetings.fields.duration_minutes'))
                            ->numeric()
                            ->suffix(__('meetings::meetings.units.minutes')),
                        TextInput::make('location')
                            ->label(__('meetings::meetings.fields.location'))
                            ->maxLength(255),
                        Select::make('project_id')
                            ->label(__('meetings::meetings.fields.project'))
                            ->options(fn () => Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('chair_person_id')
                            ->label(__('meetings::meetings.fields.chair_person'))
                            ->options(fn () => User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Set $set, Get $get, ?int $state): void {
                                if (! $state) {
                                    return;
                                }

                                $attendees = collect($get('attendees') ?? []);

                                if ($attendees->contains('user_id', $state)) {
                                    return;
                                }

                                $set('attendees', [
                                    ...$attendees->all(),
                                    [
                                        'user_id'   => $state,
                                        'role'      => 'chair',
                                        'attended'  => false,
                                        'signed_at' => null,
                                    ],
                                ]);
                            }),
                        Select::make('secretary_id')
                            ->label(__('meetings::meetings.fields.secretary'))
                            ->options(fn () => User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make(__('meetings::meetings.form.sections.agenda'))
                    ->schema([
                        RichEditor::make('agenda')
                            ->label(__('meetings::meetings.fields.agenda'))
                            ->extraAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('meetings::meetings.form.sections.attendees'))
                    ->schema([
                        Repeater::make('attendees')
                            ->relationship()
                            ->label(__('meetings::meetings.form.sections.attendees'))
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('meetings::meetings.fields.user'))
                                    ->options(fn () => User::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('role')
                                    ->label(__('meetings::meetings.fields.role'))
                                    ->options(static::roleOptions())
                                    ->required(),
                                Toggle::make('attended')
                                    ->label(__('meetings::meetings.fields.attended')),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ]),

                Section::make(__('meetings::meetings.form.sections.purchase_requests'))
                    ->schema([
                        Repeater::make('tasks')
                            ->relationship()
                            ->label(__('meetings::meetings.form.sections.purchase_requests'))
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('meetings::meetings.fields.task_title'))
                                    ->required(),
                                Select::make('assigned_to')
                                    ->label(__('meetings::meetings.fields.assigned_to'))
                                    ->options(fn () => User::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('purchase_request_id')
                                    ->label(__('meetings::meetings.fields.purchase_request'))
                                    ->options(static::purchaseRequestOptions())
                                    ->searchable()
                                    ->visible(fn (): bool => class_exists(PurchaseOrder::class)),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ])
                    ->visible(fn (): bool => class_exists(PurchaseOrder::class)),

                Section::make(__('meetings::meetings.form.sections.attachments'))
                    ->schema([
                        FileUpload::make('uploads')
                            ->label(__('meetings::meetings.fields.attachments'))
                            ->multiple()
                            ->disk('private')
                            ->directory(fn () => 'meetings/'.now()->year)
                            ->dehydrated(false),
                    ]),

                Section::make(__('meetings::meetings.form.sections.notes'))
                    ->schema([
                        RichEditor::make('notes')
                            ->label(__('meetings::meetings.fields.notes'))
                            ->extraAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_number')
                    ->label(__('meetings::meetings.fields.meeting_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('meetings::meetings.fields.title'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('meetings::meetings.fields.type'))
                    ->formatStateUsing(fn (?string $state): string => static::typeOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => static::typeColor($state)),
                TextColumn::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->formatStateUsing(fn (?string $state): string => static::statusOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (Meeting $record): string => $record->status_color),
                TextColumn::make('meeting_date')
                    ->label(__('meetings::meetings.fields.meeting_date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('chairPerson.name')
                    ->label(__('meetings::meetings.fields.chair_person'))
                    ->sortable(),
                TextColumn::make('attendees_count')
                    ->label(__('meetings::meetings.fields.attendees_count'))
                    ->counts('attendees'),
                TextColumn::make('tasks_count')
                    ->label(__('meetings::meetings.fields.tasks_count'))
                    ->counts('tasks'),
                ApprovalStatusColumn::make(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('meetings::meetings.fields.type'))
                    ->options(static::typeOptions()),
                SelectFilter::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->options(static::statusOptions()),
                SelectFilter::make('project_id')
                    ->label(__('meetings::meetings.fields.project'))
                    ->options(fn () => Project::query()->pluck('name', 'id')),
                SelectFilter::make('chair_person_id')
                    ->label(__('meetings::meetings.fields.chair_person'))
                    ->options(fn () => User::query()->pluck('name', 'id')),
                Filter::make('meeting_date')
                    ->schema([
                        DatePicker::make('from')->label(__('meetings::meetings.filters.from')),
                        DatePicker::make('until')->label(__('meetings::meetings.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('meeting_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('meeting_date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Meeting $record): bool => $record->isDraft()),
                Action::make('archive')
                    ->label(__('meetings::meetings.actions.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (Meeting $record): bool => auth()->user()?->can('archive', $record) ?? false)
                    ->action(fn (Meeting $record): bool => $record->update(['status' => 'archived'])),
                ExportMeetingPdfAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('meeting_date', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('meetings::meetings.infolist.sections.details'))
                    ->schema([
                        TextEntry::make('meeting_number')->label(__('meetings::meetings.fields.meeting_number')),
                        TextEntry::make('title')->label(__('meetings::meetings.fields.title')),
                        TextEntry::make('type')->label(__('meetings::meetings.fields.type'))->formatStateUsing(fn (?string $state): string => static::typeOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('status')->label(__('meetings::meetings.fields.status'))->formatStateUsing(fn (?string $state): string => static::statusOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->dateTime(),
                        TextEntry::make('location')->label(__('meetings::meetings.fields.location'))->placeholder('-'),
                        TextEntry::make('duration_minutes')->label(__('meetings::meetings.fields.duration_minutes'))->suffix(' '.__('meetings::meetings.units.minutes'))->placeholder('-'),
                        TextEntry::make('project.name')->label(__('meetings::meetings.fields.project'))->placeholder('-'),
                        TextEntry::make('chairPerson.name')->label(__('meetings::meetings.fields.chair_person')),
                        TextEntry::make('secretary.name')->label(__('meetings::meetings.fields.secretary'))->placeholder('-'),
                        TextEntry::make('agenda')->label(__('meetings::meetings.fields.agenda'))->html()->columnSpanFull()->placeholder('-'),
                        TextEntry::make('notes')->label(__('meetings::meetings.fields.notes'))->html()->columnSpanFull()->placeholder('-'),
                    ])
                    ->columns(2),
                ApprovalStatusSection::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('meetings::meetings.relations.approvals'), [
                ApprovalsRelationManager::class,
            ]),
            RelationGroup::make(__('meetings::meetings.relations.tasks'), [
                MeetingTaskRelationManager::class,
            ]),
            RelationGroup::make(__('meetings::meetings.relations.attendees'), [
                MeetingAttendeeRelationManager::class,
            ]),
            RelationGroup::make(__('meetings::meetings.relations.attachments'), [
                MeetingAttachmentRelationManager::class,
            ]),
            RelationGroup::make(__('correspondence::correspondence.relations.meeting_correspondences'), [
                MeetingCorrespondencesRelationManager::class,
            ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'view'   => ViewMeeting::route('/{record}'),
            'edit'   => EditMeeting::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return trans('meetings::meetings.types');
    }

    public static function statusOptions(): array
    {
        return trans('meetings::meetings.statuses');
    }

    public static function roleOptions(): array
    {
        return trans('meetings::meetings.roles');
    }

    public static function taskStatusOptions(): array
    {
        return trans('meetings::meetings.task_statuses');
    }

    public static function priorityOptions(): array
    {
        return trans('meetings::meetings.priorities');
    }

    public static function typeColor(?string $type): string
    {
        return match ($type) {
            'internal'  => 'info',
            'external'  => 'purple',
            'emergency' => 'danger',
            'board'     => 'success',
            default     => 'gray',
        };
    }

    public static function purchaseRequestOptions(): array
    {
        if (! class_exists(PurchaseOrder::class)) {
            return [];
        }

        return PurchaseOrder::query()->pluck('name', 'id')->all();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->can('view_any_meetings_meeting')) {
            return $query;
        }

        return $query->whereHas('attendees', fn (Builder $query): Builder => $query->where('user_id', $user->id));
    }
}
