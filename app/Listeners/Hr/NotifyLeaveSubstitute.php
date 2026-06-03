<?php

namespace App\Listeners\Hr;

use App\Mail\LeaveSubstituteRequestMail;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\TimeOff\Filament\Clusters\MyTime\Resources\MyTimeOffResource;
use Webkul\TimeOff\Models\Leave;

class NotifyLeaveSubstitute
{
    public function handleCreated(Leave $leave): void
    {
        if (! $this->hasSubstituteColumns()) {
            return;
        }

        if (! $leave->substitute_employee_id) {
            return;
        }

        $leave->loadMissing(['employee', 'substituteEmployee.user']);

        $substituteUser = $leave->substituteEmployee?->user;

        if (! $substituteUser) {
            return;
        }

        Mail::to($substituteUser)->queue(new LeaveSubstituteRequestMail($leave));

        $viewUrl = MyTimeOffResource::getUrl('view', ['record' => $leave->id]);

        Notification::make()
            ->title(__('hr-extensions::leave.notifications.substitute_request.title'))
            ->body(__('hr-extensions::leave.notifications.substitute_request.body', [
                'employee' => $leave->employee?->name ?? '—',
                'start'    => $leave->date_from?->format('Y-m-d') ?? '—',
                'end'      => $leave->date_to?->format('Y-m-d') ?? '—',
            ]))
            ->warning()
            ->actions([
                Action::make('view')
                    ->label(__('hr-extensions::leave.actions.view_leave'))
                    ->url($viewUrl),
            ])
            ->sendToDatabase($substituteUser);
    }

    protected function hasSubstituteColumns(): bool
    {
        foreach (['time_off_leaves', 'hr_leaves', 'leaves'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'substitute_employee_id')) {
                return true;
            }
        }

        return false;
    }
}
