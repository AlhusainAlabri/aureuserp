<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Actions\Hr\CloseEmployeeFileAction;
use App\Filament\Actions\Hr\ReopenEmployeeFileAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ViewEmployee as BaseViewEmployee;
use Webkul\Payroll\Filament\Actions\CreateEndOfServiceSettlementAction;
use Webkul\Support\Models\ActivityPlan;

class ViewEmployee extends BaseViewEmployee
{
    /**
     * The end-of-service settlement action exists only where the payroll module is installed.
     *
     * @return array<int, CreateEndOfServiceSettlementAction>
     */
    protected function settlementActions(): array
    {
        if (! class_exists(CreateEndOfServiceSettlementAction::class) || ! Schema::hasTable('payroll_end_of_service_settlements')) {
            return [];
        }

        return [CreateEndOfServiceSettlementAction::make()];
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->label(__('employees::filament/resources/employee/pages/view-employee.header-actions.chatter'))
                ->resource(static::$resource)
                ->activityPlans(ActivityPlan::employees()->pluck('name', 'id')),
            CloseEmployeeFileAction::make(),
            ReopenEmployeeFileAction::make(),
            ...$this->settlementActions(),
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
