<?php

namespace Webkul\Correspondence\Filament\Resources;

use App\Filament\Actions\ExportCorrespondencePdfAction;
use App\Filament\Infolists\ApprovalStatusSection;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\CreateCorrespondence;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\EditCorrespondence;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\ListCorrespondences;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\ViewCorrespondence;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers\CorrespondenceAttachmentRelationManager;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers\CorrespondenceFollowerRelationManager;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers\CorrespondenceTasksRelationManager;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers\CorrespondenceThreadRelationManager;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Models\Department;
use Webkul\Correspondence\Services\CorrespondenceAttachmentService;
use Webkul\Correspondence\Services\CorrespondenceTaskService;
use Webkul\Correspondence\Services\CorrespondenceVisibilityService;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;
use Webkul\Purchases\Models\PurchaseOrder;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\Columns\ApprovalStatusColumn;
use Wezlo\FilamentApproval\RelationManagers\ApprovalsRelationManager;

class CorrespondenceResource extends Resource
{
    protected static ?string $model = Correspondence::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 55;

    protected static ?string $slug = 'correspondence/correspondences';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationLabel(): string
    {
        return __('correspondence::correspondence.correspondences');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.correspondence');
    }

    public static function getModelLabel(): string
    {
        return __('correspondence::correspondence.correspondence');
    }

