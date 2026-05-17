<?php

namespace Webkul\Employee\Filament\Resources\WarningTypeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Employee\Filament\Resources\WarningTypeResource;

class ViewWarningType extends ViewRecord
{
    protected static string $resource = WarningTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/warning-type/pages/view-warning-type.header-actions.delete.notification.title'))
                        ->body(__('employees::filament/resources/warning-type/pages/view-warning-type.header-actions.delete.notification.body')),
                ),
        ];
    }
}
