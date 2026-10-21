<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\RelationManagers;

use App\Support\Media\PrivateMediaUrl;
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
use Webkul\Correspondence\Models\CorrespondenceAttachment;
use Webkul\Correspondence\Services\CorrespondenceAttachmentService;

class CorrespondenceAttachmentRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public static function getTitle($ownerRecord = null, ?string $pageClass = null): string
    {
        return __('correspondence::correspondence.attachments');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label(__('correspondence::correspondence.file'))
                    ->disk('private')
                    ->directory(fn (): string => 'correspondence/'.now()->year)
                    ->visibility('private')
                    ->acceptedFileTypes(CorrespondenceAttachmentService::acceptedMimeTypes())
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
            ->modelLabel(__('correspondence::correspondence.attachment'))
            ->pluralModelLabel(__('correspondence::correspondence.attachment_plural'))
            ->columns([
                TextColumn::make('file_name')
                    ->label(__('correspondence::correspondence.file_name'))
                    ->searchable(),
                TextColumn::make('file_size')
                    ->label(__('correspondence::correspondence.file_size'))
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-'),
                TextColumn::make('mime_type')
                    ->label(__('correspondence::correspondence.mime_type')),
                TextColumn::make('creator.name')
                    ->label(__('correspondence::correspondence.creator')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('correspondence::correspondence.actions.add_attachment'))
                    ->authorize(fn (): bool => auth()->user()?->can('view', $this->getOwnerRecord()) ?? false)
                    ->mutateFormDataUsing(fn (array $data): array => CorrespondenceAttachmentService::enrichFileMetadata($data)),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('correspondence::correspondence.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (CorrespondenceAttachment $record): ?string => PrivateMediaUrl::downloadUrl($record->file_path))
                    ->openUrlInNewTab()
                    ->visible(fn (CorrespondenceAttachment $record): bool => filled(PrivateMediaUrl::downloadUrl($record->file_path))),
                DeleteAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('view', $this->getOwnerRecord()) ?? false),
            ])
            ->emptyStateHeading(__('correspondence::correspondence.empty.no_attachments'))
            ->emptyStateDescription(__('correspondence::correspondence.empty.no_attachments_description'));
    }
}
