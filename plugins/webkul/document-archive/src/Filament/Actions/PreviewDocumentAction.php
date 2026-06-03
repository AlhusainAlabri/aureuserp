<?php

namespace Webkul\DocumentArchive\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;

class PreviewDocumentAction
{
    public static function make(string $name = 'preview'): Action
    {
        return static::configureAction(
            Action::make($name)
                ->label(__('document-archive::document-archive.actions.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (DocFile $record): bool => static::hasViewAccess($record))
                ->disabled(fn (DocFile $record): bool => static::isFileMissing($record))
                ->tooltip(fn (DocFile $record): ?string => static::isFileMissing($record)
                    ? __('document-archive::document-archive.missing_file.title')
                    : null)
                ->url(fn (DocFile $record): ?string => static::resolvePreviewUrl($record))
                ->openUrlInNewTab(fn (DocFile $record): bool => static::needsPasswordPage($record) && ! static::isFileMissing($record))
                ->before(function (DocFile $record): void {
                    static::recordPreviewView($record);
                })
                ->modalHeading(fn (DocFile $record): string => $record->name)
                ->modalContent(fn (DocFile $record) => view('document-archive::components.preview-modal-content', [
                    'file' => $record,
                ]))
        );
    }

    public static function makeForTable(string $name = 'previewFromName'): Action
    {
        return static::make($name);
    }

    public static function makeForFileId(string $name = 'previewDocument'): Action
    {
        return static::configureAction(
            Action::make($name)
                ->label(__('document-archive::document-archive.actions.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->before(function (array $arguments): void {
                    $file = DocFile::query()->find($arguments['fileId'] ?? null);

                    if ($file) {
                        static::recordPreviewView($file);
                    }
                })
                ->modalHeading(function (array $arguments): string {
                    $file = DocFile::query()->find($arguments['fileId'] ?? null);

                    return $file?->name ?? __('document-archive::document-archive.actions.preview');
                })
                ->modalContent(function (array $arguments) {
                    $file = DocFile::query()->find($arguments['fileId'] ?? null);

                    if (! $file) {
                        return view('document-archive::components.preview-modal-content', [
                            'file' => new DocFile(['name' => '-', 'reference_number' => '-']),
                        ]);
                    }

                    return view('document-archive::components.preview-modal-content', [
                        'file' => $file,
                    ]);
                })
        );
    }

    protected static function configureAction(Action $action): Action
    {
        return $action
            ->modalWidth(Width::SevenExtraLarge)
            ->extraModalWindowAttributes([
                'style' => 'height: 92vh; max-height: 92vh; display: flex; flex-direction: column;',
            ])
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    public static function recordPreviewView(DocFile $record): void
    {
        if (static::isFileMissing($record) || static::needsPasswordPage($record)) {
            return;
        }

        app(DocumentAccessService::class)->recordView($record);
    }

    public static function hasViewAccess(DocFile $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return app(DocumentAccessService::class)->canViewFile($user, $record);
    }

    public static function canPreview(DocFile $record): bool
    {
        return static::hasViewAccess($record) && ! static::isFileMissing($record);
    }

    public static function isFileMissing(DocFile $record): bool
    {
        return ! app(DocumentStorageService::class)->fileExists($record);
    }

    public static function needsPasswordPage(DocFile $record): bool
    {
        $access = app(DocumentAccessService::class);

        return $access->requiresPassword($record) && ! $access->isFileUnlocked($record);
    }

    public static function resolvePreviewUrl(DocFile $record): ?string
    {
        if (static::isFileMissing($record)) {
            return null;
        }

        if (! static::needsPasswordPage($record)) {
            return null;
        }

        return route('document-archive.preview', ['file' => $record->id]);
    }
}
