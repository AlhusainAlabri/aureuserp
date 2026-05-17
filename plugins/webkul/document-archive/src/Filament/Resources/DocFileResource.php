<?php

namespace Webkul\DocumentArchive\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\CreateDocFile;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\EditDocFile;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\ListDocFiles;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\ViewDocFile;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_number', 'name', 'original_filename'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                        TagsInput::make('tags')
                            ->label(__('document-archive::document-archive.fields.tags')),
                        TextInput::make('tag_color')
                            ->label(__('document-archive::document-archive.fields.tag_color'))
                            ->maxLength(32),
                        Select::make('project_id')
                            ->label(__('document-archive::document-archive.fields.project'))
                            ->options(fn () => static::projectOptions())
                            ->searchable()
                            ->preload(),
                        Select::make('meeting_id')
                            ->label(__('document-archive::document-archive.fields.meeting'))
                            ->options(fn () => static::meetingOptions())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make(__('document-archive::document-archive.form.sections.access'))
                    ->schema([
                        Toggle::make('is_private')
                            ->label(__('document-archive::document-archive.fields.is_private')),
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
                TernaryFilter::make('is_private')
                    ->label(__('document-archive::document-archive.fields.is_private')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('document-archive::document-archive.form.sections.general'))
                    ->schema([
                        TextEntry::make('reference_number')->label(__('document-archive::document-archive.fields.reference_number')),
                        TextEntry::make('name')->label(__('document-archive::document-archive.fields.name')),
                        TextEntry::make('folder.name')->label(__('document-archive::document-archive.fields.folder')),
                        TextEntry::make('extension')->label(__('document-archive::document-archive.fields.extension'))->badge(),
                        TextEntry::make('file_size')
                            ->label(__('document-archive::document-archive.fields.file_size'))
                            ->formatStateUsing(fn (DocFile $record): string => $record->getFileSizeForHumans()),
                        TextEntry::make('mime_type')->label(__('document-archive::document-archive.fields.mime_type')),
                        TextEntry::make('version')->label(__('document-archive::document-archive.fields.version')),
                        TextEntry::make('view_count')->label(__('document-archive::document-archive.fields.view_count')),
                        TextEntry::make('download_count')->label(__('document-archive::document-archive.fields.download_count')),
                        TextEntry::make('expiry_date')->label(__('document-archive::document-archive.fields.expiry_date'))->date()->placeholder('-'),
                        TextEntry::make('description')->label(__('document-archive::document-archive.fields.description'))->columnSpanFull()->placeholder('-'),
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
        if (! class_exists(Project::class)) {
            return [];
        }

        return Project::query()->pluck('name', 'id')->all();
    }

    protected static function meetingOptions(): array
    {
        if (! class_exists(Meeting::class)) {
            return [];
        }

        return Meeting::query()->pluck('title', 'id')->all();
    }
}
