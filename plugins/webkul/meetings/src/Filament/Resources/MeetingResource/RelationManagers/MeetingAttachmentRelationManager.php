<?php

namespace Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Webkul\Meetings\Filament\Resources\MeetingResource\RelationManagers\Concerns\HasMeetingRelationCountBadge;
use Webkul\Meetings\Models\MeetingAttachment;

class MeetingAttachmentRelationManager extends RelationManager
{
    use HasMeetingRelationCountBadge;

    protected static string $relationship = 'attachments';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('meetings::meetings.relations.documents');
    }

    public function form(Schema $schema): Schema
    {
        $maxMegabytes = (int) config('document-archive.max_file_size_mb', 50);

        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label(__('meetings::meetings.fields.file'))
                    ->disk('private')
                    ->directory(fn (): string => 'meetings/'.now()->year)
                    ->acceptedFileTypes(self::acceptedMimeTypes())
                    ->maxSize($maxMegabytes * 1024)
                    ->helperText(__('meetings::meetings.form.attachment_upload_hint', ['max' => $maxMegabytes]))
                    ->required()
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        if (! $state) {
                            return;
                        }

                        $set('file_name', basename($state));
                        $set('file_size', Storage::disk('private')->size($state));
                        $set('mime_type', Storage::disk('private')->mimeType($state));
                    }),
                Hidden::make('file_name')->default('file'),
                Hidden::make('file_size')->default(0),
                Hidden::make('mime_type')->default('application/octet-stream'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label(__('meetings::meetings.fields.file_name'))
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label(__('meetings::meetings.fields.file_size'))
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-'),
                TextColumn::make('mime_type')
                    ->label(__('meetings::meetings.fields.mime_type')),
                TextColumn::make('creator.name')
                    ->label(__('meetings::meetings.fields.creator')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('meetings::meetings.actions.upload_document'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading(__('meetings::meetings.actions.upload_document'))
                    ->visible(fn (): bool => auth()->user()?->can('manageAttachments', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttachments', $this->getOwnerRecord()) ?? false)
                    ->mutateFormDataUsing(fn (array $data): array => self::enrichFileMetadata($data)),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('meetings::meetings.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MeetingAttachment $record): string => Storage::disk('private')->temporaryUrl(
                        $record->file_path,
                        now()->addMinutes(60),
                    ))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('manageAttachments', $this->getOwnerRecord()) ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('manageAttachments', $this->getOwnerRecord()) ?? false),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_attachments'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_attachments_description'));
    }

    /**
     * @return array<int, string>
     */
    protected static function acceptedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/zip',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function enrichFileMetadata(array $data): array
    {
        if (empty($data['file_path'])) {
            return $data;
        }

        $data['file_name'] ??= basename((string) $data['file_path']);
        $data['file_size'] = Storage::disk('private')->size($data['file_path']);
        $data['mime_type'] = Storage::disk('private')->mimeType($data['file_path']);

        return $data;
    }
}
