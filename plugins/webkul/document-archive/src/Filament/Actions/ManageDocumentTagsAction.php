<?php

namespace Webkul\DocumentArchive\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\DocumentArchive\Filament\Forms\DocumentTagForm;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentTagService;

class ManageDocumentTagsAction
{
    public static function make(string $name = 'manageTags'): Action
    {
        return Action::make($name)
            ->label(__('document-archive::document-archive.tags.manage'))
            ->icon('heroicon-o-tag')
            ->color(fn (DocFile $record): string => $record->getTagsWithColors() === [] ? 'primary' : 'gray')
            ->schema(DocumentTagForm::schema(includeAdvanced: false))
            ->fillForm(fn (DocFile $record): array => [
                'tag_names' => collect($record->getTagsWithColors())->pluck('name')->all(),
            ])
            ->action(function (array $data, DocFile $record): void {
                app(DocumentTagService::class)->syncTags($record, $data);

                Notification::make()
                    ->title(__('document-archive::document-archive.tags.saved'))
                    ->success()
                    ->send();
            });
    }

    public static function makeForFileId(string $name = 'manageTags'): Action
    {
        return Action::make($name)
            ->label(__('document-archive::document-archive.tags.manage'))
            ->icon('heroicon-o-tag')
            ->schema(DocumentTagForm::schema(includeAdvanced: false))
            ->fillForm(function (array $arguments): array {
                $file = DocFile::query()->find($arguments['fileId'] ?? null);

                if (! $file) {
                    return ['tag_names' => []];
                }

                return [
                    'tag_names' => collect($file->getTagsWithColors())->pluck('name')->all(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $file = DocFile::query()->find($arguments['fileId'] ?? null);

                if (! $file) {
                    return;
                }

                app(DocumentTagService::class)->syncTags($file, $data);

                Notification::make()
                    ->title(__('document-archive::document-archive.tags.saved'))
                    ->success()
                    ->send();
            });
    }
}
