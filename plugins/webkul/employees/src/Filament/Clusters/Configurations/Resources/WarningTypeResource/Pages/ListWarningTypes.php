<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\WarningTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\WarningTypeResource;

class ListWarningTypes extends ListRecords
{
    protected static string $resource = WarningTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/clusters/configurations/resources/warning-type/pages/list-warning-type.header-actions.create.label'))
                ->createAnother(false)

                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/warning-type/pages/list-warning-type.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/warning-type/pages/list-warning-type.header-actions.create.notification.body')),
                ),
        ];
    }
}
