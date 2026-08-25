<?php

namespace App\Listeners\Hr;

use App\Mail\LeaveSubstituteRequestMail;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Filament\Clusters\MyTime\Resources\MyTimeOffResource;
use Webkul\TimeOff\Models\Leave;

class NotifyLeaveSubstitute
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

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

        $viewUrl = MyTimeOffResource::getUrl('view', ['record' => $leave->id]);

        $this->notifications->dispatch(
            NotificationDefinition::make('hr.leave.substitute_request')
                ->recipients([$substituteUser])
                ->title(__('hr-extensions::leave.notifications.substitute_request.title'))
                ->body(__('hr-extensions::leave.notifications.substitute_request.body', [
                    'employee' => $leave->employee?->name ?? '—',
                    'start'    => $leave->date_from?->format('Y-m-d') ?? '—',
                    'end'      => $leave->date_to?->format('Y-m-d') ?? '—',
                ]))
                ->color('warning')
                ->required()
                ->actions([
                    Action::make('view')
                        ->label(__('hr-extensions::leave.actions.view_leave'))
                        ->url($viewUrl),
                ])
                ->channels([NotificationChannel::Database, NotificationChannel::Mail])
                ->mail(fn (User $recipient) => new LeaveSubstituteRequestMail($leave))
                ->build(),
        );
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
