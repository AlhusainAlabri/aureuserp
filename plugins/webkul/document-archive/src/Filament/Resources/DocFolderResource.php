<?php

namespace Webkul\DocumentArchive\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages\CreateDocFolder;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages\EditDocFolder;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages\ListDocFolders;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource\Pages\ViewDocFolder;
use Webkul\DocumentArchive\Models\DocFolder;

class DocFolderResource extends Resource
{
    protected static ?string $model = DocFolder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 61;

    protected static ?string $slug = 'document-archive/folders';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('document-archive::document-archive.navigation.folders.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.document-archive');
    }

    public static function getModelLabel(): string
    {
        return __('document-archive::document-archive.models.folder');
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
                        Select::make('parent_id')
                            ->label(__('document-archive::document-archive.fields.parent'))
                            ->options(fn () => DocFolder::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->label(__('document-archive::document-archive.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('document-archive::document-archive.form.sections.metadata'))
                    ->schema([
                        ColorPicker::make('color')
                            ->label(__('document-archive::document-archive.fields.color')),
                        TextInput::make('icon')
                            ->label(__('document-archive::document-archive.fields.icon'))
                            ->placeholder('heroicon-o-folder')
                            ->maxLength(64),
                        TextInput::make('sort_order')
                            ->label(__('document-archive::document-archive.fields.sort_order'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Section::make(__('document-archive::document-archive.form.sections.access'))
                    ->schema([
                        Toggle::make('is_private')
                            ->label(__('document-archive::document-archive.fields.is_private')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('document-archive::document-archive.fields.name'))
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label(__('document-archive::document-archive.fields.parent'))
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('files_count')
                    ->label(__('document-archive::document-archive.fields.files_count'))
                    ->counts('files'),
                IconColumn::make('is_private')
                    ->label(__('document-archive::document-archive.fields.is_private'))
                    ->boolean(),
                TextColumn::make('creator.name')
                    ->label(__('document-archive::document-archive.fields.creator'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('document-archive::document-archive.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_private')
                    ->label(__('document-archive::document-archive.fields.is_private')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('document-archive::document-archive.form.sections.general'))
                    ->schema([
                        TextEntry::make('name')->label(__('document-archive::document-archive.fields.name')),
                        TextEntry::make('parent.name')->label(__('document-archive::document-archive.fields.parent'))->placeholder('-'),
                        TextEntry::make('description')->label(__('document-archive::document-archive.fields.description'))->columnSpanFull()->placeholder('-'),
                        TextEntry::make('files_count')->label(__('document-archive::document-archive.fields.files_count'))
                            ->state(fn (DocFolder $record): int => $record->files()->count()),
                        TextEntry::make('is_private')->label(__('document-archive::document-archive.fields.is_private'))->badge(),
                        TextEntry::make('creator.name')->label(__('document-archive::document-archive.fields.creator')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDocFolders::route('/'),
            'create' => CreateDocFolder::route('/create'),
            'view'   => ViewDocFolder::route('/{record}'),
            'edit'   => EditDocFolder::route('/{record}/edit'),
        ];
    }
}
