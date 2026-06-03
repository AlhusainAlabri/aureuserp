<?php

namespace Webkul\DocumentArchive\Filament\Actions;

use Filament\Actions\Action;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;

class DownloadDocumentAction
{
    public static function make(string $name = 'download'): Action
    {
        return Action::make($name)
            ->label(__('document-archive::document-archive.actions.download'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (DocFile $record): bool => static::hasDownloadAccess($record))
            ->disabled(fn (DocFile $record): bool => PreviewDocumentAction::isFileMissing($record))
            ->tooltip(fn (DocFile $record): ?string => PreviewDocumentAction::isFileMissing($record)
                ? __('document-archive::document-archive.missing_file.title')
                : null)
            ->action(fn (DocFile $record) => redirect()->route('document-archive.download', ['file' => $record->id]));
    }

    public static function hasDownloadAccess(DocFile $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return app(DocumentAccessService::class)->canDownloadFile($user, $record);
    }

    public static function canDownload(DocFile $record): bool
    {
        return static::hasDownloadAccess($record) && ! PreviewDocumentAction::isFileMissing($record);
    }
}
