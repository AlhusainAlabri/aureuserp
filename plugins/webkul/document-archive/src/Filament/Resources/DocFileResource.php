<?php

namespace Webkul\DocumentArchive\Filament\Resources;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\DocumentArchive\Filament\Actions\DownloadDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Forms\DocumentTagForm;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\CreateDocFile;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\EditDocFile;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\ListDocFiles;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\ViewDocFile;
use Webkul\DocumentArchive\Filament\Tables\DocumentTagTable;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;

class DocFileResource extends Resource
{
    protected static ?string $model = DocFile::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document';

    protected static ?int $navigationSort = 62;

    protected static ?string $slug = 'document-archive/files';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('document-archive::document-archive.navigation.files.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.document-archive');
    }

    public static function getModelLabel(): string
    {
        return __('document-archive::document-archive.models.file');
    }

    public static function getPluralModelLabel(): string
    {
        return __('document-archive::document-archive.navigation.files.label');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'name', 'original_filename'];
    }

    public static function form(Schema $schema): Schema
    {
        $storage = app(DocumentStorageService::class);

        return $schema
            ->components([
                Section::make(__('document-archive::document-archive.form.sections.file'))
                    ->description(__('document-archive::document-archive.form.file.create_help'))
                    ->schema([
                        FileUpload::make('upload')
                            ->label(__('document-archive::document-archive.fields.file'))
                            ->disk($storage->disk())
                            ->directory(fn (): string => $storage->temporaryDirectory())
                            ->acceptedFileTypes($storage->acceptedMimeTypes())
                            ->maxSize($storage->maxUploadSizeKilobytes())
                            ->required()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull(),

                Section::make(__('document-archive::document-archive.form.sections.file'))
                    ->description(__('document-archive::document-archive.form.file.replace_help'))
                    ->schema([
                        Placeholder::make('current_file_summary')
                            ->label(__('document-archive::document-archive.form.file.current'))
                            ->content(fn (?DocFile $record) => view('document-archive::components.document-current-file', [
                                'record' => $record,
                            ]))
                            ->columnSpanFull(),
                        FileUpload::make('upload')
                            ->label(__('document-archive::document-archive.form.file.replace_label'))
                            ->helperText(__('document-archive::document-archive.form.file.replace_upload_help'))
                            ->disk($storage->disk())
                            ->directory(fn (): string => $storage->temporaryDirectory())
                            ->acceptedFileTypes($storage->acceptedMimeTypes())
                            ->maxSize($storage->maxUploadSizeKilobytes())
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columnSpanFull(),

                Section::make(__('document-archive::document-archive.form.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('document-archive::document-archive.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('folder_id')
                            ->label(__('document-archive::document-archive.fields.folder'))
                            ->options(fn () => DocFolder::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('description')
                            ->label(__('document-archive::document-archive.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('document-archive::document-archive.form.sections.metadata'))
                    ->schema([
                        Grid::make(2)->schema([
                            ...DocumentTagForm::metadataSchema(),
                            Select::make('project_id')
                                ->label(__('document-archive::document-archive.fields.project'))
                                ->options(fn () => static::projectOptions())
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
                            Select::make('meeting_id')
                                ->label(__('document-archive::document-archive.fields.meeting'))
                                ->options(fn () => static::meetingOptions())
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
                            Select::make('correspondence_id')
                                ->label(__('document-archive::document-archive.fields.correspondence'))
                                ->options(fn () => static::correspondenceOptions())
                                ->searchable()
                                ->preload()
                                ->visible(fn (): bool => static::correspondenceOptions() !== [])
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make(__('document-archive::document-archive.form.sections.access'))
                    ->schema([
                        Toggle::make('is_private')
                            ->label(__('document-archive::document-archive.fields.is_private'))
                            ->helperText(__('document-archive::document-archive.form.access.private_help')),
                        Placeholder::make('password_status')
                            ->label(__('document-archive::document-archive.form.access.password_status'))
                            ->content(fn (?DocFile $record): string => $record?->isPasswordProtected()
                                ? __('document-archive::document-archive.form.access.password_enabled')
                                : __('document-archive::document-archive.form.access.password_disabled'))
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                        TextInput::make('password')
                            ->label(__('document-archive::document-archive.fields.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->helperText(__('document-archive::document-archive.form.access.password_help')),
                        Toggle::make('remove_password')
                            ->label(__('document-archive::document-archive.fields.remove_password'))
                            ->dehydrated(false)
                            ->visible(fn (string $operation, ?DocFile $record): bool => $operation === 'edit' && ($record?->hasPassword() ?? false)),
                    ])
                    ->columns(2),

                Section::make(__('document-archive::document-archive.form.sections.lifecycle'))
                    ->schema([
                        DatePicker::make('expiry_date')
                            ->label(__('document-archive::document-archive.fields.expiry_date'))
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('document-archive::document-archive.fields.reference_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('document-archive::document-archive.fields.name'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                DocumentTagTable::tagsColumn(),
                TextColumn::make('extension')
                    ->label(__('document-archive::document-archive.fields.extension'))
                    ->badge(),
                TextColumn::make('file_size')
                    ->label(__('document-archive::document-archive.fields.file_size'))
                    ->formatStateUsing(fn (DocFile $record): string => $record->getFileSizeForHumans())
                    ->sortable(),
                TextColumn::make('folder.name')
                    ->label(__('document-archive::document-archive.fields.folder'))
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label(__('document-archive::document-archive.fields.expiry_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('document-archive::document-archive.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('folder_id')
                    ->label(__('document-archive::document-archive.fields.folder'))
                    ->options(fn () => DocFolder::query()->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('extension')
                    ->label(__('document-archive::document-archive.fields.extension'))
                    ->options(fn () => DocFile::query()->distinct()->pluck('extension', 'extension')->filter()->all()),
                DocumentTagTable::tagsFilter(),
                SelectFilter::make('project_id')
                    ->label(__('document-archive::document-archive.fields.project'))
                    ->options(fn () => static::projectOptions())
                    ->visible(fn (): bool => static::projectOptions() !== []),
                TernaryFilter::make('is_private')
                    ->label(__('document-archive::document-archive.fields.is_private')),
                Filter::make('expiring_soon')
                    ->label(__('document-archive::document-archive.table.tabs.expiring_soon'))
                    ->query(fn (Builder $query): Builder => $query->expiringSoon()),
                Filter::make('expired')
                    ->label(__('document-archive::document-archive.table.tabs.expired'))
                    ->query(fn (Builder $query): Builder => $query->expired()),
            ])
            ->recordActions([
                ActionGroup::make([
                    PreviewDocumentAction::make(),
                    DownloadDocumentAction::make(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('expiry_alert')
                    ->hiddenLabel()
                    ->view('document-archive::components.document-expiry-alert')
                    ->visible(fn (DocFile $record): bool => $record->getExpiryStatus() !== null)
                    ->columnSpanFull(),

                Section::make(__('document-archive::document-archive.form.sections.general'))
                    ->schema([
                        TextEntry::make('reference_number')->label(__('document-archive::document-archive.fields.reference_number')),
                        TextEntry::make('name')->label(__('document-archive::document-archive.fields.name')),
                        TextEntry::make('folder.name')->label(__('document-archive::document-archive.fields.folder')),
                        ViewEntry::make('tags_display')
                            ->label(__('document-archive::document-archive.fields.tags'))
                            ->view('document-archive::components.document-tags-entry')
                            ->columnSpanFull(),
                        TextEntry::make('extension')->label(__('document-archive::document-archive.fields.extension'))->badge(),
                        TextEntry::make('file_size')
                            ->label(__('document-archive::document-archive.fields.file_size'))
                            ->formatStateUsing(fn (DocFile $record): string => $record->getFileSizeForHumans()),
                        TextEntry::make('mime_type')->label(__('document-archive::document-archive.fields.mime_type')),
                        TextEntry::make('version')->label(__('document-archive::document-archive.fields.version')),
                        TextEntry::make('view_count')->label(__('document-archive::document-archive.fields.view_count')),
                        TextEntry::make('download_count')->label(__('document-archive::document-archive.fields.download_count')),
                        TextEntry::make('expiry_date')
                            ->label(__('document-archive::document-archive.fields.expiry_date'))
                            ->date()
                            ->placeholder('-')
                            ->color(fn (DocFile $record): ?string => match ($record->getExpiryStatus()) {
                                'expired'       => 'danger',
                                'expiring_soon' => 'warning',
                                default         => null,
                            }),
                        TextEntry::make('description')->label(__('document-archive::document-archive.fields.description'))->columnSpanFull()->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('document-archive::document-archive.form.sections.access'))
                    ->schema([
                        TextEntry::make('is_private')
                            ->label(__('document-archive::document-archive.fields.is_private'))
                            ->formatStateUsing(fn (bool $state): string => $state
                                ? __('document-archive::document-archive.form.access.yes')
                                : __('document-archive::document-archive.form.access.no'))
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                        TextEntry::make('password_protected')
                            ->label(__('document-archive::document-archive.form.access.password_status'))
                            ->state(fn (DocFile $record): string => $record->isPasswordProtected()
                                ? __('document-archive::document-archive.form.access.password_enabled')
                                : __('document-archive::document-archive.form.access.password_disabled'))
                            ->badge()
                            ->color(fn (DocFile $record): string => $record->isPasswordProtected() ? 'warning' : 'gray'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDocFiles::route('/'),
            'create' => CreateDocFile::route('/create'),
            'view'   => ViewDocFile::route('/{record}'),
            'edit'   => EditDocFile::route('/{record}/edit'),
        ];
    }

    protected static function projectOptions(): array
    {
        if (! class_exists(Project::class) || ! DbSchema::hasTable('projects_projects')) {
            return [];
        }

        return Project::query()->pluck('name', 'id')->all();
    }

    protected static function meetingOptions(): array
    {
        if (! class_exists(Meeting::class) || ! DbSchema::hasTable('meetings')) {
            return [];
        }

        return Meeting::query()->pluck('title', 'id')->all();
    }

    /**
     * @return array<int|string, string>
     */
    protected static function correspondenceOptions(): array
    {
        if (! class_exists(Correspondence::class)) {
            return [];
        }

        if (! DbSchema::hasTable('correspondences')) {
            return [];
        }

        return Correspondence::query()
            ->pluck('subject', 'id')
            ->all();
    }
}
