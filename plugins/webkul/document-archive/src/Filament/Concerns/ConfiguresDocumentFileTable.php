<?php

namespace Webkul\DocumentArchive\Filament\Concerns;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\DocumentArchive\Filament\Actions\DownloadDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\ShareDocumentAction;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Tables\DocumentTagTable;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\DocumentArchive\Support\FilamentUrl;

trait ConfiguresDocumentFileTable
{
    protected function configureDocumentFileTable(Table $table): Table
    {
        $expiringSoonDays = (int) config('document-archive.expiring_soon_days', 7);

        return $table
            ->columns([
                IconColumn::make('missing_on_disk')
                    ->label('')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->tooltip(__('document-archive::document-archive.missing_file.title'))
                    ->visible(fn (?DocFile $record): bool => $record !== null && ! app(DocumentStorageService::class)->fileExists($record)),
                TextColumn::make('reference_number')
                    ->label(__('document-archive::document-archive.fields.reference_number')),
                TextColumn::make('name')
                    ->label(__('document-archive::document-archive.fields.name'))
                    ->searchable()
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->iconColor('gray')
                    ->action(PreviewDocumentAction::makeForTable('previewFromName')),
                DocumentTagTable::tagsColumn(),
                TextColumn::make('folder.name')
                    ->label(__('document-archive::document-archive.fields.folder')),
                TextColumn::make('extension')
                    ->label(__('document-archive::document-archive.fields.extension'))
                    ->badge(),
                TextColumn::make('expiry_date')
                    ->label(__('document-archive::document-archive.fields.expiry_date'))
                    ->date()
                    ->color(function (DocFile $record) use ($expiringSoonDays): ?string {
                        if (! $record->expiry_date) {
                            return null;
                        }

                        if ($record->expiry_date->lte(now()->startOfDay())) {
                            return 'danger';
                        }

                        if ($record->expiry_date->lte(now()->addDays($expiringSoonDays)->startOfDay())) {
                            return 'warning';
                        }

                        return null;
                    }),
                TextColumn::make('file_size')
                    ->label(__('document-archive::document-archive.fields.file_size'))
                    ->formatStateUsing(fn (DocFile $record): string => $record->getFileSizeForHumans()),
                TextColumn::make('created_at')
                    ->label(__('document-archive::document-archive.fields.created_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                PreviewDocumentAction::make()
                    ->button()
                    ->iconButton()
                    ->tooltip(__('document-archive::document-archive.actions.preview')),
                DownloadDocumentAction::make()
                    ->button()
                    ->iconButton()
                    ->tooltip(__('document-archive::document-archive.actions.download')),
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (DocFile $record): string => DocFileResource::getUrl('view', FilamentUrl::withLocale(['record' => $record]))),
                    ShareDocumentAction::make(),
                    EditAction::make()
                        ->url(fn (DocFile $record): string => DocFileResource::getUrl('edit', FilamentUrl::withLocale(['record' => $record]))),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip(__('document-archive::document-archive.manager.actions.more')),
            ])
            ->emptyStateHeading(__('document-archive::document-archive.dashboard.empty.recent_uploads'));
    }

    protected function accessibleFilesQuery(): Builder
    {
        $query = DocFile::query()->with('folder');

        app(DocumentAccessService::class)->applyAccessibleFilesScope($query);

        return $query;
    }
}
