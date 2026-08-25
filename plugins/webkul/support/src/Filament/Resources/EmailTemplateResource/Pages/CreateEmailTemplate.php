<?php

namespace Webkul\Support\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Support\Filament\Resources\EmailTemplateResource;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('support::filament/resources/email-template.notifications.created.title'))
            ->body(__('support::filament/resources/email-template.notifications.created.body'));
    }
}
