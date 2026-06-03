<?php

namespace App\Listeners\Hr;

use Filament\Notifications\Notification;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class NotifyEmployeeFileClosure
{
    public function handle(Employee $employee, User $closedBy): void
    {
        if ($employee->user) {
            Notification::make()
                ->title(__('hr-extensions::employee.notifications.file_closed_title'))
                ->body(__('hr-extensions::employee.notifications.file_closed_body'))
                ->danger()
                ->sendToDatabase($employee->user);
        }

        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', 'hr_manager'))->get() as $hrManager) {
            Notification::make()
                ->title(__('hr-extensions::employee.notifications.file_closed_hr_title'))
                ->body(__('hr-extensions::employee.notifications.file_closed_hr_body', [
                    'employee' => $employee->name,
                    'by'       => $closedBy->name,
                ]))
                ->warning()
                ->sendToDatabase($hrManager);
        }
    }
}
