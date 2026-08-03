<?php

namespace Webkul\DocumentArchive\Filament\Forms;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;

class DocumentFileQuickCreateForm
{
    /**
     * @return array<int, Component>
     */
    public static function uploadTabs(?int $defaultFolderId = null): array
    {
        return [
            Tabs::make('upload_tabs')
                ->tabs([
                    Tab::make(__('document-archive::document-archive.form.sections.file'))
                        ->schema(static::fileTab($defaultFolderId)),
                    Tab::make(__('document-archive::document-archive.form.sections.metadata'))
                        ->schema(static::detailsTab()),
                    Tab::make(__('document-archive::document-archive.form.sections.access'))
                        ->schema(static::accessTab()),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function fileTab(?int $defaultFolderId = null): array
    {
        $storage = app(DocumentStorageService::class);

        return [
            FileUpload::make('upload')
                ->label(__('document-archive::document-archive.fields.file'))
                ->disk($storage->disk())
                ->directory(fn (): string => $storage->temporaryDirectory())
                ->acceptedFileTypes($storage->acceptedMimeTypes())
                ->maxSize($storage->maxUploadSizeKilobytes())
                ->required()
                ->columnSpanFull(),
            TextInput::make('name')
                ->label(__('document-archive::document-archive.fields.name'))
                ->maxLength(255),
            Select::make('folder_id')
                ->label(__('document-archive::document-archive.fields.folder'))
                ->options(fn (): array => DocFolder::query()->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->default($defaultFolderId)
                ->required(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function detailsTab(): array
    {
        return [
            Textarea::make('description')
                ->label(__('document-archive::document-archive.fields.description'))
                ->columnSpanFull(),
            Grid::make(2)->schema([
                ...DocumentTagForm::metadataSchema(),
                Select::make('project_id')
                    ->label(__('document-archive::document-archive.fields.project'))
                    ->options(fn (): array => static::projectOptions())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => static::projectOptions() !== [])
                    ->columnSpan(1),
                Select::make('meeting_id')
                    ->label(__('document-archive::document-archive.fields.meeting'))
                    ->options(fn (): array => static::meetingOptions())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => static::meetingOptions() !== [])
                    ->columnSpan(1),
                Select::make('correspondence_id')
                    ->label(__('document-archive::document-archive.fields.correspondence'))
                    ->options(fn (): array => static::correspondenceOptions())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => static::correspondenceOptions() !== [])
                    ->columnSpan(1),
            ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function accessTab(): array
    {
        return [
            Toggle::make('is_private')
                ->label(__('document-archive::document-archive.fields.is_private'))
                ->helperText(__('document-archive::document-archive.form.access.private_help')),
            TextInput::make('password')
                ->label(__('document-archive::document-archive.fields.password'))
                ->password()
                ->revealable()
                ->dehydrated(false)
                ->helperText(__('document-archive::document-archive.form.access.password_help')),
            DatePicker::make('expiry_date')
                ->label(__('document-archive::document-archive.fields.expiry_date'))
                ->native(false),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    protected static function projectOptions(): array
    {
        if (! class_exists(Project::class) || ! DbSchema::hasTable('projects_projects')) {
            return [];
        }

        return Project::query()->pluck('name', 'id')->all();
    }

    /**
     * @return array<int|string, string>
     */
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
        if (! class_exists(Correspondence::class) || ! DbSchema::hasTable('correspondences')) {
            return [];
        }

        return Correspondence::query()->pluck('subject', 'id')->all();
    }
}
