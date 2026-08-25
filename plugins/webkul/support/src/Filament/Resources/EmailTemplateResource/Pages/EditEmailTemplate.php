<?php

namespace Webkul\Support\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Support\Filament\Resources\EmailTemplateResource;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('support::filament/resources/email-template.notifications.deleted.title'))
                        ->body(__('support::filament/resources/email-template.notifications.deleted.body')),
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('support::filament/resources/email-template.notifications.updated.title'))
            ->body(__('support::filament/resources/email-template.notifications.updated.body'));
    }
}