    public static function getPluralModelLabel(): string
    {
        return __('correspondence::correspondence.correspondences');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'subject', 'sender_name', 'body'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('correspondence::correspondence.form.sections.type'))
                    ->schema([
                        Radio::make('direction')
                            ->label(__('correspondence::correspondence.direction'))
                            ->options(static::directionOptions())
                            ->inline()
                            ->live()
                            ->default('outgoing')
                            ->required(),
                        Select::make('type')
                            ->label(__('correspondence::correspondence.type.label'))
                            ->options(static::typeOptions())
                            ->live()
                            ->default('official')
                            ->required(),
                        Select::make('priority')
                            ->label(__('correspondence::correspondence.priority.label'))
                            ->options(static::priorityOptions())
                            ->default('normal')
                            ->required(),
                        TextInput::make('subject')
                            ->label(__('correspondence::correspondence.subject'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('correspondence::correspondence.form.sections.parties'))
                    ->schema([
                        Select::make('from_department_id')
                            ->label(__('correspondence::correspondence.from_department'))
                            ->options(fn () => Department::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('direction') === 'outgoing'),
                        Select::make('to_department_id')
                            ->label(__('correspondence::correspondence.to_department'))
                            ->options(fn () => Department::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('to_user_id')
                            ->label(fn (Get $get): string => $get('direction') === 'incoming'
                                ? __('correspondence::correspondence.recipient')
                                : __('correspondence::correspondence.to_user'))
                            ->options(fn () => User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('type') !== 'external' || $get('direction') === 'incoming'),
                        TextInput::make('to_external_email')
                            ->label(__('correspondence::correspondence.to_external_email'))
                            ->email()
                            ->visible(fn (Get $get): bool => $get('direction') === 'outgoing' && $get('type') === 'external'),
                        TextInput::make('sender_name')
                            ->label(__('correspondence::correspondence.sender_name'))
                            ->visible(fn (Get $get): bool => $get('direction') === 'incoming'),
                        TextInput::make('sender_entity')
                            ->label(fn (Get $get): string => $get('direction') === 'incoming'
                                ? __('correspondence::correspondence.sender_entity')
                                : __('correspondence::correspondence.external_entity'))
                            ->visible(fn (Get $get): bool => $get('direction') === 'incoming' || $get('type') === 'external'),
                        DatePicker::make('received_at')
                            ->label(__('correspondence::correspondence.received_at'))
                            ->native(false)
                            ->default(now())
                            ->visible(fn (Get $get): bool => $get('direction') === 'incoming'),
                    ])
                    ->columns(2),
                Section::make(__('correspondence::correspondence.form.sections.content'))
                    ->schema([
                        RichEditor::make('body')
                            ->label(__('correspondence::correspondence.body'))
                            ->extraAttributes(['dir' => 'rtl'])
                            ->columnSpanFull(),
                        DatePicker::make('due_date')
                            ->label(__('correspondence::correspondence.due_date'))
                            ->native(false),
                    ]),
                Section::make(__('correspondence::correspondence.form.sections.links'))
                    ->schema([
                        Select::make('project_id')
                            ->label(__('correspondence::correspondence.project'))
                            ->options(fn () => Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('meeting_id')
                            ->label(__('correspondence::correspondence.meeting'))
                            ->options(fn () => Meeting::query()->pluck('title', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('purchase_request_id')
                            ->label(__('correspondence::correspondence.purchase_request'))
                            ->options(static::purchaseRequestOptions())
                            ->searchable()
                            ->visible(fn (): bool => class_exists(PurchaseOrder::class)),
                        Hidden::make('parent_id'),
                    ])
                    ->columns(2),
                Section::make(__('correspondence::correspondence.attachments'))
                    ->schema([
                        FileUpload::make('uploads')
                            ->label(__('correspondence::correspondence.attachments'))
                            ->multiple()
                            ->disk('private')
                            ->directory(fn (): string => 'correspondence/'.now()->year)
                            ->visibility('private')
                            ->acceptedFileTypes(CorrespondenceAttachmentService::acceptedMimeTypes())
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('correspondence::correspondence.reference_number'))
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('correspondence::correspondence.subject'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('correspondence::correspondence.type.label'))
                    ->formatStateUsing(fn (?string $state): string => static::typeOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('priority')
                    ->label(__('correspondence::correspondence.priority.label'))
                    ->formatStateUsing(fn (?string $state): string => static::priorityOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => static::priorityColor($state)),
                TextColumn::make('status')
                    ->label(__('correspondence::correspondence.status.label'))
                    ->formatStateUsing(fn (?string $state): string => static::statusOptions()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (Correspondence $record): string => $record->status_color),
                TextColumn::make('direction')
                    ->label(__('correspondence::correspondence.direction'))
                    ->formatStateUsing(fn (?string $state): string => static::directionOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('fromDepartment.name')
                    ->label(__('correspondence::correspondence.from_department'))
                    ->placeholder('-'),
                TextColumn::make('toDepartment.name')
                    ->label(__('correspondence::correspondence.to_department'))
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('correspondence::correspondence.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label(__('correspondence::correspondence.received_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('due_date')
                    ->label(__('correspondence::correspondence.due_date'))
                    ->date()
                    ->color(fn (Correspondence $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                IconColumn::make('has_replies')
                    ->label(__('correspondence::correspondence.thread'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->state(fn (Correspondence $record): bool => $record->hasReplies())
                    ->boolean(),
                ApprovalStatusColumn::make(),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('correspondence::correspondence.type.label'))->options(static::typeOptions()),
                SelectFilter::make('priority')->label(__('correspondence::correspondence.priority.label'))->options(static::priorityOptions()),
                SelectFilter::make('status')->label(__('correspondence::correspondence.status.label'))->options(static::statusOptions()),
                SelectFilter::make('from_department_id')->label(__('correspondence::correspondence.from_department'))->options(fn () => Department::query()->pluck('name', 'id')),
                SelectFilter::make('project_id')->label(__('correspondence::correspondence.project'))->options(fn () => Project::query()->pluck('name', 'id')),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label(__('correspondence::correspondence.filters.from')),
                        DatePicker::make('until')->label(__('correspondence::correspondence.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                Filter::make('received_date_range')
                    ->label(__('correspondence::correspondence.received_at'))
                    ->schema([
                        DatePicker::make('received_from')->label(__('correspondence::correspondence.filters.received_from')),
                        DatePicker::make('received_until')->label(__('correspondence::correspondence.filters.received_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['received_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('received_at', '>=', $date))
                        ->when($data['received_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('received_at', '<=', $date))),
                Filter::make('due_date_range')
                    ->label(__('correspondence::correspondence.due_date'))
                    ->schema([
                        DatePicker::make('due_from')->label(__('correspondence::correspondence.filters.due_from')),
                        DatePicker::make('due_until')->label(__('correspondence::correspondence.filters.due_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['due_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '>=', $date))
                        ->when($data['due_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '<=', $date))),
                Filter::make('overdue')->label(__('correspondence::correspondence.overdue'))->query(fn (Builder $query): Builder => $query->overdue()),
                Filter::make('has_replies')->label(__('correspondence::correspondence.thread'))->query(fn (Builder $query): Builder => $query->has('replies')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Correspondence $record): bool => $record->status === 'draft'),
                Action::make('reply')
                    ->label(__('correspondence::correspondence.reply'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(fn (Correspondence $record): string => static::getUrl('create', ['reply_to' => $record->id])),
                Action::make('archive')
                    ->label(__('correspondence::correspondence.actions.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (Correspondence $record): bool => auth()->user()?->can('archive', $record) ?? false)
                    ->action(fn (Correspondence $record): bool => $record->update(['status' => 'archived'])),
                Action::make('unarchive')
                    ->label(__('correspondence::correspondence.actions.unarchive'))
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->visible(fn (Correspondence $record): bool => $record->status === 'archived'
                        && (auth()->user()?->can('archive', $record) ?? false))
                    ->action(fn (Correspondence $record): bool => $record->unarchive()),
                ExportCorrespondencePdfAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('correspondence::correspondence.details'))
                    ->schema([
                        TextEntry::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
                        TextEntry::make('subject')->label(__('correspondence::correspondence.subject')),
                        TextEntry::make('direction')->label(__('correspondence::correspondence.direction'))->formatStateUsing(fn (?string $state): string => static::directionOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('type')->label(__('correspondence::correspondence.type.label'))->formatStateUsing(fn (?string $state): string => static::typeOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('priority')->label(__('correspondence::correspondence.priority.label'))->formatStateUsing(fn (?string $state): string => static::priorityOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('status')->label(__('correspondence::correspondence.status.label'))->formatStateUsing(fn (?string $state): string => static::statusOptions()[$state] ?? (string) $state)->badge(),
                        TextEntry::make('sender_name')->label(__('correspondence::correspondence.sender_name'))->placeholder('-'),
                        TextEntry::make('sender_entity')->label(__('correspondence::correspondence.sender_entity'))->placeholder('-'),
                        TextEntry::make('fromDepartment.name')->label(__('correspondence::correspondence.from_department'))->placeholder('-'),
                        TextEntry::make('toDepartment.name')->label(__('correspondence::correspondence.to_department'))->placeholder('-'),
                        TextEntry::make('toUser.name')->label(__('correspondence::correspondence.to_user'))->placeholder('-'),
                        TextEntry::make('body')->label(__('correspondence::correspondence.body'))->html()->columnSpanFull()->placeholder('-'),
                    ])
                    ->columns(2),
                ApprovalStatusSection::make(),
            ]);
    }

    public static function getRelations(): array
    {
        $relations = [
            RelationGroup::make(__('correspondence::correspondence.relations.approvals'), [
                ApprovalsRelationManager::class,
            ]),
            RelationGroup::make(__('correspondence::correspondence.thread'), [
                CorrespondenceThreadRelationManager::class,
            ]),
            RelationGroup::make(__('correspondence::correspondence.attachments'), [
                CorrespondenceAttachmentRelationManager::class,
            ]),
        ];

        if (CorrespondenceTaskService::isAvailable()) {
            $relations[] = RelationGroup::make(__('correspondence::correspondence.tasks.navigation'), [
                CorrespondenceTasksRelationManager::class,
            ]);
        }

        $relations[] = RelationGroup::make(__('correspondence::correspondence.followers'), [
            CorrespondenceFollowerRelationManager::class,
        ]);

        return $relations;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCorrespondences::route('/'),
            'create' => CreateCorrespondence::route('/create'),
            'view'   => ViewCorrespondence::route('/{record}'),
            'edit'   => EditCorrespondence::route('/{record}/edit'),
        ];
    }

    public static function directionOptions(): array
    {
        return trans('correspondence::correspondence.directions');
    }

    public static function typeOptions(): array
    {
        return trans('correspondence::correspondence.types');
    }

    public static function priorityOptions(): array
    {
        return trans('correspondence::correspondence.priorities');
    }

    public static function statusOptions(): array
    {
        return trans('correspondence::correspondence.statuses');
    }

    public static function priorityColor(?string $priority): string
    {
        return match ($priority) {
            'urgent'       => 'danger',
            'confidential' => 'warning',
            default        => 'gray',
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

        if (! $user || $user->can('view_all_departments_correspondence_correspondence')) {
            return $query;
        }

        return CorrespondenceVisibilityService::applyDepartmentScope($query, $user);
    }
}
