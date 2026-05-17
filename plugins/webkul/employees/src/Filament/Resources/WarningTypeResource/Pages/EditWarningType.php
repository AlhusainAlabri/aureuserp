<?php

namespace Webkul\Employee\Filament\Resources\WarningTypeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Webkul\Employee\Filament\Resources\WarningTypeResource;

class EditWarningType extends EditRecord
{
    protected static string $resource = WarningTypeResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('employees::filament/resources/warning-type/pages/edit-warning-type.notification.title'))
            ->body(__('employees::filament/resources/warning-type/pages/edit-warning-type.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/warning-type/pages/edit-warning-type.header-actions.delete.notification.title'))
                        ->body(__('employees::filament/resources/warning-type/pages/edit-warning-type.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }
}
