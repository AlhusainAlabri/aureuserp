<?php

namespace Webkul\DocumentArchive\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentShareService;

class ShareDocumentAction
{
    public static function make(string $name = 'share'): Action
    {
        return Action::make($name)
            ->label(__('document-archive::document-archive.actions.share'))
            ->icon('heroicon-o-share')
            ->visible(fn (DocFile $record): bool => auth()->user()?->can('share', $record) ?? false)
            ->schema(static::schema())
            ->action(function (array $data, DocFile $record): void {
                static::createShareLink($record, $data);
            });
    }

    public static function makeForFileId(string $name = 'shareDocument'): Action
    {
        return Action::make($name)
            ->label(__('document-archive::document-archive.actions.share'))
            ->icon('heroicon-o-share')
            ->schema(static::schema())
            ->action(function (array $data, array $arguments): void {
                $file = DocFile::query()->find($arguments['fileId'] ?? null);

                if (! $file) {
                    return;
                }

                static::createShareLink($file, $data);
            });
    }

    /**
     * @return array<int, Component>
     */
    protected static function schema(): array
    {
        return [
            TextInput::make('shared_with_email')
                ->label(__('document-archive::document-archive.share.shared_with_email'))
                ->email(),
            Toggle::make('view_once')
                ->label(__('document-archive::document-archive.share.view_once'))
                ->default(false),
            DateTimePicker::make('expires_at')
                ->label(__('document-archive::document-archive.share.expires_at'))
                ->native(false),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function createShareLink(DocFile $record, array $data): void
    {
        $link = app(DocumentShareService::class)->createLink($record, $data);

        Notification::make()
            ->title(__('document-archive::document-archive.share.created_title'))
            ->body(__('document-archive::document-archive.share.created_body', [
                'url' => $link->getPublicUrl(),
            ]))
            ->success()
            ->send();
    }
}
