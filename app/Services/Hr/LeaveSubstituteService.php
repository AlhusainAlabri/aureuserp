<?php

namespace App\Services\Hr;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Webkul\TimeOff\Models\Leave;

class LeaveSubstituteService
{
    public function accept(Leave $leave): void
    {
        if (! $this->hasSubstituteColumns()) {
            return;
        }

        $leave->update([
            'substitute_accepted_at' => now(),
            'substitute_declined_at' => null,
        ]);

        $leave->loadMissing(['employee.user', 'substituteEmployee']);

        $requester = $leave->employee?->user;

        if (! $requester) {
            return;
        }

        Notification::make()
            ->title(__('hr-extensions::leave.notifications.substitute_accepted.title'))
            ->body(__('hr-extensions::leave.notifications.substitute_accepted.body', [
                'substitute' => $leave->substituteEmployee?->name ?? '—',
            ]))
            ->success()
            ->sendToDatabase($requester);
    }

    public function decline(Leave $leave): void
    {
        if (! $this->hasSubstituteColumns()) {
            return;
        }

        $leave->update([
            'substitute_declined_at' => now(),
            'substitute_accepted_at' => null,
        ]);

        $leave->loadMissing(['employee.user', 'substituteEmployee']);

        $requester = $leave->employee?->user;

        if (! $requester) {
            return;
        }

        Notification::make()
            ->title(__('hr-extensions::leave.notifications.substitute_declined.title'))
            ->body(__('hr-extensions::leave.notifications.substitute_declined.body', [
                'substitute' => $leave->substituteEmployee?->name ?? '—',
            ]))
            ->warning()
            ->sendToDatabase($requester);
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
