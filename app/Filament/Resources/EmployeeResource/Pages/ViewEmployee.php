<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Actions\Hr\CloseEmployeeFileAction;
use App\Filament\Actions\Hr\ReopenEmployeeFileAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ViewEmployee as BaseViewEmployee;
use Webkul\Support\Models\ActivityPlan;

class ViewEmployee extends BaseViewEmployee
{
    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->label(__('employees::filament/resources/employee/pages/view-employee.header-actions.chatter'))
                ->resource(static::$resource)
                ->activityPlans(ActivityPlan::employees()->pluck('name', 'id')),
            CloseEmployeeFileAction::make(),
            ReopenEmployeeFileAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/resources/employee/pages/view-employee.header-actions.delete.notification.title'))
                        ->body(__('employees::filament/resources/employee/pages/view-employee.header-actions.delete.notification.body')),
                ),
        ];
    }
}
