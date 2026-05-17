<?php

namespace Webkul\Employee\Filament\Resources\WarningTypeResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Employee\Filament\Resources\WarningTypeResource;

class CreateWarningType extends CreateRecord
{
    protected static string $resource = WarningTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('employees::filament/resources/warning-type/pages/create-warning-type.notification.title'))
            ->body(__('employees::filament/resources/warning-type/pages/create-warning-type.notification.body'));
    }
}
